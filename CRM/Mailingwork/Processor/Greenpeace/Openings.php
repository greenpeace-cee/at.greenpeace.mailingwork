<?php

/**
 * Warning: WIP!
 *
 * @todo DRY up code (Bounces/Clicks/Openings/Recipients)
 *
 * Class CRM_Mailingwork_Processor_Greenpeace_Openings
 */
class CRM_Mailingwork_Processor_Greenpeace_Openings extends CRM_Mailingwork_Processor_Base {

  /**
   * Fetch and process openings
   *
   * @return array import results
   * @throws \Exception
   */
  public function import() {
    $this->preloadFields();
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
    $results = [];
    foreach ($mailings['values'] as $id => $mailing) {
      $result[$id] = $this->importMailingOpenings($mailing);
    }

    return $results;
  }

  /**
   * Fetch and process openings for a specific mailing
   *
   * @param $mailing
   *
   * @return array
   * @throws \Exception
   */
  private function importMailingOpenings($mailing) {
    $start_date = NULL;
    if (!empty($mailing['opening_sync_date'])) {
      $start_date = $mailing['opening_sync_date'];
    }
    $last_opening_date = $start_date;
    $start = 0;
    $limit = 1000;
    $more_pages = TRUE;
    $last_sending_date = NULL;
    $total_count = 0;
    try {
      while ($more_pages) {
        $openings = $this->client->api('opening')->getOpeningsByEmailId(
          $mailing['mailingwork_identifier'],
          [
            'startDate' => $start_date,
            'start'     => $start,
            'limit'     => $limit,
            'passDate'  => 1,
          ]
        );
        $count = count($openings);
        $start += $count;
        if ($count < $limit) {
          $more_pages = FALSE;
        }
        foreach ($openings as $opening) {
          $opening = $this->prepareRecipient($opening);
          $contact_id = $this->resolveContactId($opening);
          if (empty($contact_id)) {
            Civi::log()->info('[Mailingwork/Openings] Unable to identify contact: ' . $opening['Contact_ID']);
            continue;
          }
          if (empty($opening[self::EMAIL_FIELD])) {
            // we'd really like an email, but we can continue without if needed ...
            Civi::log()->warning('[Mailingwork/Openings] Unable to determine email for recipient ' . $opening['recipient']);
          }
          $activity = $this->createActivity($contact_id, $opening);
          if (!is_null($activity)) {
            $last_opening_date = $opening['date'];
            $total_count++;
          }
        }
      }
    }
    catch (Exception $e) {
      Civi::log()->error("[Mailingwork/Openings] Exception: {$e->getMessage()}", (array) $e);
      throw $e;
    } finally {
      if (!empty($last_opening_date)) {
        // TODO: update opening_sync_date
      }
    }

    return [
      'opening_count' => $total_count,
      'date'          => $last_opening_date,
    ];
  }

  /**
   * Get parent Online_Mailing activity for matching contact, email and mailing ID
   *
   * @param $contact_id
   * @param $openingData
   *
   * @return array|null
   * @throws \CiviCRM_API3_Exception
   */
  protected function getParentActivity($contact_id, $openingData) {
    $email_provider_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email_provider',
      'email_information'
    );
    $mailing_id_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'mailing_id',
      'email_information'
    );

    $email_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email',
      'email_information'
    );

    $result = civicrm_api3('Activity', 'get', [
      'activity_type_id'    => 'Online_Mailing',
      $email_field          => $openingData[self::EMAIL_FIELD],
      $email_provider_field => self::PROVIDER_NAME,
      $mailing_id_field     => $openingData['email'],
      'target_contact_id'   => $contact_id,
      'return'              => ['id', 'campaign_id', 'subject'],
    ]);

    if ($result['count'] > 1) {
      Civi::log()->warning('[Mailingwork/Openings] Ambiguous parent activity for recipient ' . $openingData['recipient']);
    }
    if ($result['count'] != 1) {
      return NULL;
    }
    // return first activity
    return reset($result['values']);
  }

  /**
   * Create an Opening activity
   *
   * @param int $contact_id
   * @param $openingData
   *
   * @todo define activity/custom field structure
   *
   * @return array Activity
   * @throws \CiviCRM_API3_Exception
   */
  protected function createActivity($contact_id, $openingData) {
    $parent_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'parent_activity_id',
      'activity_hierarchy'
    );
    $email_provider_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email_provider',
      'opening_information'
    );
    $email_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email',
      'opening_information'
    );

    $params = [
      'target_id'           => $contact_id,
      'activity_type_id'    => 'Opening',
      'medium_id'           => 'email',
      'status_id'           => 'Completed',
      'subject'             => 'Opening',
      $email_field          => $openingData[self::EMAIL_FIELD],
      $email_provider_field => self::PROVIDER_NAME,
    ];

    $parent = $this->getParentActivity($contact_id, $openingData);
    if (!is_null($parent)) {
      $params['campaign_id'] = $parent['campaign_id'];
      $params[$parent_field] = $parent['id'];
      $params['subject'] = "{$params['subject']} - {$parent['subject']}";
    }

    $dupes = civicrm_api3(
      'Activity',
      'getcount',
      $params
    );
    if ($dupes > 0) {
      // Activity already exists
      return NULL;
    }

    return civicrm_api3(
      'Activity',
      'create',
      $params
    );
  }

}
