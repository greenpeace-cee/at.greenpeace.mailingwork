<?php

use Civi\Api4\MailingworkMailing;
use Civi\Api4\MailingworkOpening;

/**
 * Warning: WIP!
 *
 * @todo DRY up code (Bounces/Clicks/Openings/Recipients)
 *
 * Class CRM_Mailingwork_Processor_Greenpeace_Openings
 */
class CRM_Mailingwork_Processor_Greenpeace_Openings extends CRM_Mailingwork_Processor_Base {
  /**
   * Required root properties that should be present in API responses
   *
   * @var array
   */
  protected $rootProperties = ['recipient', 'date', 'userAgentType', 'userAgent'];

  protected $standardSyncDays = 90;

  /**
   * Fetch and process openings
   *
   * @return array import results
   * @throws \Exception
   */
  public function import() {
    if (empty($this->params['skip_mailing_sync'])) {
      // sync mailings first
      $this->importMailings();
    }
    if (empty($this->params['mailingwork_mailing_id'])) {
      // fetch all mailings where recipients have started or finished syncing
      // and opening sync hasn't finished yet. we need recipients to already be
      // imported as we attach openings to these activities.
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'recipient_sync_status_id' => ['IN' => ['in_progress', 'completed']],
        'opening_sync_status_id'   => ['IN' => ['pending', 'in_progress', 'retrying']],
        'options'                  => ['limit' => 0],
      ]);
    }
    else {
      $mailings = civicrm_api3('MailingworkMailing', 'get', [
        'id' => $this->params['mailingwork_mailing_id'],
      ]);
    }
    $results = [];
    $opening_count = 0;
    foreach ($mailings['values'] as $id => $mailing) {
      try {
        Civi::log()
          ->info("[Mailingwork/Openings] Starting synchronization of mailing {$mailing['id']}/{$mailing['subject']}. Start Date: {$mailing['opening_sync_date']}");
        $results[$id] = $this->importMailingOpenings($mailing);
        if ($results[$id]['success'] && $this->isSyncCompleted($mailing)) {
          civicrm_api3('MailingworkMailing', 'create', [
            'id' => $mailing['id'],
            'opening_sync_status_id' => 'completed',
          ]);
        }
        Civi::log()
          ->info("[Mailingwork/Openings] Finished synchronization of mailing {$mailing['id']}/{$mailing['subject']}. Openings: {$results[$id]['openings']}, Imported: {$results[$id]['imported_openings']}");
        if (!empty($results[$id]['openings'])) {
          $opening_count += $results[$id]['openings'];
          if ($this->params['soft_limit'] > 0 && $opening_count >= $this->params['soft_limit']) {
            break;
          }
        }
      }
      catch (Exception $e) {
        Civi::log()->error("[Mailingwork/Openings] Synchronization of mailing {$mailing['id']}/{$mailing['subject']} failed. Error: {$e->getMessage()}");
        $syncStatus = CRM_Core_PseudoConstant::getName('CRM_Mailingwork_BAO_MailingworkMailing', 'opening_sync_status_id', $mailing['opening_sync_status_id']);
        MailingworkMailing::update(FALSE)
          ->addWhere('id', '=', $mailing['id'])
          ->addValue('opening_sync_status_id:name', $syncStatus == 'retrying' ? 'failed' : 'retrying')
          ->execute();
      }
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
    $total_count = 0;
    $stored_count = 0;
    try {
      $fields = array_search(self::EMAIL_FIELD, $this->fields) . ',' .
        array_search('Contact_ID', $this->fields);
      $openings = $this->client->api('opening')->getOpeningsByEmailId(
        $mailing['mailingwork_identifier'],
        [
          // perf hack: only request the email and contact_id fields
          'fieldId'       => $fields,
          'startDate'     => $start_date,
          // request the opening date
          'passDate'      => 1,
          'userAgentType' => 1,
          'userAgent'     => 1,
        ]
      );
      $total_count = count($openings);
      foreach ($openings as $opening) {
        $opening = $this->prepareRecipient($opening);
        $contact_id = $this->resolveContactId($opening);
        if (empty($contact_id)) {
          if (!empty($recipient['Contact_ID'])) {
            Civi::log()->info('[Mailingwork/Openings] Unable to identify contact: ' . $opening['Contact_ID']);
          }
          continue;
        }
        $parent_activity = $this->getParentActivity(
          $contact_id,
          $opening,
          $mailing
        );
        if (is_null($parent_activity)) {
          Civi::log()->warning('[Mailingwork/Openings] Ignoring opening without matching activity');
          continue;
        }
        $dupeOpening = MailingworkOpening::get(FALSE)
          ->addSelect('id')
          ->addWhere('activity_contact_id', '=', $parent_activity['activity_contact_id'])
          ->addWhere('opening_date', '=', $opening['date'])
          ->execute()
          ->first();
        if (!empty($dupeOpening)) {
          Civi::log()->info('[Mailingwork/Openings] Found opening with existing MailingworkOpening, merging');
          $apiOpening = MailingworkOpening::update(FALSE)
            ->addWhere('id', '=', $dupeOpening['id']);
        }
        else {
          $apiOpening = MailingworkOpening::create(FALSE);
          $stored_count++;
        }
        $apiOpening->addValue('activity_contact_id', $parent_activity['activity_contact_id'])
          ->addValue('opening_date', $opening['date']);

        if (!empty($opening['userAgentType'])) {
          $apiOpening->addValue('user_agent_type_id', $this->getOrCreateOptionValue(
            'mailingwork_user_agent_type',
            $opening['userAgentType']
          ));
        }
        if (!empty($opening['userAgent'])) {
          $apiOpening->addValue('user_agent_id', $this->getOrCreateOptionValue(
            'mailingwork_user_agent',
            $opening['userAgent']
          ));
        }
        $apiOpening->execute();
        $last_opening_date = max($opening['date'], $last_opening_date);
      }
    }
    catch (Exception $e) {
      Civi::log()->error("[Mailingwork/Openings] Exception: {$e->getMessage()}", (array) $e);
      throw $e;
    }
    finally {
      if (!empty($last_opening_date)) {
        civicrm_api3('MailingworkMailing', 'create', [
          'id'                     => $mailing['id'],
          'opening_sync_status_id' => 'in_progress',
          'opening_sync_date'      => $last_opening_date,
        ]);
      }
    }

    return [
      'success'           => TRUE,
      'openings'          => $total_count,
      'imported_openings' => $stored_count,
      'date'              => $last_opening_date,
    ];
  }

}
