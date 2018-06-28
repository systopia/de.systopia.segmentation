<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Segmentation.Get API Test Case
 *
 * @group headless
 */
class api_v3_Segmentation_GetTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  private $_segmentId;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->_segmentId = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 1',
    ])['id'];
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   */
  public function testGetExisting() {
    $segment = $this->callApiSuccess('Segmentation', 'Get', ['id' => $this->_segmentId]);
    $this->assertEquals('Test Segment 1', $segment['values'][0]['name'], 'Segmentation.Get should return segment with the correct name');
  }

}
