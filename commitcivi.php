<?php

require_once 'commitcivi.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function commitcivi_civicrm_config(&$config) {
  _commitcivi_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function commitcivi_civicrm_xmlMenu(&$files) {
  _commitcivi_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function commitcivi_civicrm_install() {
  _commitcivi_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function commitcivi_civicrm_uninstall() {
  _commitcivi_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function commitcivi_civicrm_enable() {
  _commitcivi_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function commitcivi_civicrm_disable() {
  _commitcivi_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function commitcivi_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _commitcivi_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function commitcivi_civicrm_managed(&$entities) {
  _commitcivi_civix_civicrm_managed($entities);
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
function commitcivi_civicrm_caseTypes(&$caseTypes) {
  _commitcivi_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function commitcivi_civicrm_angularModules(&$angularModules) {
  _commitcivi_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function commitcivi_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _commitcivi_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hooks civicrm_buildForm().
 *
 */
function commitcivi_civicrm_buildForm($formName, &$form) {
  // CRM_Core_Error::debug_log_message("checking formName $formName");
  if (
      $formName=='CRM_Contribute_Form_AdditionalPayment'
      || $formName=='CRM_Contribute_Form_Search'
      || $formName=='CRM_Contribute_Form_ContributionView'
    ) {
      // CRM_Core_Error::debug_log_message("bweep!");
      CRM_Core_Resources::singleton()->addScriptFile('eu.wemove.commitcivi', 'add_payment_links.js');
  }
}