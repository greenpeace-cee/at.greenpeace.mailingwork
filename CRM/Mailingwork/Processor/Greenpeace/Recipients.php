<?php

use Civi\Api4\MailingworkMailing;

class CRM_Mailingwork_Processor_Greenpeace_Recipients extends CRM_Mailingwork_Processor_Base {
  private $sourceRecordTypeId = NULL;
  private $sourceContactId = NULL;
  private $emailMediumId = NULL;
  private $activityCache = [];

  public function import() {
    $import_results = [];
    if (empty($this->params['skip_mailing_sync'])) {
      // sync mailings first
      $this->importMailings();
    }
    if (empty($this->params['mailingwork_mailing_id'])) {
      // fetch all mailings that haven't been fully synced yet
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'recipient_sync_status_id' => ['IN' => ['pending', 'in_progress', 'retrying']],
        'api.MailingworkMailing.getcampaign' => [],
        'options'                  => ['limit' => 0],
      ]);
    }
    else {
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'id' => $this->params['mailingwork_mailing_id'],
        'api.MailingworkMailing.getcampaign' => [],
      ]);
    }
    $recipient_count = 0;
    foreach ($mailings['values'] as $mailing) {
      try {
        $type = CRM_Core_PseudoConstant::getName('CRM_Mailingwork_BAO_MailingworkMailing', 'type_id', $mailing['type_id']);
        $status = CRM_Core_PseudoConstant::getName('CRM_Mailingwork_BAO_MailingworkMailing', 'status_id', $mailing['status_id']);
        if ($status == 'drafted') {
          continue;
        }
        Civi::log()
          ->info("[Mailingwork/Recipients] Starting synchronization of mailing {$mailing['id']}/{$mailing['subject']}. Start Date: {$mailing['recipient_sync_date']}");
        $sending_date = new DateTime($mailing['sending_date']);
        $result = $this->importRecipients($mailing);
        // we're caching activities per mailing, so flush once done with one
        $this->activityCache = [];
        $result['id'] = $mailing['id'];
        Civi::log()
          ->info("[Mailingwork/Recipients] Finished synchronization of mailing {$mailing['id']}/{$mailing['subject']}. Recipients: {$result['recipients']}, Activities: {$result['activities']}");
        $import_results[] = $result;

        if (!empty($result['date'])) {
          $last_sending_date = new DateTime($result['date']);
          // add one second to the recipient_sync_date so we avoid re-fetching
          // the same recipients for large one-time mailings
          $last_sending_date->add(new DateInterval('PT1S'));
          civicrm_api3('MailingworkMailing', 'create', [
            'id' => $mailing['id'],
            'recipient_sync_date' => $last_sending_date->format('Y-m-d H:i:s'),
          ]);
        }

        // standard/ab* mailings: sync fully completed 30 days after they've been sent
        if (
          ($type == 'standard' || $type == 'abtest' || $type == 'abwinner') && ($status == 'done' || $status == 'cancelled') &&
          $result['success'] && $sending_date->diff(new DateTime())->days > 30
        ) {
          civicrm_api3('MailingworkMailing', 'create', [
            'id' => $mailing['id'],
            'recipient_sync_status_id' => 'completed',
          ]);
        }

        if (!empty($result['recipients'])) {
          $recipient_count += $result['recipients'];
          if ($this->params['soft_limit'] > 0 && $recipient_count >= $this->params['soft_limit']) {
            break;
          }
        }
      }
      catch (Exception $e) {
        Civi::log()->error("[Mailingwork/Recipients] Synchronization of mailing {$mailing['id']}/{$mailing['subject']} failed. Error: {$e->getMessage()}");
        $syncStatus = CRM_Core_PseudoConstant::getName('CRM_Mailingwork_BAO_MailingworkMailing', 'recipient_sync_status_id', $mailing['recipient_sync_status_id']);
        MailingworkMailing::update(FALSE)
          ->addWhere('id', '=', $mailing['id'])
          ->addValue('recipient_sync_status_id:name', $syncStatus == 'retrying' ? 'failed' : 'retrying')
          ->execute();
      }
    }
    return $import_results;
  }

  private function importRecipients(array $mailing) {
    $start_date = NULL;
    if (!empty($mailing['recipient_sync_date'])) {
      $start_date = $mailing['recipient_sync_date'];
    }
    $start = 0;
    $limit = 1000;
    $more_pages = TRUE;
    $last_sending_date = NULL;
    $activity_count = 0;
    $recipient_count = 0;
    while ($more_pages) {
      $recipients = $this->client->api('recipient')
        ->getRecipientsByEmailId(
          $mailing['mailingwork_identifier'],
          $start_date,
          NULL,
          $start,
          $limit
        );
      $count = count($recipients);
      Civi::log()->info("[Mailingwork/Recipients] Fetched {$count} recipients of mailing {$mailing['id']}/{$mailing['subject']}");
      $start += $count;
      if ($count < $limit) {
        $more_pages = FALSE;
      }
      foreach ($recipients as $recipient) {
        $recipient_count++;
        $recipient = $this->prepareRecipient($recipient);
        $contact_id = $this->resolveContactId($recipient);
        $last_sending_date = $recipient['date'];
        if (empty($contact_id)) {
          if (!empty($recipient['Contact_ID'])) {
            Civi::log()->info('[Mailingwork/Recipients] Unable to identify contact: ' . $recipient['Contact_ID']);
          }
          // TODO: match by other criteria, e.g. email?
          continue;
        }
        if ($this->createActivity($contact_id, $recipient, $mailing)) {
          $activity_count++;
        };
      }

      Civi::log()->info("[Mailingwork/Recipients] Processed {$count} recipients of mailing {$mailing['id']}/{$mailing['subject']}. Activity count: {$activity_count}");

      if (!empty($last_sending_date)) {
        civicrm_api3('MailingworkMailing', 'create', [
          'id'                       => $mailing['id'],
          'recipient_sync_status_id' => 'in_progress',
          'recipient_sync_date'      => $last_sending_date,
        ]);
      }
    }

    return [
      'success' => TRUE,
      'activities' => $activity_count,
      'recipients' => $recipient_count,
      'date'    => $last_sending_date,
    ];
  }

  /**
   * Create a Online_Mailing activity and add the contact
   *
   * @param $contact_id
   * @param array $recipient
   * @param array $mailing
   *
   * @return int|null
   * @throws \CiviCRM_API3_Exception
   */
  protected function createActivity($contact_id, array $recipient, array $mailing) {
    if (is_null($this->sourceRecordTypeId)) {
      $this->sourceRecordTypeId = CRM_Core_PseudoConstant::getKey(
        'CRM_Activity_BAO_ActivityContact',
        'record_type_id',
        'Activity Source'
      );
    }

    if (is_null($this->emailMediumId)) {
      $this->emailMediumId = CRM_Core_PseudoConstant::getKey(
        'CRM_Activity_BAO_Activity',
        'medium_id',
        'email'
      );
    }

    if (is_null($this->sourceContactId)) {
      $session = CRM_Core_Session::singleton();
      $this->sourceContactId = $session->get('userID');
      if (empty($this->sourceContactId)) {
        // @TODO: better fallback?
        $this->sourceContactId = 1;
      }
    }

    $count_existing = CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) AS countExisting
      FROM civicrm_activity a
      INNER JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = %6
      INNER JOIN civicrm_value_email_information e on e.entity_id = a.id
      WHERE a.activity_date_time = %1
        AND a.activity_type_id = %2
        AND e.email_provider = %3
        AND e.mailing_id = %4
        AND ac.contact_id = %5",
      [
        1 => [$recipient['date'], 'String'],
        2 => [$this->activityTypeId, 'Integer'],
        3 => [$this->emailProviderId, 'Integer'],
        4 => [$mailing['id'], 'String'],
        5 => [$contact_id, 'Integer'],
        6 => [$this->targetRecordTypeId, 'Integer'],
      ]
    );
    if ($count_existing > 0) {
      return NULL;
    }

    $activity_id = NULL;
    if (Civi::settings()->get('mailingwork_use_mass_activities')) {
      if (empty($this->activityCache[$mailing['id']][$recipient['date']])) {
        $activity_id = CRM_Core_DAO::singleValueQuery(
          "SELECT a.id
          FROM civicrm_activity a
          INNER JOIN civicrm_value_email_information e on e.entity_id = a.id
          WHERE a.activity_date_time = %1
            AND a.activity_type_id = %2
            AND e.email_provider = %3
            AND e.mailing_id = %4
          LIMIT 1",
          [
            1 => [$recipient['date'], 'String'],
            2 => [$this->activityTypeId, 'Integer'],
            3 => [$this->emailProviderId, 'Integer'],
            4 => [$mailing['id'], 'String'],
          ]
        );
      }
      else {
        $activity_id = $this->activityCache[$mailing['id']][$recipient['date']];
      }
    }
    if (empty($activity_id)) {
      $activity = new CRM_Activity_BAO_Activity();
      $activity->subject = trim($mailing['subject']);
      $campaign_id = $mailing['api.MailingworkMailing.getcampaign']['values']['id'];
      if (empty($campaign_id)) {
        Civi::log()->warning('[Mailingwork/Recipients] Unknown campaign for: ' . $recipient['Contact_ID']);
      }
      $activity->campaign_id = $campaign_id;
      $activity->activity_date_time = $recipient['date'];
      $activity->activity_type_id = $this->activityTypeId;
      $activity->status_id = $this->activityStatusId;
      $activity->medium_id = $this->emailMediumId;
      $activity->save();
      $activity_id = $activity->id;

      $activity_contact = new CRM_Activity_BAO_ActivityContact();
      $activity_contact->contact_id = $this->sourceContactId;
      $activity_contact->activity_id = $activity_id;
      $activity_contact->record_type_id = $this->sourceRecordTypeId;
      $activity_contact->save();

      CRM_Core_DAO::executeQuery(
        "INSERT INTO civicrm_value_email_information
                        (entity_id, email, mailing_subject, mailing_description,
                         sender_name, mailing_type, email_provider, mailing_id)
                      VALUES
                        (%1, %2, %3, %4, %5, %6, %7, %8)",
        [
          1 => [$activity->id, 'Integer'],
          2 => [Civi::settings()->get('mailingwork_use_mass_activities') ? '' : $recipient['email'], 'String'],
          3 => [trim($mailing['subject']), 'String'],
          4 => [trim($mailing['description']), 'String'],
          5 => [trim($mailing['sender_name']), 'String'],
          6 => [$mailing['type_id'], 'String'],
          7 => [$this->emailProviderId, 'String'],
          8 => [$mailing['id'], 'String'],
        ]
      );
    }

    $this->activityCache[$mailing['id']][$recipient['date']] = $activity_id;

    $activity_contact = new CRM_Activity_BAO_ActivityContact();
    $activity_contact->contact_id = $contact_id;
    $activity_contact->activity_id = $activity_id;
    $activity_contact->record_type_id = 3;
    $activity_contact->save();

    $activity_contact_email = new CRM_Mailingwork_BAO_ActivityContactEmail();
    $activity_contact_email->activity_contact_id = $activity_contact->id;
    $activity_contact_email->email = $recipient['email'];
    $activity_contact_email->save();

    return $activity_id;
  }

  protected function prepareRecipient($item) {
    $item = parent::prepareRecipient($item);
    $requiredProperties = ['Contact_ID', 'email', 'date'];
    foreach ($requiredProperties as $property) {
      if (!array_key_exists($property, $item)) {
        throw new CRM_Mailingwork_Processor_Exception(
          'Property "' . $property . '" not set'
        );
      }
    }
    return $item;
  }

}
