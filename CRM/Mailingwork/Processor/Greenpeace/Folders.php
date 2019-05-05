<?php

class CRM_Mailingwork_Processor_Greenpeace_Folders extends CRM_Mailingwork_Processor_Base {
  public function import() {
    // array with PK of civicrm_mailingwork_folder => parent ID mailingwork_identifier
    $hierarchy = [];
    $count = 0;
    // first iteration: create/update all folders
    foreach ($this->client->api('folder')->getFolders() as $folder) {
      $data = [
        'mailingwork_identifier' => $folder->id,
        'name'                   => $folder->name
      ];
      $existing_folder = civicrm_api3('MailingworkFolder', 'get', [
        'return'                 => ['id'],
        'mailingwork_identifier' => $folder->id,
      ]);
      if ($existing_folder['count'] > 0) {
        $data['id'] = $existing_folder['id'];
      }
      $result = civicrm_api3('MailingworkFolder', 'create', $data);
      $hierarchy[$result['id']] = $folder->parent_id;
      $count++;
    }
    // second iteration: set parents
    foreach ($hierarchy as $id => $parent_identifier) {
      $parent_id = NULL;
      if (!empty($parent_identifier) && $parent_identifier != 0) {
        $parent_id = civicrm_api3('MailingworkFolder', 'getvalue', [
          'return' => 'id',
          'mailingwork_identifier' => $parent_identifier,
        ]);
      }
      civicrm_api3('MailingworkFolder', 'create', [
        'id'        => $id,
        'parent_id' => $parent_id,
      ]);
    }
    return ['count' => $count];
  }

}
