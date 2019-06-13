<?php

require_once 'mailingwork.civix.php';
require_once __DIR__ . '/vendor/autoload.php';

use CRM_Mailingwork_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mailingwork_civicrm_config(&$config) {
  _mailingwork_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function mailingwork_civicrm_xmlMenu(&$files) {
  _mailingwork_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mailingwork_civicrm_install() {
  _mailingwork_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function mailingwork_civicrm_postInstall() {
  _mailingwork_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function mailingwork_civicrm_uninstall() {
  _mailingwork_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mailingwork_civicrm_enable() {
  _mailingwork_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function mailingwork_civicrm_disable() {
  _mailingwork_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function mailingwork_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mailingwork_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function mailingwork_civicrm_managed(&$entities) {
  _mailingwork_civix_civicrm_managed($entities);
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
function mailingwork_civicrm_caseTypes(&$caseTypes) {
  _mailingwork_civix_civicrm_caseTypes($caseTypes);
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
function mailingwork_civicrm_angularModules(&$angularModules) {
  _mailingwork_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function mailingwork_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _mailingwork_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function mailingwork_civicrm_entityTypes(&$entityTypes) {
  _mailingwork_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function mailingwork_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function mailingwork_civicrm_navigationMenu(&$menu) {
  _mailingwork_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('Mailingwork'),
    'name' => 'Mailingwork',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 2,
  ));
  _mailingwork_civix_insert_navigation_menu($menu, 'Mailings/Mailingwork', array(
    'label' => E::ts('Mailings'),
    'name' => 'Mailingwork_Mailings',
    'url' => 'civicrm/mailingwork/mailings',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _mailingwork_civix_insert_navigation_menu($menu, 'Mailings/Mailingwork', array(
    'label' => E::ts('Folders'),
    'name' => 'Mailingwork_Folders',
    'url' => 'civicrm/mailingwork/folders',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _mailingwork_civix_navigationMenu($menu);
}
