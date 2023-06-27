<?php

use Civi\Api4;

class CRM_Mailingwork_Processor_Greenpeace_Clicks extends CRM_Mailingwork_Processor_Base {

  private $links = [];
  protected $standardSyncDays = 90;

  /**
   * Fetch and process clicks
   *
   * @return array import results
   * @throws \Exception
   */
  public function import() {

    // -- Sync mailings -- //

    if (empty($this->params['skip_mailing_sync'])) {
      $this->importMailings();
    }

    // -- Select mailings -- //

    $syncStatuses = [
      'in_progress' => $this->getOrCreateOptionValue('mailingwork_mailing_sync_status', 'in_progress'),
      'pending'     => $this->getOrCreateOptionValue('mailingwork_mailing_sync_status', 'pending'),
      'retrying'    => $this->getOrCreateOptionValue('mailingwork_mailing_sync_status', 'retrying'),
      'completed'    => $this->getOrCreateOptionValue('mailingwork_mailing_sync_status', 'completed'),
    ];

    $mailingQuery = Api4\MailingworkMailing::get()
      ->addSelect('*', 'click_sync_status_id:name')
      ->addWhere('recipient_sync_status_id', 'IN', [
        $syncStatuses['in_progress'],
        $syncStatuses['completed'],
      ])
      ->addWhere('click_sync_status_id', 'IN', [
        $syncStatuses['pending'],
        $syncStatuses['in_progress'],
        $syncStatuses['retrying'],
      ]);

    if (isset($this->params['mailingwork_mailing_id'])) {
      $mailingQuery->addWhere('id', '=', $this->params['mailingwork_mailing_id']);
    }

    $mailings = $mailingQuery->execute();

    // -- Import clicks -- //

    $result = [];

    foreach ($mailings as $mailing) {
      $mailingID = $mailing['id'];
      $subject = $mailing['subject'];

      try {
        Civi::log()->info("[Mailingwork/Clicks] Starting synchronization for mailing $mailingID/$subject");

        $this->importMailingLinks($mailing);
        $result[$mailingID] = $this->importMailingClicks($mailing);

        $clickCount = $result[$mailingID]['click_count'];
        Civi::log()->info("[Mailingwork/Clicks] Finished synchronization for mailing $mailingID/$subject. Imported $clickCount");
      } catch (Exception $exc) {
        $errMsg = $exc->getMessage();
        Civi::log()->error("[Mailingwork/Clicks] Synchronization of mailing $mailingID/$subject failed. Error: $errMsg");

        $newClickSyncStatus = $mailing['click_sync_status_id:name'] === 'retrying' ? 'failed' : 'retrying';

        Api4\MailingworkMailing::update(FALSE)
          ->addWhere('id', '=', $mailingID)
          ->addValue('click_sync_status_id:name', $newClickSyncStatus)
          ->execute();
      }
    }

    return $result;

  }

  private function getActivityContactID(int $mailingID, int $contactID) {
    $activityTargetOptValue = $this->getOrCreateOptionValue('activity_contacts', 'Activity Targets');

    $activityQuery = Api4\Activity::get()
      ->addSelect('activity_contact.id')
      ->addJoin(
        'ActivityContact AS activity_contact', 'INNER', ['id', '=', 'activity_contact.activity_id']
      )
      ->addWhere('activity_contact.contact_id',     '=', $contactID)
      ->addWhere('activity_contact.record_type_id', '=', $activityTargetOptValue)
      ->addWhere('activity_type_id:name',           '=', 'Online_Mailing')
      ->addWhere('email_information.mailing_id',    '=', $mailingID)
      ->addOrderBy('activity_date_time', 'DESC')
      ->setLimit(1)
      ->execute();

    return $activityQuery->first()['activity_contact.id'];
  }

  private function getClicks($mwMailingID, $startDate) {
    $fields = array_search(self::EMAIL_FIELD, $this->fields) . ',' .
      array_search('Contact_ID', $this->fields);
    return $this->client->api('click')->getClicksByEmailId($mwMailingID, [
      'fieldId'   => $fields,
      'passDate'  => 1,
      'passLink'  => 1,
      'startDate' => $startDate,
    ]);
  }

  /**
   * Fetch and process clicks for a specific mailing
   *
   * @param $mailing
   *
   * @return array
   * @throws \Exception
   */
  private function importMailingClicks(array $mailing) {
    $startDate = $mailing['click_sync_date'] ?? NULL;
    $lastClickDate = $startDate;
    $totalCount = 0;

    try {
      $clicks = $this->getClicks($mailing['mailingwork_identifier'], $startDate);

      foreach ($clicks as $click) {
        $this->resolveResponseFields($click);
        $contactID = $this->resolveContactId($click->fields);

        if (empty($contactID)) {
          $clickContactID = $click->fields['Contact_ID'];

          if (empty($clickContactID)) continue;

          Civi::log()->info("[Mailingwork/Clicks] Unable to identify contact: $clickContactID");
          continue;
        }

        $activityContactID = self::getActivityContactID($mailing['id'], $contactID);

        if (is_null($activityContactID)) {
          Civi::log()->warning('[Mailingwork/Clicks] Ignoring click without matching activity');
          continue;
        }

        $linkID = $this->links[$mailing['id']][$click->link->id];

        if (self::isDuplicateClick($click->date, $activityContactID, $linkID)) {
          Civi::log()->info('[Mailingwork/Clicks] Found click with existing MailingworkClick, skipping');
        } else {
          Api4\MailingworkClick::create()
            ->addValue('activity_contact_id', $activityContactID)
            ->addValue('click_date',          $click->date)
            ->addValue('link_id',             $linkID)
            ->execute();
        }

        $lastClickDate = max($click->date, $lastClickDate);
        $totalCount++;
      }

      $startDate = $lastClickDate;
    } catch (Exception $exc) {
      $errorMessage = "[Mailingwork/Clicks] Exception: {$exc->getMessage()}";
      Civi::log()->error($errorMessage, (array) $exc);
      throw $exc;
    }

    if (isset($lastClickDate)) {
      self::updateMailingClickSyncStatus($mailing['id'], $lastClickDate);
    }

    if ($this->isSyncCompleted($mailing)) {
      self::setMailingClicksCompleted($mailing['id']);
    }

    return [
      'click_count' => $totalCount,
      'date'        => $lastClickDate,
    ];
  }

  private function importMailingLinks($mailing) {
    $links = $this->client->api('link')->getLinksByEmailId($mailing['mailingwork_identifier']);

    foreach ($links as $link) {

      $linksQuery = Api4\MailingworkLink::get()
        ->addSelect('id')
        ->addWhere('mailing_id',     '=', $mailing['id'])
        ->addWhere('mailingwork_id', '=', $link->id)
        ->setLimit(1)
        ->execute();

      if ($linksQuery->count() < 1) {
        $createdLink = Api4\MailingworkLink::create()
          ->addValue('mailing_id', $mailing['id'])
          ->addValue('mailingwork_id', $link->id)
          ->addValue('url', $link->url)
          ->execute()
          ->first();

        $this->links[$mailing['id']][$link->id] = $createdLink['id'];
        self::importLinkInterests($createdLink['id'], $link->interests);
      } else {
        $this->links[$mailing['id']][$link->id] = $linksQuery->first()['id'];
      }
    }
  }

  private function importLinkInterests(int $linkID, array $interests) {
    foreach ($interests as $interest) {
      $linkInterestsQuery = Api4\MailingworkLinkInterest::get()
        ->addJoin(
          'MailingworkInterest AS mailingwork_interest',
          'LEFT',
          ['interest_id', '=', 'mailingwork_interest.id']
        )
        ->addWhere('link_id',                             '=', $linkID)
        ->addWhere('mailingwork_interest.mailingwork_id', '=', $interest->id)
        ->setLimit(1)
        ->execute();

      if ($linkInterestsQuery->count() > 0) continue;

      $interestsQuery = Api4\MailingworkInterest::get()
        ->addSelect('id')
        ->addWhere('mailingwork_id', '=', $interest->id)
        ->setLimit(1)
        ->execute();

      $interestExists = $interestsQuery->count() > 0;
      $interestID = $interestExists ? $interestsQuery->first()['id'] : NULL;

      if (!$interestExists) {
        $createdInterest = Api4\MailingworkInterest::create()
          ->addValue('mailingwork_id', $interest->id)
          ->addValue('name',           $interest->name)
          ->execute()
          ->first();

        $interestID = $createdInterest['id'];
      }

      $createLinkInterestResult = Api4\MailingworkLinkInterest::create()
        ->addValue('interest_id', $interestID)
        ->addValue('link_id',     $linkID)
        ->execute();
    }
  }

  private function resolveResponseFields(&$click) {
    $resolvedFields = [];

    foreach ($click->fields as $index => $field) {
      $fieldName = $this->fields[$field->id];
      $resolvedFields[$fieldName] = $field->value;
    }

    $click->fields = $resolvedFields;
  }

  private function updateMailingClickSyncStatus(int $mailingID, string $lastClickDate) {
    $statusInProgress = $this->getOrCreateOptionValue(
      'mailingwork_mailing_sync_status',
      'in_progress'
    );

    Api4\MailingworkMailing::update()
      ->addWhere('id', '=', $mailingID)
      ->addValue('click_sync_date',      $lastClickDate)
      ->addValue('click_sync_status_id', $statusInProgress)
      ->execute();
  }

  private static function setMailingClicksCompleted($mailingID) {
    Api4\MailingworkMailing::update()
      ->addValue('click_sync_status_id:name', 'completed')
      ->addWhere('id', '=', $mailingID)
      ->execute();
  }

  private static function isDuplicateClick($clickDate, $activityContactID, $linkID) {
    $count = Api4\MailingworkClick::get()
      ->addWhere('activity_contact_id', '=', $activityContactID)
      ->addWhere('click_date',          '=', $clickDate)
      ->addWhere('link_id',             '=', $linkID)
      ->execute()
      ->rowCount;

    return $count > 0;
  }

}
