<?php

use Civi\Api4\Activity;
use Civi\Api4\ActivityContact;
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\Group;
use Civi\Api4\GroupContact;

/**
 * Import opt-outs
 *
 * @todo DRY up code (Bounces/Clicks/Openings/Recipients)
 *
 * Class CRM_Mailingwork_Processor_Greenpeace_Openings
 */
class CRM_Mailingwork_Processor_Greenpeace_Optouts extends CRM_Mailingwork_Processor_Base {
  /**
   * Required root properties that should be present in API responses
   *
   * @var array
   */
  protected $rootProperties = ['email', 'recipient', 'date', 'fields', 'listId'];

  protected $standardSyncDays = 90;

  private $groups = [];

  private $mailings = [];

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
    $this->prepareGroupCache();
    $start_date = Civi::settings()->get('mailingwork_optouts_start_date');
    $list_map = Civi::settings()->get('mailingwork_optout_list_map');
    Civi::log()->info("[Mailingwork/Optouts] Starting synchronization. Start Date: {$start_date}");
    $start = 0;
    $limit = 1000;
    $more_pages = TRUE;
    $last_optout_date = NULL;
    $activity_count = 0;
    $optout_count = 0;
    while ($more_pages) {
      $optouts = $this->client->api('optout')
        ->getOptouts([
            'fieldId'   => array_search(self::EMAIL_FIELD, $this->fields),
            'startDate' => $start_date,
            'start'     => $start,
            'limit'     => $limit,
          ]
        );
      $count = count($optouts);
      Civi::log()->info("[Mailingwork/Optouts] Fetched {$count} opt-outs");
      $start += $count;
      if ($count < $limit) {
        $more_pages = FALSE;
      }
      foreach ($optouts as $optout) {
        $optout_count++;
        $optout = $this->prepareRecipient($optout);
        if (empty($optout[self::EMAIL_FIELD])) {
          Civi::log()->error('[Mailingwork/Optout] Unable to determine email for recipient ' . $optout['recipient']);
          continue;
        }
        $contacts = Email::get(FALSE)
          ->addSelect('contact_id')
          ->addWhere('email', '=', $optout[self::EMAIL_FIELD])
          ->addWhere('contact_id.is_deleted', '=', FALSE)
          ->execute();
        // extract array of contact IDs
        $contacts = array_column((array) $contacts, 'contact_id');
        if (count($contacts) == 0) {
          Civi::log()->error('[Mailingwork/Optout] Unable to determine contact for recipient ' . $optout['recipient'] . ' / ' . $optout[self::EMAIL_FIELD]);
          continue;
        }
        if (empty($optout['listId'])) {
          Civi::log()->error('[Mailingwork/Optout] Unable to determine opt-out list for recipient ' . $optout['recipient'] . ' / ' . $optout[self::EMAIL_FIELD]);
          continue;
        }
        $mailing = $this->getMailing($optout);

        $groups_to_remove = [];
        $suppressions_to_set = [];
        foreach ($optout['listId'] as $list) {
          if (empty($list_map[$list])) {
            Civi::log()->info('[Mailingwork/Optout] Unhandled opt-out list with ID ' . $list .  ' for recipient ' . $optout['recipient'] . ' / ' . $optout[self::EMAIL_FIELD]);
          }
          if (!empty($list_map[$list]['groups'])) {
            $groups_to_remove = array_merge($groups_to_remove, $list_map[$list]['groups']);
          }
          if (!empty($list_map[$list]['suppressions'])) {
            $suppressions_to_set = array_merge($suppressions_to_set, $list_map[$list]['suppressions']);
          }
        }

        if (count($groups_to_remove) > 0) {
          $activity_count += $this->removeGroups($contacts, $groups_to_remove, $optout, $mailing);
        }

        foreach ($suppressions_to_set as $suppression) {
          $activity_count += $this->setSuppression($contacts, $suppression, $optout, $mailing);
        }

        $last_optout_date = $optout['date'];
        if (!empty($this->params['soft_limit']) && $optout_count >= $this->params['soft_limit']) {
          $more_pages = FALSE;
        }
      }

      Civi::log()->info("[Mailingwork/Optout] Processed {$optout_count} opt-outs. Activity count: {$activity_count}");

      if (!empty($last_optout_date)) {
        Civi::settings()->set('mailingwork_optouts_start_date', $last_optout_date);
      }
    }

    return [
      'success'    => TRUE,
      'activities' => $activity_count,
      'optouts'    => $optout_count,
      'date'       => $last_optout_date,
    ];
  }

  private function removeGroups(array $contacts, array $groups_to_remove, array $optout, array $mailing = NULL) {
    $count = 0;
    $groupContacts = GroupContact::get(FALSE)
      ->addWhere('contact_id', 'IN', $contacts)
      ->addWhere('group_id', 'IN', $groups_to_remove)
      ->addWhere('status', '=', 'Added')
      /*->addChain('remove_groups', GroupContact::update()
        ->addWhere('id', '=', '$id')
        ->addValue('status', 'Removed')
      )*/
      ->execute();
    foreach ($groupContacts as $groupContact) {
      $count++;
      // GroupContact in APIv4 is broken in Civi 5.24, using v3
      civicrm_api3('GroupContact', 'create', [
        'id' => $groupContact['id'],
        'status' => 'Removed',
      ]);
      // this could probably be performed as part of the chain, but activities in APIv4 are a bit unstable as of Civi 5.24
      $group_title = $this->groups[$groupContact['group_id']];
      $activity = Activity::create(FALSE)
        ->addValue('source_contact_id', CRM_Core_Session::singleton()->getLoggedInContactID())
        ->addValue('activity_type_id', CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Optout'))
        ->addValue('activity_date_time', $optout['date'])
        ->addValue('medium_id', CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'medium_id', 'web'))
        ->addValue('optout_information.optout_source', civicrm_api3('OptionValue', 'getvalue', [
          'return' => 'value',
          'option_group_id' => 'optout_source',
          'name' => 'Mailingwork',
        ]))
        ->addValue('optout_information.optout_type', civicrm_api3('OptionValue', 'getvalue', [
          'return' => 'value',
          'option_group_id' => 'optout_type',
          'name' => 'group',
        ]))
        ->addValue('optout_information.optout_identifier', $mailing['id'] ?? NULL)
        ->addValue('optout_information.optout_item', $optout[self::EMAIL_FIELD])
        ->addValue('subject', "Opt-Out from \"{$group_title}\" via Mailingwork")
        ->addValue('source_record_id', $groupContact['group_id'])
        ->addChain('activity_contact', ActivityContact::create(FALSE)
          ->addValue('activity_id', '$id')
          ->addValue('contact_id', $groupContact['contact_id'])
          ->addValue('record_type_id', 3)
        );
      $parent = NULL;
      if (!is_null($mailing)) {
        $parent = $this->getParentActivity($groupContact['contact_id'], $optout, $mailing);
      }
      $campaign_id = $mailing['campaign_id'] ?? NULL;
      if (!is_null($parent)) {
        $activity->addValue('activity_hierarchy.parent_activity_id', $parent['activity_id']);
        if (!empty($parent['campaign_id'])) {
          // prefer campaign_id from parent activity over mailing-derived campaign
          $campaign_id = $parent['campaign_id'];
        }
      }
      $activity->addValue('campaign_id', $campaign_id);

      $activity = $activity->execute()->first();

      civicrm_api3('ActivityContactEmail', 'create', [
        'activity_contact_id' => $activity['activity_contact'][0]['id'],
        'email'               => $optout[self::EMAIL_FIELD],
      ]);
    }
    return $count;
  }

  private function setSuppression(array $contacts, $suppression, array $optout, array $mailing = NULL) {
    $count = 0;
    $suppressedContacts = Contact::get(FALSE)
      ->addWhere('id', 'IN', $contacts)
      ->addWhere($suppression, '=', 0)
      ->addChain('set_suppression', Contact::update(FALSE)
        ->addWhere('id', '=', '$id')
        ->addValue($suppression, 1)
      )
      ->execute();
    foreach ($suppressedContacts as $contact) {
      $count++;
      // this could probably be performed as part of the chain, but activities in APIv4 are a bit unstable as of Civi 5.24
      $suppression_title = civicrm_api3('OptionValue', 'getvalue', [
        'return' => 'label',
        'option_group_id' => 'optout_type',
        'name' => $suppression,
      ]);
      $activity = Activity::create(FALSE)
        ->addValue('source_contact_id', CRM_Core_Session::singleton()->getLoggedInContactID())
        ->addValue('activity_type_id', CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Optout'))
        ->addValue('activity_date_time', $optout['date'])
        ->addValue('medium_id', CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'medium_id', 'web'))
        ->addValue('optout_information.optout_source', civicrm_api3('OptionValue', 'getvalue', [
          'return' => 'value',
          'option_group_id' => 'optout_source',
          'name' => 'Mailingwork',
        ]))
        ->addValue('optout_information.optout_type', civicrm_api3('OptionValue', 'getvalue', [
          'return' => 'value',
          'option_group_id' => 'optout_type',
          'name' => $suppression,
        ]))
        ->addValue('optout_information.optout_identifier', $mailing['id'])
        ->addValue('optout_information.optout_item', $optout[self::EMAIL_FIELD])
        ->addValue('subject', "Added \"{$suppression_title}\" via Mailingwork")
        ->addChain('activity_contact', ActivityContact::create(FALSE)
          ->addValue('activity_id', '$id')
          ->addValue('contact_id', $contact['id'])
          ->addValue('record_type_id', 3)
        );
      $parent = NULL;
      if (!is_null($mailing)) {
        $parent = $this->getParentActivity($contact['id'], $optout, $mailing);
      }
      $campaign_id = $mailing['campaign_id'];
      if (!is_null($parent)) {
        $activity->addValue('activity_hierarchy.parent_activity_id', $parent['activity_id']);
        if (!empty($parent['campaign_id'])) {
          // prefer campaign_id from parent activity over mailing-derived campaign
          $campaign_id = $parent['campaign_id'];
        }
      }
      $activity->addValue('campaign_id', $campaign_id);

      $activity = $activity->execute()->first();

      civicrm_api3('ActivityContactEmail', 'create', [
        'activity_contact_id' => $activity['activity_contact'][0]['id'],
        'email'               => $optout[self::EMAIL_FIELD],
      ]);
    }
    return $count;
  }

  private function prepareGroupCache() {
    $groups = Group::get(FALSE)
      ->addSelect('id', 'title')
      ->execute();
    foreach ($groups as $group) {
      $this->groups[$group['id']] = $group['title'];
    }
  }

  private function getMailing(array $optout) {
    if (empty($optout['email'])) {
      return NULL;
    }
    if (!array_key_exists($optout['email'], $this->mailings)) {
      $mailing = reset(civicrm_api3('MailingworkMailing', 'get', [
        'return' => ['id'],
        'mailingwork_identifier' => $optout['email'],
        'api.MailingworkMailing.getcampaign' => [],
      ])['values']);
      if (!empty($mailing)) {
        $this->mailings[$optout['email']] = [
          'id' => $mailing['id'],
          'campaign_id' => $mailing['api.MailingworkMailing.getcampaign']['values']['id'],
        ];
      }
      else {
        $this->mailings[$optout['email']] = NULL;
      }
    }
    return $this->mailings[$optout['email']];
  }

}
