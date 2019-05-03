<?php

use bconnect\MailingWork\Client;

class CRM_Mailingwork_Processor_Base {

  /**
   * @var Client
   */
  protected $client;

  /**
   * @var array
   */
  protected $params;

  /**
   * @var array
   */
  protected $fields = [];

  public function __construct(array $params) {
    $this->params = $params;
    $this->client = Client::getClient($params['username'], $params['password']);
    $this->preloadFields();
    foreach ($this->client->api('mailing')->getMailings(NULL, NULL, '2017-06-01') as $mailing) {
      if ($mailing->status == 'drafted') {
        continue;
      }
      if ($mailing->id != 1067) {
        continue;
      }
      $mailing_id = $mailing->id;
      $mailing = $this->client->api('mailing')->getEmailById($mailing->id);
      $start = 0;
      $limit = 5;
      $more_pages = TRUE;
      /*$recipients = $this->client->api('recipient')->getRecipientsByEmailId($mailing_id, NULL, NULL, $start, $limit);
      foreach ($recipients as $recipient) {
        var_dump($this->prepareRecipient($recipient));
      }*/
    }
  }

  private function preloadFields() {
    foreach ($this->client->api('field')->getFields() as $field) {
      $this->fields[$field->id] = $field->name;
    }
  }

  // @TODO: move to Recipients.php
  protected function prepareRecipient($item) {
    $recipient = [];
    $root_properties = ['recipient', 'date', 'email'];
    foreach ($root_properties as $property) {
      if (!property_exists($item, $property)) {
        throw new CRM_Mailingwork_Processor_Exception(
          'Property "' . $property . '" not set'
        );
      }
      $recipient[$property] = $item->$property;
    }
    foreach ($item->fields as $field) {
      if (!array_key_exists($field->field, $this->fields)) {
        // this field was probably deleted
        continue;
      }
      $property = $this->fields[$field->field];
      // don't overwrite root properties with same name
      if (!array_key_exists($property, $recipient)) {
        $recipient[$property] = $field->value;
      }
    }
    return $recipient;
  }

}
