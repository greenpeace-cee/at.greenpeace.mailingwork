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
class CRM_Mailingwork_Processor_Greenpeace_FoldersTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
   * Test that folders are created with hierarchy
   */
  public function testFoldersCreated() {
    $container = [];
    $history = Middleware::history($container);
    $mock = new MockHandler([
      new Response(200, [], '{"error":0,"message":"successfully executed","result":[{"id":1,"parent_id":0,"name":"1: Parent_1","role_ids":[]},{"id":5,"parent_id":7,"name":"2.1: Child_1","role_ids":[]},{"id":7,"parent_id":0,"name":"2: Parent_2","role_ids":[]},{"id":2,"parent_id":1,"name":"1.1: Child_1","role_ids":[]},{"id":3,"parent_id":1,"name":"1.2: Child_2","role_ids":[]},{"id":4,"parent_id":7,"name":"2.2: Child_2","role_ids":[]}]}'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push($history);
    $processor = new CRM_Mailingwork_Processor_Greenpeace_Folders([
      'username' => 'at.greenpeace.mailingwork',
      'password' => 'hunter2',
    ], $stack);
    $result = $processor->import();
    // 6 folders in API response
    $this->assertEquals(6, $result['count']);
    // 6 folders via getcount
    $this->assertEquals(6, $this->callAPISuccess('MailingworkFolder', 'getcount', []));

    $parent_1 = $this->callAPISuccess('MailingworkFolder', 'getsingle', [
      'name' => '1: Parent_1',
    ]);
    $this->assertEquals(1, $parent_1['mailingwork_identifier']);
    // root folder should not have a parent
    $this->assertArrayNotHasKey('parent_id', $parent_1);

    $parent_1_child_1 = $this->callAPISuccess('MailingworkFolder', 'getsingle', [
      'name' => '1.1: Child_1',
    ]);
    $this->assertEquals(2, $parent_1_child_1['mailingwork_identifier']);
    // $parent_1 should be parent of $parent_1_child_1
    $this->assertEquals($parent_1['id'], $parent_1_child_1['parent_id']);

    $parent_2 = $this->callAPISuccess('MailingworkFolder', 'getsingle', [
      'name' => '2: Parent_2',
    ]);
    $this->assertEquals(7, $parent_2['mailingwork_identifier']);
    // root folder should not have a parent
    $this->assertArrayNotHasKey('parent_id', $parent_2);

    $parent_2_child_2 = $this->callAPISuccess('MailingworkFolder', 'getsingle', [
      'name' => '2.1: Child_1',
    ]);
    $this->assertEquals(5, $parent_2_child_2['mailingwork_identifier']);
    // $parent_2 should be parent of $parent_2_child_2
    $this->assertEquals($parent_2['id'], $parent_2_child_2['parent_id']);

  }

}
