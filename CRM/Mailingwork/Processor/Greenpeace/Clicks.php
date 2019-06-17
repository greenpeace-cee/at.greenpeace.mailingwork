<?php

/**
 * Warning: WIP!
 *
 * @todo DRY up code (Bounces/Clicks/Openings/Recipients)
 *
 * Class CRM_Mailingwork_Processor_Greenpeace_Clicks
 */
class CRM_Mailingwork_Processor_Greenpeace_Clicks extends CRM_Mailingwork_Processor_Base {

  /**
   * Fetch and process clicks
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
      $result[$id] = $this->importMailingClicks($mailing);
    }

    return $results;
  }

  /**
   * Fetch and process clicks for a specific mailing
   *
   * @param $mailing
   *
   * @return array
   * @throws \Exception
   */
  private function importMailingClicks($mailing) {
    $start_date = NULL;
    if (!empty($mailing['click_sync_date'])) {
      $start_date = $mailing['click_sync_date'];
    }
    $last_click_date = $start_date;
    $start = 0;
    $limit = 1000;
    $more_pages = TRUE;
    $last_sending_date = NULL;
    $total_count = 0;
    try {
      while ($more_pages) {
        $clicks = $this->client->api('click')->getClicksByEmailId(
          $mailing['mailingwork_identifier'],
          [
            'startDate' => $start_date,
            'start'     => $start,
            'limit'     => $limit,
            'passDate'  => 1,
          ]
        );
        $count = count($clicks);
        $start += $count;
        if ($count < $limit) {
          $more_pages = FALSE;
        }
        foreach ($clicks as $click) {
          $click = $this->prepareRecipient($click);
          $contact_id = $this->resolveContactId($click);
          if (empty($contact_id)) {
            Civi::log()->info('[Mailingwork/Clicks] Unable to identify contact: ' . $click['Contact_ID']);
            continue;
          }
          if (empty($click[self::EMAIL_FIELD])) {
            // we'd really like an email, but we can continue without if needed ...
            Civi::log()->warning('[Mailingwork/Clicks] Unable to determine email for recipient ' . $click['recipient']);
          }
          $activity = $this->createActivity($contact_id, $click);
          if (!is_null($activity)) {
            $last_click_date = $click['date'];
            $total_count++;
          }
        }
      }
    }
    catch (Exception $e) {
      Civi::log()->error("[Mailingwork/Clicks] Exception: {$e->getMessage()}", (array) $e);
      throw $e;
    } finally {
      if (!empty($last_click_date)) {
        // TODO: update click_sync_date
      }
    }

    return [
      'click_count' => $total_count,
      'date'          => $last_click_date,
    ];
  }

  /**
   * Get parent Online_Mailing activity for matching contact, email and mailing ID
   *
   * @param $contact_id
   * @param $clickData
   *
   * @return array|null
   * @throws \CiviCRM_API3_Exception
   */
  protected function getParentActivity($contact_id, $clickData) {
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
      $email_field          => $clickData[self::EMAIL_FIELD],
      $email_provider_field => self::PROVIDER_NAME,
      $mailing_id_field     => $clickData['email'],
      'target_contact_id'   => $contact_id,
      'return'              => ['id', 'campaign_id', 'subject'],
    ]);

    if ($result['count'] > 1) {
      Civi::log()->warning('[Mailingwork/Clicks] Ambiguous parent activity for recipient ' . $clickData['recipient']);
    }
    if ($result['count'] != 1) {
      return NULL;
    }
    // return first activity
    return reset($result['values']);
  }

  /**
   * Create an Click activity
   *
   * @param int $contact_id
   * @param $clickData
   *
   * @todo define activity/custom field structure
   *
   * @return array Activity
   * @throws \CiviCRM_API3_Exception
   */
  protected function createActivity($contact_id, $clickData) {
    $parent_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'parent_activity_id',
      'activity_hierarchy'
    );
    $email_provider_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email_provider',
      'click_information'
    );
    $email_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email',
      'click_information'
    );

    $params = [
      'target_id'           => $contact_id,
      'activity_type_id'    => 'Click',
      'medium_id'           => 'email',
      'status_id'           => 'Completed',
      'subject'             => 'Click',
      $email_field          => $clickData[self::EMAIL_FIELD],
      $email_provider_field => self::PROVIDER_NAME,
    ];

    $parent = $this->getParentActivity($contact_id, $clickData);
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
