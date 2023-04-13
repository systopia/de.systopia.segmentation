<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SegmentationOrder.Create API Test Case
 *
 * @group headless
 */
class api_v3_SegmentationOrder_CreateTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  private $_campaignId;
  private $_segmentId;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->_campaignId = $this->callApiSuccess('Campaign', 'create', [
      'title' => 'Test Campaign',
    ])['id'];

    $this->_segmentId = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 1',
    ])['id'];
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testUpdate() {
    CRM_Segmentation_Logic::setSegmentOrder($this->_campaignId, [$this->_segmentId]);

    $segment_order_id = CRM_Core_DAO::singleValueQuery(
      "SELECT id AS count FROM civicrm_segmentation_order
                                          WHERE campaign_id=%0 AND segment_id=%1",
      [[$this->_campaignId, 'Integer'], [$this->_segmentId, 'Integer']]
    );

    $segmentationOrder = $this->callApiSuccess(
      'SegmentationOrder',
      'Create',
      [
        'id' => $segment_order_id,
        'order_number' => 2,
        'bundle' => '1',
        'text_block' => 'example test block',
      ]
    );

    $this->assertEquals(
      1,
      CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE segment_id=%0 AND order_number=2 AND bundle='1' AND text_block='example test block'",
        [[$this->_segmentId, 'Integer']]
      ),
      'SegmentationOrder.Create should update SegmentationOrder with supplied values'
    );

    $this->assertEquals(1, $segmentationOrder['count'], 'SegmentationOrder.Create should return one SegmentationOrder');
  }

}
