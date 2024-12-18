<?php

require_once 'mailingwork.civix.php';
$autoload = __DIR__ . '/vendor/autoload.php';

if (file_exists($autoload)) {
  require_once $autoload;
}

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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mailingwork_civicrm_install() {
  _mailingwork_civix_civicrm_install();
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
