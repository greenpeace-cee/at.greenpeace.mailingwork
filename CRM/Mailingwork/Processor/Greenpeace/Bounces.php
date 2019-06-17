<?php

class CRM_Mailingwork_Processor_Greenpeace_Bounces extends CRM_Mailingwork_Processor_Base {

  /**
   * Fetch and process bounces
   *
   * @todo DRY up code (Bounces/Clicks/Openings/Recipients)
   *
   * @return array import results
   * @throws \Exception
   */
  public function import() {
    $this->preloadFields();
    $start_date = Civi::settings()->get('mailingwork_bounce_sync_date');
    if (empty($start_date)) {
      $start_date = NULL;
    }
    $last_bounce_date = $start_date;
    $start = 0;
    $limit = 1000;
    $more_pages = TRUE;
    $last_sending_date = NULL;
    $total_count = 0;
    try {
      while ($more_pages) {
        $bounces = $this->client->api('bounce')->getBounces([
          'startDate' => $start_date,
          'start'     => $start,
          'limit'     => $limit,
        ]);
        $count = count($bounces);
        $start += $count;
        if ($count < $limit) {
          $more_pages = FALSE;
        }
        foreach ($bounces as $bounce) {
          $bounce = $this->prepareRecipient($bounce);
          $contact_id = $this->resolveContactId($bounce);
          if (empty($contact_id)) {
            Civi::log()->info('[Mailingwork/Bounces] Unable to identify contact: ' . $bounce['Contact_ID']);
            continue;
          }
          if (empty($bounce[self::EMAIL_FIELD])) {
            // we'd really like an email, but we can continue without if needed ...
            Civi::log()->warning('[Mailingwork/Bounces] Unable to determine email for recipient ' . $bounce['recipient']);
          }
          $activity = $this->createActivity($contact_id, $bounce);
          if (!is_null($activity)) {
            $last_bounce_date = $bounce['date'];
            $total_count++;
          }
        }
      }
    }
    catch (Exception $e) {
      Civi::log()->error("[Mailingwork/Bounces] Exception: {$e->getMessage()}", (array) $e);
      throw $e;
    } finally {
      if (!empty($last_bounce_date)) {
        Civi::settings()->set('mailingwork_bounce_sync_date', $last_bounce_date);
      }
    }

    return [
      'bounce_count' => $total_count,
      'date'         => $last_bounce_date,
    ];
  }

  /**
   * Get parent Online_Mailing activity for matching contact, email and mailing ID
   *
   * @param $contact_id
   * @param $bounceData
   *
   * @return array|null
   * @throws \CiviCRM_API3_Exception
   */
  protected function getParentActivity($contact_id, $bounceData) {
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
      $email_field          => $bounceData[self::EMAIL_FIELD],
      $email_provider_field => self::PROVIDER_NAME,
      $mailing_id_field     => $bounceData['email'],
      'target_contact_id'   => $contact_id,
      'return'              => ['id', 'campaign_id'],
    ]);

    if ($result['count'] > 1) {
      Civi::log()->warning('[Mailingwork/Bounces] Ambiguous parent activity for recipient ' . $bounceData['recipient']);
    }
    if ($result['count'] != 1) {
      return NULL;
    }
    // return first activity
    return reset($result['values']);
  }

  /**
   * Create a Bounce activity
   *
   * @param int $contact_id
   * @param $bounceData
   *
   * @return array Activity
   * @throws \CiviCRM_API3_Exception
   */
  protected function createActivity($contact_id, $bounceData) {
    $parent_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'parent_activity_id',
      'activity_hierarchy'
    );
    $bounce_type_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'bounce_type',
      'bounce_information'
    );
    $email_provider_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email_provider',
      'bounce_information'
    );
    $email_field = 'custom_' . CRM_Core_BAO_CustomField::getCustomFieldID(
      'email',
      'bounce_information'
    );

    $bounce_type = 'Hardbounce';
    if ($bounceData['type'] == 'soft') {
      $bounce_type = 'Softbounce';
    }

    $params = [
      'target_id'           => $contact_id,
      'activity_type_id'    => 'Bounce',
      'medium_id'           => 'email',
      'status_id'           => 'Completed',
      'subject'             => "{$bounce_type} - {$bounceData[self::EMAIL_FIELD]}",
      $email_field          => $bounceData[self::EMAIL_FIELD],
      $bounce_type_field    => $bounce_type,
      $email_provider_field => self::PROVIDER_NAME,
    ];

    $parent = $this->getParentActivity($contact_id, $bounceData);
    if (!is_null($parent)) {
      $params['campaign_id'] = $parent['campaign_id'];
      $params[$parent_field] = $parent['id'];
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
