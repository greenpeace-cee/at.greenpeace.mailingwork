<?php

class CRM_Mailingwork_Processor_Greenpeace_Recipients extends CRM_Mailingwork_Processor_Base {

  public function import() {
    $import_results = [];
    if (empty($this->params['skip_mailing_sync'])) {
      // sync mailings first
      $this->importMailings();
    }
    if (empty($this->params['mailingwork_mailing_id'])) {
      // fetch all mailings that haven't been fully synced yet
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'recipient_sync_status_id' => ['IN' => ['pending', 'in_progress']],
        'options'                  => ['limit' => 0],
      ]);
    }
    else {
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'id' => $this->params['mailingwork_mailing_id'],
      ]);
    }
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
        civicrm_api3('MailingworkMailing', 'create', [
          'id'                       => $mailing['id'],
          'recipient_sync_status_id' => 'in_progress',
          'recipient_sync_date'      => $result['date'],
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
    $total_count = 0;
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
        $recipient = $this->prepareRecipient($recipient);
        $last_sending_date = $recipient['date'];
        $contact_id = $this->resolveContactId($recipient);
        if (empty($contact_id)) {
          Civi::log()->info('Unable to identify contact: ' . $recipient['Contact_ID']);
          // TODO: match by other criteria, e.g. email?
          continue;
        }
        $this->createActivity($contact_id, $recipient, $mailing);

        $last_sending_date = $recipient->date;
        $total_count++;
      }
    }
    // TODO: process 1K per iteration, wrapped in transaction, then update recipient_sync_date
    // if error occurs, rollback and return with success = false
    return [
      'success' => TRUE,
      'count'   => $total_count,
      'date'    => $last_sending_date,
    ];
  }

  protected function resolveContactId($recipient) {
    if (empty($recipient['Contact_ID'])) {
      return NULL;
    }
    $query = CRM_Core_DAO::executeQuery(CRM_Identitytracker_Configuration::getSearchSQL(), [
      1 => ['internal', 'String'],
      2 => [$recipient['Contact_ID'], 'String'],
    ]);
    if (!$query->fetch()) {
      return NULL;
    };
    return $query->entity_id;
  }

  protected function createActivity($contact_id, array $recipient, array $mailing) {
    $activity = new CRM_Activity_BAO_Activity();
    $activity->subject = trim($mailing['subject']);
    $activity->activity_date_time = $recipient['date'];
    $activity->activity_type_id = 101; // TODO: fetch via DB
    return $activity->save();
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
