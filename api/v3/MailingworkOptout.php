<?php

/**
 * MailingworkOptout.import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_optout_import_spec(&$spec) {
  $spec['soft_limit'] = [
    'name'         => 'soft_limit',
    'title'        => 'Soft limit',
    'description'  => 'Soft limit for number of opt-outs to process.',
    'type'         => CRM_Utils_TYPE::T_INT,
    'api.required' => 0,
    'api.default'  => 0,
  ];

  $spec['skip_mailing_sync'] = [
    'name'         => 'skip_mailing_sync',
    'title'        => 'Skip fetching new mailings?',
    'description'  => 'Should fetching new mailings be skipped before importing recipients?',
    'type'         => CRM_Utils_TYPE::T_BOOLEAN,
    'api.required' => 0,
    'api.default'  => FALSE,
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
 * MailingworkOptout.import API
 *
 * @param $params API parameters
 *
 * @return array API result
 * @throws \Exception
 */
function civicrm_api3_mailingwork_optout_import($params) {
  $params['soft_limit'] = abs($params['soft_limit']);
  $processor = new CRM_Mailingwork_Processor_Greenpeace_Optouts($params);
  return civicrm_api3_create_success($processor->import());
}
