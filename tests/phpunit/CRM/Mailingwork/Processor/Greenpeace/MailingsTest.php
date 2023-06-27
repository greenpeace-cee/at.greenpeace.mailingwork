<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test Folders API Processor
 *
 * @group headless
 */
class CRM_Mailingwork_Processor_Greenpeace_MailingsTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test that mailings are created
   */
  public function testMailingsCreated() {
    $container = [];
    $history = Middleware::history($container);
    $mock = new MockHandler([
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"name":"E-Mail","description":"mandatory","alias":"E-Mail","type":"varchar","options":"","default":"","behavior":"email"},{"id":17,"name":"Contact_ID","description":"","alias":"personID","type":"int","options":"","default":"","behavior":""}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"parent_id":0,"name":"1: Parent_1","role_ids":[]},{"id":5,"parent_id":7,"name":"2.1: Child_1","role_ids":[]},{"id":7,"parent_id":0,"name":"2: Parent_2","role_ids":[]},{"id":2,"parent_id":1,"name":"1.1: Child_1","role_ids":[]},{"id":3,"parent_id":1,"name":"1.2: Child_2","role_ids":[]},{"id":4,"parent_id":7,"name":"2.2: Child_2","role_ids":[]}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"name":"E-Mail","description":"mandatory","alias":"E-Mail","type":"varchar","options":"","default":"","behavior":"email"},{"id":17,"name":"Contact_ID","description":"","alias":"personID","type":"int","options":"","default":"","behavior":""}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"subject":"Save the Prince of Whales","description":"Save the ... Prince of Whales?","type":"standard","status":"drafted","sendingTime":null},{"id":2,"subject":"Testmailing2","description":"Second testmailing","type":"campaign","status":"done","sendingTime":"2019-05-09 13:43:00"}]}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":{"subject":"Save the Prince of Whales","senderEmail":"no-reply@example.com","senderName":"Jane Doe","text":"Foobar","html":"<strong>Foobar</strong>","templateId":1,"folderId":4,"description":"Save the ... Prince of Whales?","behavior":"standard","format":"multi","replyEmail":"service@example.com","openingRate":1,"trackingHtml":1,"trackingText":0,"inboundImages":0,"lineBreak":null,"scheduleStart":null,"limiterAmount":null,"limiterInterval":3600,"targetgroupId":["1"],"listId":[1],"status":"drafted"}}'),
      new Response(200, [], '{"error":0,"message":"successfully executed","result":{"subject":"Testmailing2","senderEmail":"no-reply@example.com","senderName":"Jane Doe","text":"Foobar","html":"<strong>Foobar</strong>","templateId":1,"folderId":0,"description":"Second testmailing","behavior":"campaign","format":"multi","replyEmail":"service@example.com","openingRate":1,"trackingHtml":1,"trackingText":0,"inboundImages":0,"lineBreak":null,"scheduleStart":"2019-05-09 13:43:00","limiterAmount":null,"limiterInterval":3600,"targetgroupId":["1"],"listId":[1],"status":"done"}}'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push($history);

    // import folders first. handled here because mocking gets painful otherwise
    $processor = new CRM_Mailingwork_Processor_Greenpeace_Folders([
      'username' => 'at.greenpeace.mailingwork',
      'password' => 'hunter2',
    ], 'https://webservice.mailingwork.test/webservice/webservice/json/', $stack);
    $processor->import();

    $processor = new CRM_Mailingwork_Processor_Greenpeace_Mailings([
      'username' => 'at.greenpeace.mailingwork',
      'password' => 'hunter2',
    ], 'https://login.mailingwork.test/webservice/webservice/json/', $stack);
    $result = $processor->import(FALSE);
    // 2 mailings in API response
    $this->assertEquals(2, $result['count']);
    $this->assertEquals(2, $result['new']);
    $this->assertEquals(0, $result['existing']);
    // 2 mailings via getcount
    $this->assertEquals(2, $this->callAPISuccess('MailingworkMailing', 'getcount', []));

    $folder = $this->callAPISuccess('MailingworkFolder', 'getvalue', [
      'mailingwork_identifier' => 4,
      'return'                 => 'id',
    ]);

    $status_drafted = CRM_Core_PseudoConstant::getKey(
      'CRM_Mailingwork_BAO_MailingworkMailing',
      'status_id',
      'drafted'
    );

    $type_standard = CRM_Core_PseudoConstant::getKey(
      'CRM_Mailingwork_BAO_MailingworkMailing',
      'type_id',
      'standard'
    );

    $mailing_1 = $this->callAPISuccess('MailingworkMailing', 'getsingle', [
      'subject' => 'Save the Prince of Whales',
    ]);

    $this->assertEquals(1, $mailing_1['mailingwork_identifier']);
    $this->assertEquals('Save the ... Prince of Whales?', $mailing_1['description']);
    $this->assertEquals('Jane Doe', $mailing_1['sender_name']);
    $this->assertEquals('no-reply@example.com', $mailing_1['sender_email']);
    $this->assertEquals($folder, $mailing_1['mailingwork_folder_id']);
    $this->assertEquals($status_drafted, $mailing_1['status_id']);
    $this->assertEquals($type_standard, $mailing_1['type_id']);

    $mailing_2 = $this->callAPISuccess('MailingworkMailing', 'getsingle', [
      'subject' => 'Testmailing2',
    ]);
    $this->assertArrayNotHasKey('mailingwork_folder_id', $mailing_2);

  }

}
