<?php

use bconnect\MailingWork\Client;

class CRM_Mailingwork_Processor_Base {

  /**
   * @var bconnect\MailingWork\Client
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

  /**
   * CRM_Mailingwork_Processor_Base constructor.
   *
   * @param array $params import parameters
   * @param mixed $url Mailingwork API endpoint URL
   * @param null $handler Guzzle handler, useful for mocking
   */
  public function __construct(array $params, $url = FALSE, $handler = NULL) {
    $this->params = $params;
    $this->client = Client::getClient($params['username'], $params['password'], $url, $handler);
  }

  protected function preloadFields() {
    foreach ($this->client->api('field')->getFields() as $field) {
      $this->fields[$field->id] = $field->name;
    }
  }

  /**
   * @param $item
   *
   * @TODO: move to Recipients.php
   *
   * @return array
   * @throws \CRM_Mailingwork_Processor_Exception
   */
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
