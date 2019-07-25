<?php
use CRM_Mandrill_ExtensionUtil as E;

class CRM_Mandrill_Page_Webhook extends CRM_Core_Page {
  /**
   * @var array keys are names like Invalid; values are IDs
   */
  public static $cached_bounce_types = [];
  public function run() {
    try {
      $events = $this->validateInput($_POST);
      Civi::log()->info("Mandrill Webhook data", ['data' => serialize($_POST)]);
      foreach ($events as $event) {
        $debugging_info = $this->getSimplifiedEventData($event);
        try {
          $this->processEvent($event);
          Civi::log()->info("Mandrill Webhook event successfully processed", $debugging_info);
        }
        catch (CRM_Mandrill_WebhookEventFailedException $e) {
          Civi::log()->error("Mandrill Webhook event not processed: " . $e->getMessage(), $debugging_info);
        }
      }
    }
    catch (CRM_Mandrill_WebhookInvalid $e) {
      // Signature mismatch etc.
      Civi::log()->error("Mandrill Webhook not processed: " . $e->getMessage(), []);
    }
    catch (\Exception $e) {
      Civi::log()->notice("Mandrill Webhook fatal (returning 500)", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
      header("$_SERVER[SERVER_PROTOCOL] 500 Server Error");
    }
    CRM_Utils_System::civiExit();
  }
  public function getSimplifiedEventData($event) {
    $data = [
      'type'     => $event['event'] ?? '(none)',
      'email'    => $event['msg']['email'] ?? '(none)',
      'subject'  => $event['msg']['subject'] ?? '(none)',
      'id'       => $event['msg']['_id'] ?? '(none)',
      'time'     => $event['msg']['_ts'] ?? '(none)',
      'metadata' => $event['msg']['metadata'] ?? '(none)',
    ];
    return $data;
  }
  /**
   * Check input and return the event data if all ok.
   *
   * @param array $post ($_POST)
   * @return array
   */
  public function validateInput($post) {

    // @todo check secret key matches.
    if (function_exists('getallheaders')) {
      $headers = getallheaders();
    }
    else {
      // Some server configs do not provide getallheaders().
      // We only care about the X-Mandrill-Signature header so try to extract that from $_SERVER.
      $headers = [];
      if (isset($_SERVER['HTTP_X_MANDRILL_SIGNATURE'])) {
        $headers['X-Mandrill-Signature'] = $_SERVER['HTTP_X_MANDRILL_SIGNATURE'];
      }
    }

    if (empty($headers['X-Mandrill-Signature'])) {
      throw new CRM_Mandrill_WebhookInvalid('Missing signature');
    }

    $webhook_url = CRM_Utils_System::url('civicrm/mandrill/webhook', NULL, TRUE, NULL, FALSE);
    $webhook_key = Civi::settings()->get('mandrill_webhook_key');
    $expected_signature = $this->generateSignature($webhook_key, $webhook_url, $post);
    if ($headers['X-Mandrill-Signature'] !== $expected_signature) {
      throw new CRM_Mandrill_WebhookInvalid('Webhook signature did not match.');
    }

    if (empty($post['mandrill_events'])) {
      throw new CRM_Mandrill_WebhookInvalid("Missing mandrill_events key in POST.");
    }
    $events = json_decode($post['mandrill_events'], TRUE);
    if (!is_array($events)) {
      throw new CRM_Mandrill_WebhookInvalid("Missing mandrill_events data or not valid JSON.");
    }
    return $events;
  }
  /**
   * Generates a base64-encoded signature for a Mandrill webhook request.
   *
   * (code taken from Mandrill website)
   *
   * @param string $webhook_key the webhook's authentication key
   * @param string $url the webhook url
   * @param array $params the request's POST parameters
   *
   * @return String
   */
  function generateSignature($webhook_key, $url, $params) {
    $signed_data = $url;
    ksort($params);
    foreach ($params as $key => $value) {
      $signed_data .= $key;
      $signed_data .= $value;
    }

    return base64_encode(hash_hmac('sha1', $signed_data, $webhook_key, true));
  }

  /**
   * Actually process the data.
   */
  public function processEvent($event) {
    switch ($event['event']){
    case 'hard_bounce':
      $reason = $this->getBounceReason($event);
      $this->processCommonBounce($event, 'Invalid', $reason);
      break;

    case 'soft_bounce':
      $reason = $this->getBounceReason($event);
      $this->processCommonBounce($event, 'Syntax', $reason);
      break;

    case 'reject':
      // https://mandrill.zendesk.com/hc/en-us/articles/205582957-What-is-a-rejected-email-Rejection-Blacklist-
      $reason = 'Rejected by Mandrill (because of a previous hard bounce)';
      $this->processCommonBounce($event, 'Invalid', $reason);
      break;

    case 'send':
    case 'deferral':
    case 'unsub':
    case 'spam':
    case 'open':
    case 'click':
    default:
      throw new CRM_Mandrill_WebhookEventFailedException("Ignored '$event[event]' type event. Consider reconfiguring your webhook not to send these as it wastes resources.");
    }
  }
  /**
   * Extract human readable reason for bounces.
   *
   * @return String
   */
  public function getBounceReason($event) {
    return
        ($event['msg']['bounce_description'] ?? '')
        . " "
        . ($event['msg']['diag'] ?? '')
        . " "
        . "Mandrill Id: " . ($event['_id'] ?? '');
  }
  public function processCommonBounce($event, $type, $reason) {
    Civi::log()->info("Mandrill Webhook processing bounce (CiviCRM bounce type '$type')");
    $bounce_params = $this->extractVerpData($event);
    if (!$bounce_params) {
      throw new CRM_Mandrill_WebhookEventFailedException("Cannot find VERP data necessary to process bounce.");
    }
    $bounce_params['bounce_type_id'] = $this->getCiviBounceTypeId($type);
    $bounce_params['bounce_reason'] = $reason;
    Civi::log()->info("Mandrill Webhook processing bounce params ", $bounce_params);

    // Note: it is not possible to set the correct timestamp for the bounce, so
    // it's just recorded as the current time (i.e. the time the webhook fired,
    // which might be up to an hour after the actual bounce).
    $bounced = CRM_Mailing_Event_BAO_Bounce::create($bounce_params);
    if (!$bounced) {
      Civi::log()->warning("Mandrill Webhook failed to create bounce. Perhaps an entity was deleted, or something?");
    }
  }
  /**
   * Extract data from verp data if we can.
   *
   * First we look for data we created, under the key 'civiverp'
   * If that's not found we look for data from the MTE extension 'CiviCRM_Mandrill_id'
   *
   * Note metadata we created looks like (assuming . for verp separator)
   *     <job_id>.<event_queue_id>.<hash>
   *
   * And metadata created by MTE looks like:
   *     <other_id>.<prefix_and_verp_token>.<job_id>.<event_queue_id>.<hash>
   *
   * @param string $data e.g. 'prefixb.22.23.1bc42342342@example.com'
   * @return array with keys: job_id, event_queue_id, hash (or NULL)
   */
  public function extractVerpData($event) {

    // Extract metadata from civiverp or CiviCRM_Mandrill_id
    $data = NULL;
    foreach (['civiverp', 'CiviCRM_Mandrill_id'] as $creator) {
      if (!empty($event['msg']['metadata'][$creator])) {
        $data = $event['msg']['metadata'][$creator];
        break;
      }
    }

    if (!$data) {
      // No data for us to consider.
      return;
    }

    $parts = explode(Civi::settings()->get('verpSeparator'), $data);

    if ($creator === 'CiviCRM_Mandrill_id' && count($parts) === 5) {
      // The MTE extension also prepends an activity ID to the start of
      // CiviCRM's normal VERP data, so we remove that first.
      // We don't care about the 'verp token' which is also inlcuded here.
      $parts = array_slice($parts, 2);
    }

    $verp_items = (count($parts) === 3)
      ? array_combine(['job_id', 'event_queue_id', 'hash'], $parts)
      : [];

    return $verp_items;
  }

  /**
   * Get CiviCRM bounce type.
   *
   * @param string Name of bounce type, e.g. Invalid|Syntax|Spam|Relay|Quota|Loop|Inactive|Host|Dns|Away|AOL
   * @return int Bounce type ID
   */
  protected function getCiviBounceTypeId($name) {
    if (!isset(static::$cached_bounce_types[$name])) {
      $bounce_type = new CRM_Mailing_DAO_BounceType();
      $bounce_type->name = $name;
      $bounce_type->find(TRUE);
      static::$cached_bounce_types[$name] = $bounce_type->id;
    }
    return static::$cached_bounce_types[$name];
  }
}
class CRM_Mandrill_WebhookEventFailedException extends Exception {}
class CRM_Mandrill_WebhookInvalid extends Exception {}
