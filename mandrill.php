<?php

require_once 'mandrill.civix.php';
use CRM_Mandrill_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mandrill_civicrm_config(&$config) {
  _mandrill_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function mandrill_civicrm_xmlMenu(&$files) {
  _mandrill_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mandrill_civicrm_install() {
  _mandrill_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function mandrill_civicrm_postInstall() {
  _mandrill_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function mandrill_civicrm_uninstall() {
  _mandrill_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mandrill_civicrm_enable() {
  _mandrill_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function mandrill_civicrm_disable() {
  _mandrill_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function mandrill_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mandrill_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function mandrill_civicrm_managed(&$entities) {
  _mandrill_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function mandrill_civicrm_caseTypes(&$caseTypes) {
  _mandrill_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function mandrill_civicrm_angularModules(&$angularModules) {
  _mandrill_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function mandrill_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _mandrill_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function mandrill_civicrm_entityTypes(&$entityTypes) {
  _mandrill_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Try to embed VERP data in a way that Mandrill will provide to webhooks.
 *
 * Implements hook_civicrm_alterMailParams(&$params, $context)
 */
function mandrill_civicrm_alterMailParams(&$params, $context) {
  if (in_array($context, ['civimail', 'flexmailer'])) {
    if (!empty($params['Return-Path'])) {
      // Copy this header to one that will be returned by Mandrill's webhook.
      $params['headers']['X-MC-Metadata'] = json_encode(['civiverp' => $params['Return-Path']]);
    }
    elseif (!empty($params['X-CiviMail-Bounce'])) {
      $params['headers']['X-MC-Metadata'] = json_encode(['civiverp' => $params['X-CiviMail-Bounce']]);
    }
  }
  else {
    // example case:
    // context === 'singleEmail'
    // $params[groupName] == 'Activity Email Sender'
    $params['headers']['X-MC-Metadata'] = '{ "singleEmail": "1" }';
  }
  /*
 ⬦ $context = (string [10]) `flexmailer`
   ⬦ $params['X-CiviMail-Mosaico'] = (string [3]) `Yes`
   ⬦ $params['List-Unsubscribe'] = (string [52]) `<mailto:u.72.32.fa5f74c72c53c77f@crm.artfulrobot.uk>`
   ⬦ $params['Precedence'] = (string [4]) `bulk`
   ⬦ $params['job_id'] = (string [2]) `72`
   ⬦ $params['From'] = (string [37]) `"Artful Robot" <hello@artfulrobot.uk>`
   ⬦ $params['toEmail'] = (string [20]) `hello@artfulrobot.uk`
   ⬦ $params['toName'] = (string [12]) `Artful Robot`
   ⬦ $params['Return-Path'] = (string [43]) `b.72.32.fa5f74c72c53c77f@crm.artfulrobot.uk`
   ⬦ $params['X-CiviMail-Bounce'] = (string [43]) `b.72.32.fa5f74c72c53c77f@crm.artfulrobot.uk`
   ⬦ $params['attachments'] = (array)
   */
}
/**
 * Implementation of hook_civicrm_idsException().
 *
 * Prevent webhook data form from being processed by the IDS
 */
function mandrill_civicrm_idsException(&$skip) {
  $skip[] = 'civicrm/mandrill/webhook';
}
