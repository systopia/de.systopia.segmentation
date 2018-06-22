<?php

use CRM_Segmentation_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests utility functions in CRM_Segmentation_Logic
 *
 * @group headless
 */
class CRM_Segmentation_LogicTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  private $_campaignId;
  private $_segments;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->_campaignId = $this->callApiSuccess('Campaign', 'create', [
      'title' => 'Test Campaign',
      'status_id' => 1,
    ])['id'];

    $this->_segments[] = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 1',
    ])['id'];
    $this->_segments[] = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 2',
    ])['id'];
    $this->_segments[] = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 3',
    ])['id'];

    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testSegmentOrderCreate() {
    CRM_Segmentation_Logic::setSegmentOrder($this->_campaignId, $this->_segments);
    foreach ($this->_segments as $segment_order => $segment_id) {
      $segment_order++;
      $this->assertEquals(
        1,
        CRM_Core_DAO::singleValueQuery(
          "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE segment_id=%0 AND order_number=%1",
          [[$segment_id, 'Integer'], [$segment_order, 'Integer']]
        ),
        "CRM_Segmentation_Logic::setSegmentOrder should create segment_id={$segment_id} with order_number={$segment_order}"
      );
    }

    $this->assertEquals(
      3,
      CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE campaign_id=%0",
        [[$this->_campaignId, 'Integer']]
      ),
      'CRM_Segmentation_Logic::setSegmentOrder should create three segmentation order entries'
    );
  }

  public function testSegmentOrderModify() {
    // start with 3 segments
    CRM_Segmentation_Logic::setSegmentOrder($this->_campaignId, $this->_segments);

    // change order
    shuffle($this->_segments);

    CRM_Segmentation_Logic::setSegmentOrder($this->_campaignId, $this->_segments);

    foreach ($this->_segments as $segment_order => $segment_id) {
      $segment_order++;
      $this->assertEquals(
        1,
        CRM_Core_DAO::singleValueQuery(
          "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE segment_id=%0 AND order_number=%1",
          [[$segment_id, 'Integer'], [$segment_order, 'Integer']]
        ),
        "CRM_Segmentation_Logic::setSegmentOrder should update segment_id={$segment_id} to order_number={$segment_order}"
      );
    }

    $this->assertEquals(
      3,
      CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE campaign_id=%0",
        [[$this->_campaignId, 'Integer']]
      ),
      'CRM_Segmentation_Logic::setSegmentOrder should only have stored three segmentation order entries after changing order'
    );
  }

  public function testSegmentOrderDelete() {
    // start with 3 segments
    CRM_Segmentation_Logic::setSegmentOrder($this->_campaignId, $this->_segments);

    // remove middle segment
    $deleted_segment = $this->_segments[1];
    unset($this->_segments[1]);
    $this->_segments = array_values($this->_segments);

    CRM_Segmentation_Logic::setSegmentOrder($this->_campaignId, $this->_segments);

    foreach ($this->_segments as $segment_order => $segment_id) {
      $segment_order++;
      $this->assertEquals(
        1,
        CRM_Core_DAO::singleValueQuery(
          "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE segment_id=%0 AND order_number=%1",
          [[$segment_id, 'Integer'], [$segment_order, 'Integer']]
        ),
        "CRM_Segmentation_Logic::setSegmentOrder should update segment_id={$segment_id} to order_number={$segment_order}"
      );
    }

    $this->assertEquals(
      0,
      CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE segment_id=%0",
        [[$deleted_segment, 'Integer']]
      ),
      "CRM_Segmentation_Logic::setSegmentOrder should delete segmentation order with segment_id={$deleted_segment}"
    );

    $this->assertEquals(
      2,
      CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                                          WHERE campaign_id=%0",
        [[$this->_campaignId, 'Integer']]
      ),
      'CRM_Segmentation_Logic::setSegmentOrder should only have stored two segmentation order entries after deleting one'
    );
  }

}
