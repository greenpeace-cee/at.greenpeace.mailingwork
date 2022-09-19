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
class CRM_Mailingwork_Processor_Greenpeace_RetainedEmailsTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $session = CRM_Core_Session::singleton();
    $session->set('userID', 1);
    $this->callApiSuccess('OptionValue', 'create', [
      'option_group_id' => 'activity_type',
      'name'      => 'contact_updated',
      'label'     => 'Contact Updated',
      'is_active' => 1,
    ]);
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test that retained emails are processed
   */
  public function testRetainedEmails() {
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
      new Response(200, [], '{"error":0,"message":"successfully executed","result":{"total":"3","data":[{"address":"foo@example.com"},{"address":"bar@example.org"},{"address":"unknown@example.org"}]}}'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push($history);
    $processor = new CRM_Mailingwork_Processor_Greenpeace_RetainedEmails([
      'username'   => 'at.greenpeace.mailingwork',
      'password'   => 'hunter2',
      'soft_limit' => 0,
    ], 'https://webservice.mailingwork.test/webservice/webservice/json/', $stack);
    $result = $processor->import();
    $this->assertEquals(3, $result['activities']);
    $this->assertEquals(3, $result['emails']);
    $emails = $this->callAPISuccess('Email', 'get', [
      'return' => ['on_hold', 'hold_date'],
      'email' => ['IN' => ['foo@example.com', 'bar@example.org']],
    ]);
    $this->assertCount(3, $emails['values']);
    foreach ($emails['values'] as $email) {
      $this->assertEquals(1, $email['on_hold']);
      $this->assertNotEmpty($email['hold_date']);
    }
    $activityEmails = $this->callAPISuccess('ActivityContactEmail', 'get', [
      'email' => ['IN' => ['foo@example.com', 'bar@example.org']],
    ]);
    $this->assertCount(3, $activityEmails['values']);
    $activities = $this->callAPISuccess('Activity', 'get', [
      'activity_type_id' => "contact_updated",
      'subject' => "Email put on hold after too many bounces",
    ]);
    $this->assertCount(3, $activities['values']);

  }

}
