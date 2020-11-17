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
   * Required root properties that should be present in API responses
   *
   * @var array
   */
  protected $rootProperties = ['recipient', 'date', 'email'];

  /**
   * Number of days after which completed standard mailings are considered fully
   * synchronized
   *
   * @var int
   */
  protected $standardSyncDays = 30;

  /**
   * @var int
   */
  protected $activityTypeId;

  /**
   * @var int
   */
  protected $activityStatusId;

  /**
   * @var int
   */
  protected $emailProviderId;

  /**
   * @var int
   */
  protected $targetRecordTypeId;

  /**
   * @var array
   */
  protected $optionValueCache = [];

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
    $this->preloadFields();
    $this->preloadPseudoConstants();
  }

  protected function preloadFields() {
    foreach ($this->client->api('field')->getFields() as $field) {
      $this->fields[$field->id] = $field->name;
    }
  }

  protected function preloadPseudoConstants() {
    $this->activityTypeId = CRM_Core_PseudoConstant::getKey(
      'CRM_Activity_BAO_Activity',
      'activity_type_id',
      'Online_Mailing'
    );
    $this->activityStatusId = CRM_Core_PseudoConstant::getKey(
      'CRM_Activity_BAO_Activity',
      'status_id',
      'Completed'
    );
    $this->targetRecordTypeId = CRM_Core_PseudoConstant::getKey(
      'CRM_Activity_BAO_ActivityContact',
      'record_type_id',
      'Activity Targets'
    );
    try {
      $this->emailProviderId = civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'email_provider',
        'name' => 'Mailingwork',
        'return' => 'value',
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      // TODO: setup custom fields at install/enable
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
    foreach ($this->rootProperties as $property) {
      if (!property_exists($item, $property)) {
        throw new CRM_Mailingwork_Processor_Exception(
          'Property "' . $property . '" not set'
        );
      }
      $recipient[$property] = $item->$property;
    }
    foreach ($item->fields as $field) {
      $fieldId = NULL;
      // inconsistent APIs are the best APIs
      if (property_exists($field, 'field')) {
        $fieldId = $field->field;
      }
      elseif (property_exists($field, 'id')) {
        $fieldId = $field->id;
      }
      else {
        throw new CRM_Mailingwork_Processor_Exception(
          'Expecting either a "field" or a "id" field, found neither'
        );
      }
      if (!array_key_exists($fieldId, $this->fields)) {
        // this field was probably deleted
        continue;
      }
      $property = $this->fields[$fieldId];
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

  /**
   * Import Mailingwork mailing meta data
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function importMailings() {
    return civicrm_api3('MailingworkMailing', 'import', [
      'username' => $this->params['username'],
      'password' => $this->params['password'],
    ]);
  }

  /**
   * Get Online_Mailing activity ID for matching contact, email and mailing
   *
   * @param $contact_id
   * @param $recipientData
   * @param $mailing
   *
   * @return array|null
   */
  protected function getParentActivity($contact_id, $recipientData, $mailing) {
    if (empty($recipientData[self::EMAIL_FIELD])) {
      Civi::log()->warning('Cannot determine parent activity, no email given.');
      return NULL;
    }

    $query = CRM_Core_DAO::executeQuery("
      SELECT
        a.id AS activity_id,
        ac.id AS activity_contact_id
      FROM
        civicrm_activity a
      JOIN
        civicrm_value_email_information e
      ON
        e.entity_id = a.id
      JOIN
        civicrm_activity_contact ac
      ON
        ac.activity_id = a.id AND record_type_id = %1
      JOIN
        civicrm_activity_contact_email ace
      ON
        ace.activity_contact_id = ac.id
      WHERE
        a.activity_type_id = %2 AND
        e.email_provider = %3 AND
        e.mailing_id = %4 AND
        ac.contact_id = %5 AND
        ace.email = %6
      ORDER BY
        a.activity_date_time DESC
      LIMIT 1",
      [
        1 => [$this->targetRecordTypeId, 'Integer'],
        2 => [$this->activityTypeId, 'Integer'],
        3 => [$this->emailProviderId, 'Integer'],
        4 => [$mailing['id'], 'String'],
        5 => [$contact_id, 'Integer'],
        6 => [$recipientData[self::EMAIL_FIELD], 'String'],
      ]
    );
    if (!$query->fetch()) {
      return NULL;
    }
    return [
      'activity_id' => $query->activity_id,
      'activity_contact_id' => $query->activity_contact_id
    ];
  }

  protected function isSyncCompleted($mailing) {
    $type = \CRM_Core_PseudoConstant::getName(
      'CRM_Mailingwork_BAO_MailingworkMailing',
      'type_id',
      $mailing['type_id']
    );
    $status = \CRM_Core_PseudoConstant::getName(
      'CRM_Mailingwork_BAO_MailingworkMailing',
      'status_id',
      $mailing['status_id']
    );
    $sending_date = new \DateTime($mailing['sending_date']);

    if (
      ($type == 'standard' || $type == 'abtest' || $type == 'abwinner') && ($status == 'done' || $status == 'cancelled') &&
      $sending_date->diff(new \DateTime())->days > $this->standardSyncDays
    ) {
      return TRUE;
    }

    return FALSE;
  }

  protected function getOrCreateOptionValue($optionGroupName, $optionValueName) {
    // check if OptionValue is in cache
    if (empty($this->optionValueCache[$optionGroupName][$optionValueName])) {
      try {
        // get existing OptionValue
        $this->optionValueCache[$optionGroupName][$optionValueName] = civicrm_api3(
          'OptionValue',
          'getvalue',
          [
            'return' => 'value',
            'option_group_id' => $optionGroupName,
            'name' => $optionValueName,
          ]
        );
      } catch (CiviCRM_API3_Exception $e) {
        // OptionValue doesn't exist yet, create it
        $this->optionValueCache[$optionGroupName][$optionValueName] = reset(
          civicrm_api3('OptionValue', 'create', [
            'option_group_id' => $optionGroupName,
            'name' => $optionValueName,
          ])['values']
        )['value'];
      }
    }
    return $this->optionValueCache[$optionGroupName][$optionValueName];
  }

}
