<?php

class CRM_Mailingwork_Processor_Greenpeace_Mailings extends CRM_Mailingwork_Processor_Base {
  public function import() {
    foreach ($this->client->api('mailing')->getMailings() as $mailing) {
      $mailing_id = $mailing->id;
      $mailingDetails = $this->client->api('mailing')->getEmailById($mailing->id);
      $data = [
        'mailingwork_identifier' => $mailing->id,
        'subject'                => $mailing->subject,
        'description'            => $mailing->description,
        'sender_name'            => $mailingDetails->senderName,
        'sender_email'           => $mailingDetails->senderEmail,
        'folder_id'              => $mailingDetails->folderId,
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
      }
      civicrm_api3('MailingworkMailing', 'create', $data);
    }
  }

}
