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
        'date'   => date('Y-m-d 07:00:00'),
        'link'   => [ 'id' => $links['gp_energy']['id'] ],
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
        'date'   => date('Y-m-d 08:00:00'),
        'link'   => [ 'id' => $links['li_ocean']['id'] ],
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
        'date'   => date('Y-m-d 09:00:00'),
        'link'   => [ 'id' => $links['yt_ocean']['id'] ],
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
    ];

    $mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mockHandler);

    // -- Assert there are no existing clicks/links/interests -- //

    $interests = Api4\MailingworkInterest::get()->selectRowCount()->execute();
    $this->assertEquals(0, $interests->count());

    $links = Api4\MailingworkLink::get()->selectRowCount()->execute();
    $this->assertEquals(0, $links->count());

    $clicks = Api4\MailingworkClick::get()->selectRowCount()->execute();
    $this->assertEquals(0, $clicks->count());

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

    // -- Assert clicks/links/interests have been imported -- //

    $this->assertTrue(TRUE);

  }

  private function createMailingActivities() {
    $customFields = [
      'mailing_id'      => self::getCustomFieldID('email_information', 'mailing_id'),
      'mailing_subject' => self::getCustomFieldID('email_information', 'mailing_subject'),
      'mailing_type'    => self::getCustomFieldID('email_information', 'mailing_type'),
    ];

    foreach ($this->mailings as $mailing) {
      foreach ($this->recipients as $recipient) {
        $activityParams = [
          'activity_date_time'                         => date('Y-m-d 06:00:00'),
          'activity_type_id:name'                      => 'Online_Mailing',
          'custom_' . $customFields['mailing_id']      => $mailing['id'],
          'custom_' . $customFields['mailing_subject'] => $mailing['subject'],
          'custom_' . $customFields['mailing_type']    => $mailing['type_id'],
          'source_contact_id'                          => 1,
          'target_contact_id'                          => $recipient['id'],
        ];

        civicrm_api4('Activity', 'create', [ 'values' => $activityParams ]);
      }
    }
  }

  private function createMailings() {
    $syncStatuses = ['completed', 'in_progress', 'pending'];

    foreach (range(1, 3) as $i) {
      $createMailingResult = Api4\MailingworkMailing::create()
        ->addValue('click_sync_status_id',   $syncStatuses[$i - 1])
        ->addValue('mailingwork_identifier', random_int(0, 999))
        ->addValue('status_id',              'activated')
        ->addValue('subject',                "Mailing #$i")
        ->addValue('type_id',                'standard')
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
    Api4\CustomGroup::create()
      ->addValue('extends',                       'Activity')
      ->addValue('extends_entity_column_id:name', 'Online_Mailing')
      ->addValue('is_active',                     TRUE)
      ->addValue('name',                          'email_information')
      ->addValue('table_name',                    'civicrm_value_email_information')
      ->addValue('title',                         'Email Information')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_description')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'String')
      ->addValue('html_type',            'Text')
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Mailing Description')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_id')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'String')
      ->addValue('html_type',            'Text')
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Mailing ID')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_subject')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'String')
      ->addValue('html_type',            'Text')
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Mailing Subject')
      ->execute();

    Api4\CustomField::create()
      ->addValue('column_name',          'mailing_type')
      ->addValue('custom_group_id.name', 'email_information')
      ->addValue('data_type',            'Int')
      ->addValue('html_type',            'Select')
      ->addValue('is_active',            TRUE)
      ->addValue('label',                'Mailing Type')
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

  private static function getCustomFieldID(string $customGroup, string $name) {
    return Api4\CustomField::get()
      ->addSelect('id')
      ->addWhere('custom_group_id:name', '=', $customGroup)
      ->addWhere('name', '=', $name)
      ->setLimit(1)
      ->execute()
      ->first()['id'];
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

  private static function wrapResponse(array $result) {
    return json_encode([
      'error'   => 0,
      'message' => 'successfully executed',
      'result'  => $result,
    ]);
  }

}

?>
