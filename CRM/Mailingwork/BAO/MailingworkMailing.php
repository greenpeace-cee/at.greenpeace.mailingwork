<?php
use CRM_Mailingwork_ExtensionUtil as E;

class CRM_Mailingwork_BAO_MailingworkMailing extends CRM_Mailingwork_DAO_MailingworkMailing {

  /**
   * Create a new MailingworkMailing based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Mailingwork_DAO_MailingworkMailing|NULL
   *
  public static function create($params) {
    $className = 'CRM_Mailingwork_DAO_MailingworkMailing';
    $entityName = 'MailingworkMailing';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
