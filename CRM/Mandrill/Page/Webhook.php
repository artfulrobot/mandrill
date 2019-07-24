<?php
use CRM_Mandrill_ExtensionUtil as E;

class CRM_Mandrill_Page_Webhook extends CRM_Core_Page {
  public function run() {
    try {
      $events = $this->validateInput($_POST);
      foreach ($events as $event) {
        try {
          $this->processEvent($event);
          Civi::log()->info("Mandrill Webhook event successfully processed", ['event' => $event]);
        }
        catch (CRM_Mandrill_WebhookEventFailedException $e) {
          Civi::log()->error("Mandrill Webhook event not processed: " . $e->getMessage(), ['event' => $event]);
        }
      }
    }
    catch (CRM_Mandrill_WebhookInvalid $e) {
      // Signature mismatch etc.
      Civi::log()->error("Mandrill Webhook not processed: " . $e->getMessage(), []);
    }
    /*
    catch (CRM_Mandrill_WebhookRejectedException $e) {
      Civi::log()->notice("Mandrill Webhook ignored (returning 406)", ['message' => $e->getMessage()]);
      header("$_SERVER[SERVER_PROTOCOL] 406 " . $e->getMessage());
      echo json_encode(['error' => $e->getMessage()]);
    }
     */
    catch (\Exception $e) {
      Civi::log()->notice("Mandrill Webhook fatal (returning 500)", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
      header("$_SERVER[SERVER_PROTOCOL] 500 Server Error");
    }
    CRM_Utils_System::civiExit();
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
  /*
   * Generates a base64-encoded signature for a Mandrill webhook request.
   * @param string $webhook_key the webhook's authentication key
   * @param string $url the webhook url
   * @param array $params the request's POST parameters
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
      $this->processPermanentBounce($event);
      break;
    case 'soft_bounce':
      $this->processTemporaryBounce($event);
      break;

    case 'send':
    case 'deferral':
    case 'unsub':
    case 'reject':
    case 'spam':
    case 'open':
    case 'click':
    default:
      throw new CRM_Mandrill_WebhookEventFailedException("Ignored '$event[event]' type event.");
    }
  }

  /**
   * Get API key from Mandrill account.
   *
   * @return string
   */
  public function getApiKey() {
    return Civi::settings()->get('mandrill_webhook_key');
  }

  public function processPermanentBounce($event) {
    $this->processCommonBounce($event, 'Invalid');
  }
  public function processTemporaryBounce($event) {
    $this->processCommonBounce($event, 'Syntax');
  }
  public function processCommonBounce($event, $type) {
    Civi::log()->info("Mandrill Webhook processing bounce: $type");
    // Ideally we would have access to 'X-CiviMail-Bounce' but I don't think we do.
    $bounce_params = $this->extractVerpData($event);
    if (!$bounce_params) {
      throw new CRM_Mandrill_WebhookEventFailedException("Cannot find VERP data necessary to process bounce.");
    }
    $bounce_params['bounce_type_id'] = $this->getCiviBounceTypeId($type);
    /*$bounce_params['bounce_reason'] = ($event->{'delivery-status'}->description ?? '')
      . " "
      . ($event->{'delivery-status'}->message ?? '')
      . " Mandrill Event Id: " . ($event->id ?? '');
     */
    //$bounced = CRM_Mailing_Event_BAO_Bounce::create($bounce_params);
  }
  /**
   * Extract data from verp data if we can.
   *
   * @param string $data e.g. 'b.22.23.1bc42342342@example.com'
   * @return array with keys: job_id, event_queue_id, hash
   */
  public function extractVerpData($event) {
    return; // xxx

    if (!empty($event->{'user-variables'}->{'civimail-bounce'})) {
      // Great, we found the header we added in our hook_civicrm_alterMailParams.
      $data = $event->{'user-variables'}->{'civimail-bounce'};
    }
    elseif (!empty($event->envelope->sender)) {
      // Hmmm. See if the envelope sender has anything useful in it.
      $data = $event->envelope->sender;
    }

    // Credit goes to https://github.com/mecachisenros for the verp parsing:
    $verp_separator = Civi::settings()->get('verpSeparator');
		$localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $parts = explode($verp_separator, substr(substr($data, 0, strpos($data, '@')), strlen($localpart) + 2));

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
    $bounce_type = new CRM_Mailing_DAO_BounceType();
    $bounce_type->name = $name;
    $bounce_type->find(TRUE);
    return $bounce_type->id;
  }
}
class CRM_Mandrill_WebhookEventFailedException extends Exception {}
class CRM_Mandrill_WebhookInvalid extends Exception {}
