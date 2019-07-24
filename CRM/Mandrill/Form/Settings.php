<?php

use CRM_Mandrill_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Mandrill_Form_Settings extends CRM_Admin_Form_Setting {
  protected $_settings = [
    'mandrill_webhook_key' =>  CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  ];
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->assign('elementNames', array_keys($this->_settings));
  }
}
