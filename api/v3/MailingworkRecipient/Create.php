<?php
use bconnect\MailingWork\Client;

/**
 * MailingworkRecipient.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_recipient_create_spec(&$spec) {
  $spec['mailingwork_fields'] = [
    'name'         => 'mailingwork_fields',
    'title'        => 'Mailingwork Fields',
    'description'  => 'Array of Mailingwork fields (key = field ID, value = field value)',
    'api.required' => 1,
  ];

  $spec['mailingwork_list_id'] = [
    'name'         => 'mailingwork_list_id',
    'title'        => 'Mailingwork List ID',
    'description'  => 'Mailingwork List ID (or comma-separate list of List IDs)',
    'type'         => CRM_Utils_TYPE::T_STRING,
    'api.required' => 1,
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
 * MailingworkRecipient.import API
 *
 * @param $params API parameters
 *
 * @return array API result
 * @throws \Exception
 */
function civicrm_api3_mailingwork_recipient_create($params) {
  $client = Client::getClient($params['username'], $params['password']);
  $response = $client->api('recipient')->createRecipient(
    $params['mailingwork_list_id'],
    $params['mailingwork_fields']
  );
  return civicrm_api3_create_success($response);
}
