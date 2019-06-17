<?php

use bconnect\MailingWork\Client;

class CRM_Mailingwork_Processor_Base {

  /**
   * Provider name for activity custom field
   *
   * @var string
   */
  const PROVIDER_NAME = 'Mailingwork';

  /**
   * Name of the field containing the recipient's email
   *
   * Used for APIs that don't return root-level email field (e.g. GetBounces)
   *
   * @var string
   */
  const EMAIL_FIELD = 'E-Mail';

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

  /**
   * Resolve to current contact_id using de.systopia.identitytracker
   *
   * We bypass the API and use identitytracker internals for performance reasons
   * This may break when identitytracker is updated
   *
   * @param $recipient
   *
   * @return int|null
   */
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

}
