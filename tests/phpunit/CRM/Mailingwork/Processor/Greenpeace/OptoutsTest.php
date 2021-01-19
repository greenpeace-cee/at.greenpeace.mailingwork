<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Test Folders API Processor
 *
 * @group headless
 */
class CRM_Mailingwork_Processor_Greenpeace_OptoutsTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  /**
   * @var mixed
   */
  private $campaignId;

  /**
   * @var array|int
   */
  private $groupId;

  /**
   * @var array|int
   */
  private $altGroupId;

  /**
   * @var mixed
   */
  private $mailingActivityTypeID;

  /**
   * @var mixed
   */
  private $optoutActivityTypeID;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply(TRUE);
  }

  public function setUp() {
    $session = CRM_Core_Session::singleton();
    $session->set('userID', 1);
    $this->setUpFieldsAndData();
    parent::setUp();
  }

  protected function setUpFieldsAndData() {
    $this->campaignId = reset($this->callAPISuccess('Campaign', 'create', [
      'name'                => 'Mailing Campaign',
      'title'               => 'Mailing Campaign',
      'external_identifier' => 'MC',
    ])['values'])['id'];

    $this->groupId = reset($this->callAPISuccess('Group', 'create', [
      'title' => 'Newsletter',
    ])['values'])['id'];

    $this->altGroupId = reset($this->callAPISuccess('Group', 'create', [
      'title' => 'alt Newsletter',
    ])['values'])['id'];

    $this->mailingActivityTypeID = reset($this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'activity_type',
      'name'            => 'Online_Mailing',
      'label'           => 'Online Mailing',
    ])['values'])['value'];

    $this->optoutActivityTypeID = reset($this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'activity_type',
      'name'            => 'Optout',
      'label'           => 'Opt-Out',
    ])['values'])['value'];

    $this->callAPISuccess('OptionGroup', 'create', [
      'name' => 'optout_type',
      'data_type' => 'Integer',
    ]);

    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'optout_type',
      'name' => 'group',
    ]);

    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'optout_type',
      'name' => 'is_opt_out',
    ]);

    $this->callAPISuccess('OptionGroup', 'create', [
      'name' => 'optout_source',
      'data_type' => 'Integer',
    ]);

    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'optout_source',
      'name' => 'Mailingwork',
    ]);

    $this->callAPISuccess('OptionGroup', 'create', [
      'name' => 'email_provider',
      'data_type' => 'Integer',
    ]);

    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'email_provider',
      'name' => 'Mailingwork',
    ]);

    // core hack - missing cache invalidation. there's probably a cleaner way to do this ...
    unset(\Civi::$statics['CRM_Core_BAO_OptionGroup']['titles_by_name']);

    $this->callAPISuccess('CustomGroup', 'create', [
      'title' => 'Email Information',
      'name' => 'email_information',
      'table_name' => 'civicrm_value_email_information',
      'extends' => 'Activity',
      'extends_entity_column_value' => $this->mailingActivityTypeID,
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'email_information',
      'label' => 'Email',
      'name' => 'email',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'email_information',
      'label' => 'Email Provider',
      'name' => 'email_provider',
      'column_name' => 'email_provider',
      'option_group_id' => 'email_provider',
      'data_type' => 'Integer',
      'html_type' => 'Select',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'email_information',
      'label' => 'Mailing Subject',
      'name' => 'mailing_subject',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'email_information',
      'label' => 'Mailing Type',
      'name' => 'mailing_type',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'email_information',
      'label' => 'Mailing ID',
      'name' => 'mailing_id',
      'column_name' => 'mailing_id',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    $this->callAPISuccess('CustomGroup', 'create', [
      'title' => 'Opt-Out Information',
      'name' => 'optout_information',
      'extends' => 'Activity',
      'extends_entity_column_value' => $this->optoutActivityTypeID,
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'optout_information',
      'label' => 'Opt-Out Type',
      'name' => 'optout_type',
      'option_group_id' => 'optout_type',
      'data_type' => 'Integer',
      'html_type' => 'Select',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'optout_information',
      'label' => 'Opt-Out Source',
      'name' => 'optout_source',
      'option_group_id' => 'optout_source',
      'data_type' => 'Integer',
      'html_type' => 'Select',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'optout_information',
      'label' => 'Opt-Out Identifier',
      'name' => 'optout_identifier',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => 'optout_information',
      'label' => 'Opt-Out Item',
      'name' => 'optout_item',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    Civi::settings()->set('mailingwork_fallback_campaign', $this->campaignId);
    Civi::settings()->set('mailingwork_optout_list_map', [
      '1' => [
        'groups' => [$this->groupId, $this->altGroupId],
      ],
      '2' => [
        'groups' => [$this->groupId, $this->altGroupId],
        'suppressions' => ['is_opt_out']
      ]
    ]);
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test that opt-outs are processed
   */
  public function testOptouts() {
    $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'email' => 'foo@example.com',
    ]);
    $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'email' => 'bar@example.org',
    ]);
    $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'email' => 'bar@example.org',
    ]);
    $container = [];
    $history = Middleware::history($container);
    $mock = new MockHandler([
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"name":"E-Mail","description":"mandatory","alias":"E-Mail","type":"varchar","options":"","default":"","behavior":"email"},{"id":17,"name":"Contact_ID","description":"","alias":"personID","type":"int","options":"","default":"","behavior":""}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"parent_id":0,"name":"1: Parent_1","role_ids":[]},{"id":5,"parent_id":7,"name":"2.1: Child_1","role_ids":[]},{"id":7,"parent_id":0,"name":"2: Parent_2","role_ids":[]},{"id":2,"parent_id":1,"name":"1.1: Child_1","role_ids":[]},{"id":3,"parent_id":1,"name":"1.2: Child_2","role_ids":[]},{"id":4,"parent_id":7,"name":"2.2: Child_2","role_ids":[]}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"name":"E-Mail","description":"mandatory","alias":"E-Mail","type":"varchar","options":"","default":"","behavior":"email"},{"id":17,"name":"Contact_ID","description":"","alias":"personID","type":"int","options":"","default":"","behavior":""}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"subject":"Optout Mailing","description":"Mailing with opt-outs","type":"standard","status":"done","sendingTime":"2019-05-09 13:43:00"}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":{"subject":"Optout Mailing","senderEmail":"no-reply@example.com","senderName":"Jane Doe","text":"Foobar","html":"<strong>Foobar</strong>","templateId":1,"folderId":4,"description":"Mailing with opt-outs","behavior":"standard","format":"multi","replyEmail":"service@example.com","openingRate":1,"trackingHtml":1,"trackingText":0,"inboundImages":0,"lineBreak":null,"scheduleStart":null,"limiterAmount":null,"limiterInterval":3600,"targetgroupId":["1"],"listId":[1],"status":"done","sendingTime":"2019-05-09 13:43:00"}}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"name":"E-Mail","description":"mandatory","alias":"E-Mail","type":"varchar","options":"","default":"","behavior":"email"},{"id":17,"name":"Contact_ID","description":"","alias":"personID","type":"int","options":"","default":"","behavior":""}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"email":null,"recipient":"1234","optoutSetup":"1","date":"2021-01-18 06:35:03","fields":[{"id":1,"value":"john@example.com","field":1}],"listId":[1]},{"email":1,"recipient":"1235","optoutSetup":"1","date":"2021-01-18 07:02:19","fields":[{"id":1,"value":"jane@example.com","field":1}],"listId":[1,2]}]}')
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push($history);
    // import folders and mailings first. this is a bit quick & dirty, but meh
    $processor = new CRM_Mailingwork_Processor_Greenpeace_Folders([
      'username' => 'at.greenpeace.mailingwork',
      'password' => 'hunter2',
    ], 'https://login.mailingwork.test/webservice/webservice/json/', $stack);
    $processor->import();
    $processor = new CRM_Mailingwork_Processor_Greenpeace_Mailings([
      'username' => 'at.greenpeace.mailingwork',
      'password' => 'hunter2',
    ], 'https://login.mailingwork.test/webservice/webservice/json/', $stack);
    $processor->import(FALSE);

    // create dummy contacts
    $firstContactId = reset($this->callAPISuccess('Contact', 'create', [
      'email'        => 'john@example.com',
      'contact_type' => 'Individual',
      'api.GroupContact.create' => [['group_id' => $this->groupId], ['group_id' => $this->altGroupId]]
    ])['values'])['id'];

    $firstContactDupeId = reset($this->callAPISuccess('Contact', 'create', [
      'email'        => 'john@example.com',
      'contact_type' => 'Individual',
      'api.GroupContact.create' => ['group_id' => $this->altGroupId]
    ])['values'])['id'];

    $secondContactId = reset($this->callAPISuccess('Contact', 'create', [
      'email'        => 'jane@example.com',
      'contact_type' => 'Individual',
      'api.GroupContact.create' => ['group_id' => $this->groupId]
    ])['values'])['id'];

    $processor = new CRM_Mailingwork_Processor_Greenpeace_Optouts([
      'username' => 'at.greenpeace.mailingwork',
      'password' => 'hunter2',
      'skip_mailing_sync' => TRUE
    ], 'https://login.mailingwork.test/webservice/webservice/json/', $stack);
    $processor->import();

    $this->assertEquals(
      2,
      $this->callAPISuccess('Activity', 'getcount', [
        'target_contact_id' => $firstContactId,
        'activity_type_id' => 'Optout',
        'subject' => ['LIKE' => 'Opt-Out from%'],
      ]),
      'Should have two opt-out group activities'
    );

    $this->assertEquals(
      1,
      $this->callAPISuccess('Activity', 'getcount', [
        'target_contact_id' => $firstContactDupeId,
        'activity_type_id' => 'Optout',
        'subject' => ['LIKE' => 'Opt-Out from%'],
      ]),
      'Should have one opt-out group activities'
    );

    $this->assertEquals(
      1,
      $this->callAPISuccess('Activity', 'getcount', [
        'target_contact_id' => $secondContactId,
        'activity_type_id' => 'Optout',
        'subject' => ['LIKE' => 'Opt-Out from%'],
      ]),
      'Should have one opt-out group activities'
    );

    $this->assertEquals(
      1,
      $this->callAPISuccess('GroupContact', 'getcount', [
        'status' => 'Removed',
        'group_id' => $this->groupId,
        'contact_id' => $firstContactId,
      ]),
      'Group should be removed'
    );

    $this->assertEquals(
      1,
      $this->callAPISuccess('GroupContact', 'getcount', [
        'status' => 'Removed',
        'group_id' => $this->altGroupId,
        'contact_id' => $firstContactDupeId,
      ]),
      'Group should be removed'
    );

    $this->assertEquals(
      0,
      $this->callAPISuccess('GroupContact', 'getcount', [
        'status' => 'Removed',
        'group_id' => $this->groupId,
        'contact_id' => $firstContactDupeId,
      ]),
      'Group should not be assigned with any status'
    );

    $this->assertEquals(
      1,
      $this->callAPISuccess('Activity', 'getcount', [
        'target_contact_id' => $secondContactId,
        'activity_type_id' => 'Optout',
        'subject' => ['LIKE' => 'Added%'],
      ]),
      'Should have one opt-out suppression activity'
    );

    $this->assertEquals(
      1,
      $this->callAPISuccess('Contact', 'getvalue', [
        'return' => 'is_opt_out',
        'id' => $secondContactId,
      ]),
      'is_opt_out should be set'
    );

  }

}
