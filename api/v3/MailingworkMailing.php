<?php

/**
 * MailingworkMailing.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_mailing_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * MailingworkMailing.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_mailingwork_mailing_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * MailingworkMailing.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_mailingwork_mailing_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * MailingworkMailing.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_mailingwork_mailing_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * MailingworkMailing.import API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_mailing_import_spec(&$spec) {
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
function civicrm_api3_mailingwork_mailing_import($params) {
  $processor = new CRM_Mailingwork_Processor_Greenpeace_Mailings($params);
  return civicrm_api3_create_success($processor->import());
}

/**
 * MailingworkMailing.getcampaign API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_mailing_getcampaign_spec(&$spec) {
  $spec['id'] = [
    'name'         => 'id',
    'title'        => 'Mailing ID',
    'type'         => CRM_Utils_TYPE::T_INT,
    'api.required' => 1,
  ];
}

function civicrm_api3_mailingwork_mailing_getcampaign($params) {
  $campaign = civicrm_api3('Campaign', 'getsingle', [
    'id' => CRM_Mailingwork_BAO_MailingworkMailing::getEffectiveCampaignId($params['id']),
  ]);
  return civicrm_api3_create_success($campaign);
}
