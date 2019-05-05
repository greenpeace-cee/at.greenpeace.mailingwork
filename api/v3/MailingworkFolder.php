<?php
use CRM_Mailingwork_ExtensionUtil as E;

/**
 * MailingworkFolder.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_folder_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * MailingworkFolder.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_mailingwork_folder_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * MailingworkFolder.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_mailingwork_folder_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * MailingworkFolder.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_mailingwork_folder_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * MailingworkFolder.import API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_folder_import_spec(&$spec) {
  $spec['username'] = [
    'name'         => 'username',
    'title'        => 'Mailingwork API User',
    'type'         => CRM_Utils_TYPE::T_STRING,
    'api.required' => 1,
  ];

  $spec['password'] = [
    'name'         => 'password',
    'title'        => 'Mailingwork API Password',
    'type'         => CRM_Utils_TYPE::T_STRING,
    'api.required' => 1,
  ];
}

/**
 * MailingworkRecipient.import API
 *
 * @param $params API parameters
 *
 * @return array API result
 * @throws \Exception
 */
function civicrm_api3_mailingwork_folder_import($params) {
  $processor = new CRM_Mailingwork_Processor_Greenpeace_Folders($params);
  return civicrm_api3_create_success($processor->import());
}