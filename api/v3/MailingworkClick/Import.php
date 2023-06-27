<?php

use CRM_Mailingwork_ExtensionUtil as E;

/**
 * MailingworkClick.Import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_mailingwork_click_Import_spec(&$spec) {
  $spec['mailingwork_mailing_id'] = [
    'api.required' => FALSE,
    'description'  => 'Select a single MailingworkMailing to import clicks for',
    'name'         => 'mailingwork_mailing_id',
    'title'        => 'MailingworkMailing ID',
    'type'         => CRM_Utils_TYPE::T_INT,
  ];

  $spec['password'] = [
    'api.required' => TRUE,
    'description'  => 'Password of the Mailingwork API user',
    'name'         => 'password',
    'title'        => 'Mailingwork API password',
    'type'         => CRM_Utils_TYPE::T_STRING,
  ];

  $spec['skip_mailing_sync'] = [
    'api.default'  => FALSE,
    'api.required' => FALSE,
    'description'  => 'Skip mailing sync with Mailingwork before importing clicks',
    'name'         => 'skip_mailing_sync',
    'title'        => 'Skip mailing sync',
    'type'         => CRM_Utils_TYPE::T_BOOLEAN,
  ];

  $spec['username'] = [
    'api.required' => TRUE,
    'description'  => 'Username of the Mailingwork API user',
    'name'         => 'username',
    'title'        => 'Mailingwork API user',
    'type'         => CRM_Utils_TYPE::T_STRING,
  ];
}

/**
 * MailingworkClick.Import API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_mailingwork_click_Import($params) {
  $processor = new CRM_Mailingwork_Processor_Greenpeace_Clicks($params);
  return civicrm_api3_create_success($processor->import());
}
