<?php

/**
 * MailingworkRetainedEmails.import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_retained_emails_import_spec(&$spec) {
  $spec['soft_limit'] = [
    'name'         => 'soft_limit',
    'title'        => 'Soft limit',
    'description'  => 'Soft limit for number of bounces to process.',
    'type'         => CRM_Utils_TYPE::T_INT,
    'api.required' => 0,
    'api.default'  => 0,
  ];

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
 * MailingworkRetainedEmails.import API
 *
 * @param $params API parameters
 *
 * @return array API result
 * @throws \Exception
 */
function civicrm_api3_mailingwork_retained_emails_import($params) {
  $processor = new CRM_Mailingwork_Processor_Greenpeace_RetainedEmails($params);
  return civicrm_api3_create_success($processor->import());
}
