<?php

class CRM_Mailingwork_Processor_Greenpeace_Recipients extends CRM_Mailingwork_Processor_Base {
  private $activityTypeId = NULL;
  private $activityStatusId = NULL;
  private $emailProviderId = NULL;

  public function import() {
    $this->preloadFields();
    $import_results = [];
    if (empty($this->params['skip_mailing_sync'])) {
      // sync mailings first
      $this->importMailings();
    }
    if (empty($this->params['mailingwork_mailing_id'])) {
      // fetch all mailings that haven't been fully synced yet
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'recipient_sync_status_id' => ['IN' => ['pending', 'in_progress']],
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
      $type = CRM_Core_PseudoConstant::getName('CRM_Mailingwork_BAO_MailingworkMailing', 'type_id', $mailing['type_id']);
      $status = CRM_Core_PseudoConstant::getName('CRM_Mailingwork_BAO_MailingworkMailing', 'status_id', $mailing['status_id']);
      if ($status == 'drafted') {
        continue;
      }
      $sending_date = new DateTime($mailing['sending_date']);
      $result = $this->importRecipients($mailing);
      $result['id'] = $mailing['id'];
      $import_results[] = $result;

      if (!empty($result['date'])) {
        $last_sending_date = new DateTime($result['date']);
        // add one second to the recipient_sync_date so we avoid re-fetching
        // the same recipients for large one-time mailings
        $last_sending_date->add(new DateInterval('PT1S'));
        civicrm_api3('MailingworkMailing', 'create', [
          'id'                       => $mailing['id'],
          'recipient_sync_date'      => $last_sending_date->format('Y-m-d H:i:s'),
        ]);
      }

      // standard mailings: sync fully completed 30 days after they've been sent
      if (
        $type == 'standard' && ($status == 'done' || $status == 'cancelled') &&
        $result['success'] && $sending_date->diff(new DateTime())->days > 30
      ) {
        civicrm_api3('MailingworkMailing', 'create', [
          'id'                       => $mailing['id'],
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
    return $import_results;
  }

  private function importMailings() {
    return civicrm_api3('MailingworkMailing', 'import', [
      'username' => $this->params['username'],
      'password' => $this->params['password'],
    ]);
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
      $start += $count;
      if ($count < $limit) {
        $more_pages = FALSE;
      }
      foreach ($recipients as $recipient) {
        $recipient_count++;
        $recipient = $this->prepareRecipient($recipient);
        $contact_id = $this->resolveContactId($recipient);
        if (empty($contact_id)) {
          Civi::log()->info('[Mailingwork/Recipients] Unable to identify contact: ' . $recipient['Contact_ID']);
          // TODO: match by other criteria, e.g. email?
          continue;
        }
        if ($this->createActivity($contact_id, $recipient, $mailing)) {
          $last_sending_date = $recipient['date'];
          $activity_count++;
        };
      }

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
   * @return \CRM_Activity_BAO_Activity|null
   * @throws \CiviCRM_API3_Exception
   */
  protected function createActivity($contact_id, array $recipient, array $mailing) {
    $email_provider_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email_provider',
      'email_information'
    );
    $count_existing = civicrm_api3('Activity', 'getcount', [
      'target_contact_id'  => $contact_id,
      'activity_date_time'  => $recipient['date'],
      'activity_type_id'    => 'Online_Mailing',
      $email_provider_field => 'Mailingwork',
    ]);
    if ($count_existing > 0) {
      return NULL;
    }
    if (is_null($this->activityTypeId)) {
      $this->activityTypeId = CRM_Core_PseudoConstant::getKey(
        'CRM_Activity_BAO_Activity',
        'activity_type_id',
        'Online_Mailing'
      );
    }
    if (is_null($this->activityStatusId)) {
      $this->activityStatusId = CRM_Core_PseudoConstant::getKey(
        'CRM_Activity_BAO_Activity',
        'status_id',
        'Completed'
      );
    }
    if (is_null($this->emailProviderId)) {
      $this->emailProviderId = civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'email_provider',
        'name'            => 'Mailingwork',
        'return'          => 'value',
      ]);
    }
    $activity = new CRM_Activity_BAO_Activity();
    $activity->subject = trim($mailing['subject']);
    $campaign_id = $mailing['api.MailingworkMailing.getcampaign']['values']['id'];
    if (empty($campaign_id)) {
      Civi::log()->warning('[Mailingwork/Recipients] Unknown campaign for : ' . $recipient['Contact_ID']);
    }
    $activity->campaign_id = $campaign_id;
    $activity->activity_date_time = $recipient['date'];
    $activity->activity_type_id = $this->activityTypeId;
    $activity->status_id = $this->activityStatusId;
    $activity->save();

    $activity_contact = new CRM_Activity_BAO_ActivityContact();
    $activity_contact->contact_id = $contact_id;
    $activity_contact->activity_id = $activity->id;
    $activity_contact->record_type_id = 3;
    $activity_contact->save();

    // @TODO: Add "Added by" contact

    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_value_email_information
                        (entity_id, email, mailing_subject, mailing_description,
                         sender_name, mailing_type, email_provider, mailing_id)
                      VALUES
                        (%1, %2, %3, %4, %5, %6, %7, %8)",
      [
        1 => [$activity->id, 'Integer'],
        2 => [$recipient['email'], 'String'],
        3 => [trim($mailing['subject']), 'String'],
        4 => [trim($mailing['description']), 'String'],
        5 => [trim($mailing['sender_name']), 'String'],
        6 => [$mailing['type_id'], 'String'],
        7 => [$this->emailProviderId, 'String'],
        8 => [$mailing['id'], 'String'],
      ]
    );
    return $activity;
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
