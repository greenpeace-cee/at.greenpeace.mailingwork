<?php
use CRM_Donutapp_ExtensionUtil as E;

/**
 * MailingworkRecipient.import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingwork_recipient_import_spec(&$spec) {
  $spec['soft_limit'] = [
    'name'         => 'soft_limit',
    'title'        => 'Soft limit',
    'description'  => 'Soft limit for number of recipients to process.',
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

  $spec['mailingwork_mailing_id'] = [
    'name'         => 'mailingwork_mailing_id',
    'title'        => 'Mailingwork Mailing ID',
    'description'  => 'Which mailing should be synced? Note: This is NOT mailingwork_identifier!',
    'type'         => CRM_Utils_TYPE::T_INT,
    'api.required' => 0,
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
function civicrm_api3_mailingwork_recipient_import($params) {
  $params['soft_limit'] = abs($params['soft_limit']);
  if (!empty($params['mailingwork_mailing_id']) && empty($params['skip_mailing_sync'])) {
    $params['skip_mailing_sync'] = TRUE;
  }
  try {
    $processor = new CRM_Mailingwork_Processor_Greenpeace_Recipients($params);
    return civicrm_api3_create_success($processor->import());
    // TODO: move this to Processor and clean it up
    foreach ($client->api('mailing')->getMailings(NULL, NULL, '2017-06-01') as $mailing) {
      if ($mailing->status == 'drafted') {
        continue;
      }
      if ($mailing->id != 1067) {
        continue;
      }
      $mailing_id = $mailing->id;
      $mailing = $client->api('mailing')->getEmailById($mailing->id);
      $start = 0;
      $limit = 1000;
      $more_pages = TRUE;
      while ($more_pages) {
        $recipients = $client->api('recipient')
          ->getRecipientsByEmailId($mailing_id, NULL, NULL, $start, $limit);
        $count = count($recipients);
        $start += $count;
        if ($count < $limit) {
          $more_pages = FALSE;
        }
        foreach ($recipients as $recipient) {
          $recipientFields = [];
          foreach ($recipient->fields as $field) {
            $recipientFields[$fields[$field->field]] = $field->value;
          }
          //$recipientFields['Contact_ID'] = 462;
          if (!empty($recipientFields['Contact_ID'])) {
            $query = CRM_Core_DAO::executeQuery(CRM_Identitytracker_Configuration::getSearchSQL(), [
              1 => ['internal', 'String'],
              2 => [$recipientFields['Contact_ID'], 'String'],
            ]);
            if (!$query->fetch()) {
              continue;
            };
            $contact_id = $query->entity_id;

            $activity = new CRM_Activity_BAO_Activity();
            $activity->subject = trim($mailing->subject);
            $activity->activity_date_time = $recipient->date;
            $activity->activity_type_id = 101;
            $activity = $activity->save();

            CRM_Core_DAO::executeQuery(
              "INSERT INTO civicrm_value_email_information
                        (entity_id, email, mailing_subject, mailing_description,
                         sender_name, mailing_type, email_provider, mailing_id)
                      VALUES
                        (%1, %2, %3, %4, %5, %6, %7, %8)", [
              1 => [$activity->id, 'Integer'],
              2 => [$recipient->email, 'String'],
              3 => [trim($mailing->subject), 'String'],
              4 => [trim($mailing->description), 'String'],
              5 => [trim($mailing->senderName), 'String'],
              6 => [2, 'String'], // Transactional, @TODO: use $mailing->behavior
              7 => [1, 'String'], // Mailingwork
              8 => [$mailing_id, 'String']
            ]);

            $link = new CRM_Activity_BAO_ActivityContact();
            $link->contact_id = $contact_id;
            $link->activity_id = $activity->id;
            $link->record_type_id = 3;
            $link->save();

            /*addEmailActivity([
              'target_id' => $identity_result['id'],
              'activity_date_time' => $recipient->date,
              'email_information.email' => $recipient->email,
              'email_information.mailing_type' => $mailing->behavior,
              'email_information.mailing_subject' => trim($mailing->subject),
              'email_information.mailing_description' => trim($mailing->description),
              'email_information.sender_name' => trim($mailing->senderName),
              'email_information.mailing_id' => $mailing_id,
            ]);*/
          }
          // var_dump($mailing);
          // var_dump($recipient);
          // var_dump($recipientFields);
        }
        break;
      }
    }
  } catch (Exception $e) {
    var_dump($e);
  }
  return civicrm_api3_create_success();
}

function addEmailActivity($params) {
  $params = array_merge($params, [
    'activity_type_id'     => 'Online_Mailing',
    'status_id'            => 'Completed',
    'medium_id'            => 'email',
    'email_information.email_provider' => 'Mailingwork',
  ]);
  if (empty($params['subject'])) {
    $params['subject'] = "\"{$params['email_information.mailing_subject']}\" - {$params['email_information.email']}";
  }
  $params = resolveFields($params);

  return civicrm_api3(
    'Activity',
    'create',
    $params
  );
}

function resolveFields($params) {
  foreach ($params as $key => $value) {
    if (strpos($key, '.') !== FALSE) {
      list($groupName, $fieldName) = explode('.', $key, 2);
      unset($params[$key]);
      $new_key = CRM_Core_BAO_CustomField::getCustomFieldID(
        $fieldName,
        $groupName,
        TRUE
      );
      $params[$new_key] = $value;
    }
  }
  return $params;
}