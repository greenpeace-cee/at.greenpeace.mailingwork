<?php

class CRM_Mailingwork_Processor_Greenpeace_Mailings extends CRM_Mailingwork_Processor_Base {

  public function import() {
    $this->preloadFields();
    $this->importFolders();
    $count = 0;
    $countExisting = 0;
    foreach ($this->client->api('mailing')->getMailings() as $mailing) {
      $mailingDetails = $this->client->api('mailing')->getEmailById($mailing->id);
      $data = [
        'mailingwork_identifier' => $mailing->id,
        'subject'                => $mailing->subject,
        'description'            => $mailing->description,
        'sender_name'            => $mailingDetails->senderName,
        'sender_email'           => $mailingDetails->senderEmail,
        'mailingwork_folder_id'  => $this->getFolder($mailingDetails->folderId),
        'sending_date'           => $mailing->sendingTime,
        'status_id'              => $mailing->status,
        'type_id'                => $mailing->type,
      ];

      $existing_mailing = civicrm_api3('MailingworkMailing', 'get', [
        'return'                 => ['id'],
        'mailingwork_identifier' => $mailing->id,
      ]);

      if ($existing_mailing['count'] > 0) {
        $data['id'] = $existing_mailing['id'];
        $countExisting++;
      }

      civicrm_api3('MailingworkMailing', 'create', $data);
      $count++;
    }

    return [
      'count'    => $count,
      'existing' => $countExisting,
      'new'      => $count - $countExisting,
    ];
  }

  private function importFolders() {
    return civicrm_api3('MailingworkFolder', 'import', [
      'username' => $this->params['username'],
      'password' => $this->params['password'],
    ]);
  }

  private function getFolder($identifier) {
    $result = civicrm_api3('MailingworkFolder', 'get', [
      'return'                 => ['id'],
      'mailingwork_identifier' => $identifier,
    ]);
    if ($result['count'] > 0) {
      return $result['id'];
    }
    return NULL;
  }

}
