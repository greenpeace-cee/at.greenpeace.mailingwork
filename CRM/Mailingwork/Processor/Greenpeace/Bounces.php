<?php

use Civi\Api4\MailingworkMailing;

class CRM_Mailingwork_Processor_Greenpeace_Bounces extends CRM_Mailingwork_Processor_Base {

  /**
   * Required root properties that should be present in API responses
   *
   * @var array
   */
  protected $rootProperties = ['recipient', 'date', 'email', 'type'];

  /**
   * Fetch and process bounces
   *
   * @todo DRY up code (Bounces/Clicks/Openings/Recipients)
   *
   * @return array import results
   * @throws \Exception
   */
  public function import() {
    $import_results = [];
    $bounce_count = 0;
    if (empty($this->params['skip_mailing_sync'])) {
      // sync mailings first
      $this->importMailings();
    }
    if (empty($this->params['mailingwork_mailing_id'])) {
      // fetch all mailings where recipient sync has started and bounces are
      // pending or in progress
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'recipient_sync_status_id' => [
          'IN' => ['in_progress', 'completed'],
        ],
        'bounce_sync_status_id' => [
          'IN' => ['pending', 'in_progress', 'retrying'],
        ],
        'api.MailingworkMailing.getcampaign' => [],
        'options' => [
          'limit' => 0,
        ],
      ]);
    }
    else {
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'id'                                 => $this->params['mailingwork_mailing_id'],
        'api.MailingworkMailing.getcampaign' => [],
      ]);
    }
    foreach ($mailings['values'] as $mailing) {
      try {
        $type = CRM_Core_PseudoConstant::getName(
          'CRM_Mailingwork_BAO_MailingworkMailing',
          'type_id',
          $mailing['type_id']
        );
        $status = CRM_Core_PseudoConstant::getName(
          'CRM_Mailingwork_BAO_MailingworkMailing',
          'status_id',
          $mailing['status_id']
        );
        $sending_date = new DateTime($mailing['sending_date']);
        $result = $this->importBounces($mailing);
        $result['id'] = $mailing['id'];
        $import_results[] = $result;
        Civi::log()
          ->info("[Mailingwork/Bounces] Finished synchronization of mailing {$mailing['id']}/{$mailing['subject']}. Bounces: {$result['bounces']}, Activities: {$result['activities']}");
        if (!empty($result['date'])) {
          $last_bounce_date = new DateTime($result['date']);
          // add one second to the bounce_sync_date so we avoid re-fetching
          // the same bounces for large one-time mailings
          $last_bounce_date->add(new DateInterval('PT1S'));
          civicrm_api3('MailingworkMailing', 'create', [
            'id' => $mailing['id'],
            'bounce_sync_date' => $last_bounce_date->format('Y-m-d H:i:s'),
          ]);
        }

        // TODO: refactor to use isSyncCompleted()
        // standard/ab* mailings: sync fully completed 30 days after they've been sent
        if (
          ($type == 'standard' || $type == 'abtest' || $type == 'abwinner') && ($status == 'done' || $status == 'cancelled') &&
          $result['success'] && $sending_date->diff(new DateTime())->days > 30
        ) {
          civicrm_api3('MailingworkMailing', 'create', [
            'id' => $mailing['id'],
            'bounce_sync_status_id' => 'completed',
          ]);
        }

        if (!empty($result['bounces'])) {
          $bounce_count += $result['bounces'];
          if ($this->params['soft_limit'] > 0 && $bounce_count >= $this->params['soft_limit']) {
            break;
          }
        }
      }
      catch (Exception $e) {
        Civi::log()->error("[Mailingwork/Bounces] Synchronization of mailing {$mailing['id']}/{$mailing['subject']} failed. Error: {$e->getMessage()}");
        $syncStatus = CRM_Core_PseudoConstant::getName('CRM_Mailingwork_BAO_MailingworkMailing', 'bounce_sync_status_id', $mailing['bounce_sync_status_id']);
        MailingworkMailing::update(FALSE)
          ->addWhere('id', '=', $mailing['id'])
          ->addValue('bounce_sync_status_id:name', $syncStatus == 'retrying' ? 'failed' : 'retrying')
          ->execute();
      }
    }
    return $import_results;
  }

  private function importBounces(array $mailing) {
    $start_date = NULL;
    if (!empty($mailing['bounce_sync_date'])) {
      $start_date = $mailing['bounce_sync_date'];
    }
    Civi::log()->info("[Mailingwork/Bounces] Starting synchronization of mailing {$mailing['id']}/{$mailing['subject']}. Start Date: {$start_date}");
    $start = 0;
    $limit = 1000;
    $more_pages = TRUE;
    $last_bounce_date = NULL;
    $activity_count = 0;
    $bounce_count = 0;
    while ($more_pages) {
      $bounces = $this->client->api('bounce')
        ->getBouncesByEmailId(
          $mailing['mailingwork_identifier'],
          [
            'startDate' => $start_date,
            'start'     => $start,
            'limit'     => $limit,
          ]
        );
      $count = count($bounces);
      Civi::log()->info("[Mailingwork/Bounces] Fetched {$count} bounces of mailing {$mailing['id']}/{$mailing['subject']}");
      $start += $count;
      if ($count < $limit) {
        $more_pages = FALSE;
      }
      foreach ($bounces as $bounce) {
        $bounce_count++;
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
        $activity = $this->createActivity($contact_id, $bounce, $mailing);
        if (!is_null($activity)) {
          $last_bounce_date = $bounce['date'];
          $activity_count++;
        }
      }

      Civi::log()->info("[Mailingwork/Bounces] Processed {$count} bounces of mailing {$mailing['id']}/{$mailing['subject']}. Activity count: {$activity_count}");

      if (!empty($last_bounce_date)) {
        civicrm_api3('MailingworkMailing', 'create', [
          'id'                    => $mailing['id'],
          'bounce_sync_status_id' => 'in_progress',
          'bounce_sync_date'      => $last_bounce_date,
        ]);
      }
    }

    return [
      'success'    => TRUE,
      'activities' => $activity_count,
      'bounces'    => $bounce_count,
      'date'       => $last_bounce_date,
    ];
  }

  /**
   * Create a Bounce activity
   *
   * @param int $contact_id
   * @param $bounceData
   * @param $mailing
   *
   * @return array Activity
   * @throws \CiviCRM_API3_Exception
   */
  protected function createActivity($contact_id, $bounceData, $mailing) {
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

    $bounce_type = 'Softbounce';
    if ($bounceData['type'] == 'hard') {
      $bounce_type = 'Hardbounce';
    }

    if (!in_array($bounceData['type'], ['hard', 'soft'])) {
      Civi::log()->warning('[Mailingwork/Bounces] Unknown bounce type "' . $bounceData['type'] . '", assuming Softbounce.');
    }

    $params = [
      'target_id'           => $contact_id,
      'activity_type_id'    => 'Bounce',
      'medium_id'           => 'email',
      'status_id'           => 'Completed',
      'subject'             => "{$bounce_type} - {$bounceData[self::EMAIL_FIELD]}",
      'activity_date_time'  => $bounceData['date'],
      $email_field          => $bounceData[self::EMAIL_FIELD],
      $bounce_type_field    => $bounce_type,
      $email_provider_field => self::PROVIDER_NAME,
      'campaign_id'         => $mailing['api.MailingworkMailing.getcampaign']['values']['id'],
    ];

    $parent = $this->getParentActivity($contact_id, $bounceData, $mailing);
    if (!is_null($parent)) {
      $params[$parent_field] = $parent['activity_id'];
    }

    $dupes = civicrm_api3(
      'Activity',
      'getcount',
      $params
    );
    if ($dupes > 0) {
      Civi::log()->warning('[Mailingwork/Bounces] Ignoring bounce with existing activity');
      return NULL;
    }

    $activity = civicrm_api3(
      'Activity',
      'create',
      $params
    );

    $activityContactId = civicrm_api3('ActivityContact', 'getvalue', [
      'return'         => 'id',
      'activity_id'    => $activity['id'],
      'contact_id'     => $contact_id,
      'record_type_id' => 'Activity Targets',
    ]);

    civicrm_api3('ActivityContactEmail', 'create', [
      'activity_contact_id' => $activityContactId,
      'email'               => $bounceData[self::EMAIL_FIELD],
    ]);

    return $activity;
  }

}
