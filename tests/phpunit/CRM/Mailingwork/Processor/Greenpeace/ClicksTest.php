<?php

use Civi\Api4;
use Civi\Test;
use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class CRM_Mailingwork_Processor_Greenpeace_ClicksTest
  extends TestCase
  implements HeadlessInterface, HookInterface, TransactionalInterface {
  use Api3TestTrait;

  const MAILINGWORK_API_URL = 'https://webservice.mailingwork.test/webservice/webservice/json/';
  const MAILINGWORK_PASSWORD = '1234';
  const MAILINGWORK_USERNAME = 'user';

  private $mailings = [];
  private $recipients = [];

  private static $fieldDefinitions = [
    'E-Mail' => [
      'id'   => 1,
      'name' => 'E-Mail',
    ],
    'Firstname' => [
      'id'   => 3,
      'name' => 'Firstname',
    ],
    'Lastname' => [
      'id'   => 4,
      'name' => 'Lastname',
    ],
    'Contact_ID' => [
      'id'   => 17,
      'name' => 'Contact_ID',
    ],
  ];

  public function setUpHeadless() {
    return Test::headless()
      ->install('de.systopia.identitytracker')
      ->installMe(__DIR__)
      ->apply(TRUE);
  }

  public function setUp() {
    parent::setUp();

    self::createRequiredOptionGroups();
    self::createRequiredCustomGroups();
    $this->createMailings();
    $this->createRecipients();
    $this->createMailingActivities();
  }

  public function tearDown() {
    CRM_Mailingwork_Identitytracker_Configuration::resetInstance();

    parent::tearDown();
  }

  public function testImportClicks() {

    // -- Define test entities -- //

    $interests = [
      'energy' => [ 
        'id'   => 1,
        'name' => 'Campaign Energy',
      ],
      'ocean' => [ 
        'id'   => 2,
        'name' => 'Campaign Ocean',
      ],
    ];
    
    $links = [
      'gp_energy' => [
        'id'        => 1,
        'url'       => 'http://www.greenpeace.at?utm_campaign=energy',
        'interests' => [ $interests['energy'] ],
      ],
      'li_ocean' => [
        'id'        => 2,
        'url'       => 'https://www.linkedin.com/company/greenpeace?utm_campaign=ocean',
        'interests' => [ $interests['ocean'] ],
      ],
      'yt_ocean' => [
        'id'        => 3,
        'url'       => 'https://www.youtube.com/user/GreenpeaceAT?utm_campaign=ocean',
        'interests' => [ $interests['ocean'] ],
      ],
    ];

    $clicks = [
      [
        'recipient' => 1111,
        'date'      => date('Y-m-d 07:00:00'),
        'link' => [
          'id'  => $links['gp_energy']['id'],
          'url' => $links['gp_energy']['url'],
        ],
        'fields' => [
          [
            'id'    => self::$fieldDefinitions['Firstname']['id'],
            'value' => $this->recipients[0]['first_name'],
          ],
          [
            'id'    => self::$fieldDefinitions['Contact_ID']['id'],
            'value' => $this->recipients[0]['id'],
          ],
        ],
      ],
      [
        'recipient' => 2222,
        'date'      => date('Y-m-d 08:00:00'),
        'link' => [
          'id'  => $links['li_ocean']['id'],
          'url' => $links['li_ocean']['url'],
        ],
        'fields' => [
          [
            'id'    => self::$fieldDefinitions['Firstname']['id'],
            'value' => $this->recipients[1]['first_name'],
          ],
          [
            'id'    => self::$fieldDefinitions['Contact_ID']['id'],
            'value' => $this->recipients[1]['id'],
          ],
        ],
      ],
      [
        'recipient' => 3333,
        'date'      => date('Y-m-d 09:00:00'),
        'link' => [
          'id'  => $links['yt_ocean']['id'],
          'url' => $links['yt_ocean']['url'],
        ],
        'fields' => [
          [
            'id'    => self::$fieldDefinitions['Firstname']['id'],
            'value' => $this->recipients[2]['first_name'],
          ],
          [
            'id'    => self::$fieldDefinitions['Contact_ID']['id'],
            'value' => $this->recipients[2]['id'],
          ],
        ],
      ],
    ];

    // -- Set up mock handler -- //

    $responses = [
      // -> GetFields
      new Response(200, [], self::wrapResponse(array_values(self::$fieldDefinitions))),

      // -> GetLinksByEmailId for Mailing #2
      new Response(200, [], self::wrapResponse([ $links['gp_energy'] ])),

      // -> GetClicksByEmailId for Mailing #2
      new Response(200, [], self::wrapResponse(array_slice($clicks, 0, 1))),

      // -> GetLinksByEmailId for Mailing #3
      new Response(200, [], self::wrapResponse([ $links['li_ocean'], $links['yt_ocean'] ])),

      // -> GetClicksByEmailId for Mailing #3
      new Response(200, [], self::wrapResponse(array_slice($clicks, 1, 2))),
    ];

    $mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mockHandler);

    // -- Assert there are no existing clicks/links/interests -- //

    $interestsQuery = Api4\MailingworkInterest::get()->selectRowCount()->execute();
    $this->assertEquals(0, $interestsQuery->count());

    $linksQuery = Api4\MailingworkLink::get()->selectRowCount()->execute();
    $this->assertEquals(0, $linksQuery->count());

    $clicksQuery = Api4\MailingworkClick::get()->selectRowCount()->execute();
    $this->assertEquals(0, $clicksQuery->count());

    // -- Import clicks -- //

    $procParams = [
      'username'          => self::MAILINGWORK_USERNAME,
      'password'          => self::MAILINGWORK_PASSWORD,
      'skip_mailing_sync' => 1,
    ];

    $processor = new CRM_Mailingwork_Processor_Greenpeace_Clicks(
      $procParams,
      self::MAILINGWORK_API_URL,
      $handlerStack
    );

    $importResults = $processor->import();

    $this->assertEquals([
      $this->mailings[1]['id'] => [
        'click_count' => 1,
        'date'        => $clicks[0]['date'],
      ],
      $this->mailings[2]['id'] => [
        'click_count' => 2,
        'date'        => $clicks[2]['date'],
      ],
    ], $importResults);

    // -- Assert clicks/links/interests have been imported -- //

    $linksQuery = Api4\MailingworkLink::get()
      ->addSelect(
        'mailing_id',
        'mailingwork_id',
        'mailingwork_interest.name',
        'mailingwork_interest.mailingwork_id',
        'url'
      )
      ->addJoin(
        'MailingworkLinkInterest AS mailingwork_link_interest',
        'LEFT',
        ['id', '=', 'mailingwork_link_interest.link_id']
      )
      ->addJoin(
        'MailingworkInterest AS mailingwork_interest',
        'LEFT',
        ['mailingwork_link_interest.interest_id', '=', 'mailingwork_interest.id']
      )
      ->addOrderBy('mailingwork_id', 'ASC')
      ->execute();

    $this->assertEquals(3, $linksQuery->count());

    for ($i = 0; $i < count($linksQuery); $i++) {
      $actual = $linksQuery[$i];
      $expected = current($links);
      $linkMailing = $this->mailings[$i < 1 ? 1 : 2];
      $linkInterest = $interests[$i < 1 ? 'energy' : 'ocean' ];

      $this->assertEquals([
        'id'                                  => $actual['id'],
        'mailing_id'                          => $linkMailing['id'],
        'mailingwork_id'                      => $expected['id'],
        'mailingwork_interest.mailingwork_id' => $linkInterest['id'],
        'mailingwork_interest.name'           => $linkInterest['name'],
        'url'                                 => $expected['url'],
      ], $actual);

      next($links);
    }

    $interestsQuery = Api4\MailingworkInterest::get()->execute();
    $this->assertEquals(2, $interestsQuery->count());

    $clicksQuery = Api4\MailingworkClick::get()
      ->addOrderBy('click_date', 'ASC')
      ->execute();

    $this->assertEquals(3, $clicksQuery->count());

    for ($i = 0; $i < count($clicksQuery); $i++) {
      $actual = $clicksQuery[$i];
      $targetContactID = self::getTargetContactID($actual['activity_contact_id']);
      $expected = $clicks[$i];

      $this->assertEquals([
        'id'                  => $actual['id'],
        'click_date'          => $expected['date'],
        'activity_contact_id' => $actual['activity_contact_id'],
        'link_id'             => $linksQuery[$i]['id'],
      ], $actual);

      $this->assertEquals($this->recipients[$i]['id'], $targetContactID);
    }

    // -- Assert mailings have been updated -- //

    $mailing_2 = Api4\MailingworkMailing::get()
      ->addSelect('click_sync_date', 'click_sync_status_id:name')
      ->addWhere('id', '=', $this->mailings[1]['id'])
      ->execute()
      ->first();

    $this->assertEquals([
      'id' => $mailing_2['id'],
      'click_sync_date' => $clicks[0]['date'],
      'click_sync_status_id:name' => 'in_progress',
    ], $mailing_2);

    $mailing_3 = Api4\MailingworkMailing::get()
      ->addSelect('click_sync_date', 'click_sync_status_id:name')
      ->addWhere('id', '=', $this->mailings[2]['id'])
      ->execute()
      ->first();

    $this->assertEquals([
      'id' => $mailing_3['id'],
      'click_sync_date' => $clicks[2]['date'],
      'click_sync_status_id:name' => 'in_progress',
    ], $mailing_3);

  }

  private function createMailingActivities() {
    foreach ($this->mailings as $mailing) {
      foreach ($this->recipients as $recipient) {
        $activityParams = [
          'activity_date_time'                => date('Y-m-d 06:00:00'),
          'activity_type_id:name'             => 'Online_Mailing',
          'email_information.mailing_id'      => $mailing['id'],
          'email_information.mailing_subject' => $mailing['subject'],
          'email_information.mailing_type'    => $mailing['type_id'],
          'source_contact_id'                 => 1,
          'target_contact_id'                 => $recipient['id'],
        ];

        civicrm_api4('Activity', 'create', [ 'values' => $activityParams ]);
      }
    }
  }

  private function createMailings() {
    $syncStatuses = ['completed', 'in_progress', 'pending'];

    foreach (range(1, 3) as $i) {
      $createMailingResult = Api4\MailingworkMailing::create()
        ->addValue('click_sync_status_id:name', $syncStatuses[$i - 1])
        ->addValue('mailingwork_identifier',    random_int(0, 999))
        ->addValue('status_id',                 'activated')
        ->addValue('subject',                   "Mailing #$i")
        ->addValue('type_id',                   'standard')
        ->execute();

      $this->mailings[] = $createMailingResult->first();
    }
  }

  private function createRecipients() {
    $lastName = bin2hex(random_bytes(8));

    foreach (range(1, 3) as $i) {
      $createContactResult = Api4\Contact::create()
        ->addValue('contact_type', 'Individual')
        ->addValue('first_name',   "Recipient_$i")
        ->addValue('last_name',    $lastName)
        ->execute();

      $this->recipients[] = $createContactResult->first();
    }
  }

  private static function createRequiredCustomGroups() {
    $onlineMailingOptVal = self::getOptionValue('activity_type', 'Online_Mailing');

    Api4\CustomGroup::create()
      ->addValue('extends',                     'Activity')
      ->addValue('extends_entity_column_value', $onlineMailingOptVal)
      ->addValue('is_active',                   TRUE)
      ->addValue('name',                        'email_information')
      ->addValue('table_name',                  'civicrm_value_email_information')
      ->addValue('title',                       'Email Information')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_description')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'String')
      ->addValue('html_type',            'Text')
      ->addValue('is_active',            TRUE)
      ->addValue('is_searchable',        TRUE)
      ->addValue('label',                'Mailing Description')
      ->addValue('name',                 'mailing_description')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_id')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'String')
      ->addValue('html_type',            'Text')
      ->addValue('is_active',            TRUE)
      ->addValue('is_searchable',        TRUE)
      ->addValue('label',                'Mailing ID')
      ->addValue('name',                 'mailing_id')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_subject')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'String')
      ->addValue('html_type',            'Text')
      ->addValue('is_active',            TRUE)
      ->addValue('is_searchable',        TRUE)
      ->addValue('label',                'Mailing Subject')
      ->addValue('name',                 'mailing_subject')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_type')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'Int')
      ->addValue('html_type',            'Select')
      ->addValue('is_active',            TRUE)
      ->addValue('is_searchable',        TRUE)
      ->addValue('label',                'Mailing Type')
      ->addValue('name',                 'mailing_type')
      ->addValue('option_group_id.name', 'mailing_type')
      ->execute();
  }

  private static function createRequiredOptionGroups() {
    Api4\OptionValue::create()
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Online Mailing')
      ->addValue('name',                 'Online_Mailing')
      ->addValue('option_group_id.name', 'activity_type')
      ->execute();

    Api4\OptionGroup::create()
      ->addValue('description', 'Type of Online Mailing')
      ->addValue('is_active',   TRUE)
      ->addValue('name',        'mailing_type')
      ->addValue('title',       'Mailing Type')
      ->execute();

    Api4\OptionValue::create()
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Standard')
      ->addValue('option_group_id.name', 'mailing_type')
      ->addValue('value',                1)
      ->execute();

    Api4\OptionValue::create()
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Transactional')
      ->addValue('option_group_id.name', 'mailing_type')
      ->addValue('value',                2)
      ->execute();

    Api4\OptionValue::create()
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Campaign')
      ->addValue('option_group_id.name', 'mailing_type')
      ->addValue('value',                3)
      ->execute();
  }

  private static function getOptionValue(string $optionGroup, string $name) {
    return Api4\OptionValue::get()
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', $optionGroup)
      ->addWhere('name', '=', $name)
      ->setLimit(1)
      ->execute()
      ->first()['value'];
  }

  private static function getTargetContactID(int $activityContactID) {
    return Api4\ActivityContact::get()
      ->addSelect('contact_id')
      ->addWhere('id',                  '=', $activityContactID)
      ->addWhere('record_type_id:name', '=', 'Activity Targets')
      ->setLimit(1)
      ->execute()
      ->first()['contact_id'];
  }

  private static function wrapResponse(array $result) {
    return json_encode([
      'error'   => 0,
      'message' => 'successfully executed',
      'result'  => $result,
    ]);
  }

}

?>
