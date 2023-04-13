<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SegmentationOrder.Split API Test Case
 *
 * @group headless
 */
class api_v3_SegmentationOrder_SplitTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\Api3TestTrait;

  private $_campaignId;

  private $_firstSegmentId;

  private $_secondSegmentId;

  private $_segmentOrderId;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->_campaignId = $this->callApiSuccess('Campaign', 'create', [
      'title' => 'Test Campaign',
    ])['id'];
    $this->_firstSegmentId = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 1',
    ])['id'];
    $this->_secondSegmentId = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 2',
    ])['id'];
    CRM_Segmentation_Logic::setSegmentOrder($this->_campaignId, [
      $this->_firstSegmentId,
      $this->_secondSegmentId,
    ]);
    $this->_segmentOrderId = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_segmentation_order
      WHERE campaign_id=%0 AND segment_id=%1",
      [[$this->_campaignId, 'Integer'], [$this->_firstSegmentId, 'Integer']]
    );
    // create and assign 10 contacts to each segment
    for ($i = 0; $i < 20; $i++) {
      $contactId = $this->callApiSuccess('Contact', 'create', [
        'contact_type' => 'Individual',
        'first_name' => 'test' . $i,
      ])['id'];
      if ($i < 10) {
        $segmentId = $this->_firstSegmentId;
      }
      else {
        $segmentId = $this->_secondSegmentId;
      }
      CRM_Core_DAO::executeQuery(
        "INSERT INTO civicrm_segmentation (entity_id, datetime, campaign_id, segment_id) VALUES (%0, NOW(), %1, %2)",
        [
          [$contactId, 'Integer'],
          [$this->_campaignId, 'Integer'],
          [$segmentId, 'Integer'],
        ]
      );
    }
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testBucket() {
    $this->callApiSuccess(
      'SegmentationOrder',
      'split',
      [
        'id' => $this->_segmentOrderId,
        'buckets' => [
          'Test Segment 1 / A',
          'Test Segment 1 / B',
          'Test Segment 1 / C',
        ],
      ]
    );
    $firstBucket = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 1 / A',
    ])['id'];

    $secondBucket = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 1 / B',
    ])['id'];

    $thirdBucket = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'Test Segment 1 / C',
    ])['id'];

    $counts = CRM_Segmentation_Logic::getSegmentCounts($this->_campaignId);

    $this->assertEquals(3, $counts[$firstBucket], 'First bucket should have 3 contacts');
    $this->assertEquals(3, $counts[$secondBucket], 'Second bucket should have 3 contacts');
    $this->assertEquals(4, $counts[$thirdBucket], 'Third bucket should have 4 contacts');
    $this->assertEquals(10, $counts[$this->_secondSegmentId], 'Original (second) segment should have 10 contacts');
    $this->assertEquals(4, count($counts), 'Four segments should have assigned contacts');
    $this->assertEquals(4, count(CRM_Segmentation_Logic::getSegmentOrder($this->_campaignId)), 'Four segments should be assigned to campaign');
  }

  public function testBucketWithDuplicateSegment() {
    $this->callApiFailure(
      'SegmentationOrder',
      'split',
      [
        'id' => $this->_segmentOrderId,
        'buckets' => [
          'Test Segment 1',
          'Test Segment 2',
        ],
      ],
      'Segment with name Test Segment 2 already exists in this campaign.'
    );
  }

  public function testBucketWithDuplicateBucket() {
    $this->callApiFailure(
      'SegmentationOrder',
      'split',
      [
        'id' => $this->_segmentOrderId,
        'buckets' => [
          'Test Segment Duplicate',
          'Test Segment Duplicate',
        ],
      ],
      'Bucket names are not unique.'
    );
  }

  public function testExclusionTotal() {
    $this->callApiSuccess(
      'SegmentationOrder',
      'split',
      [
        'id' => $this->_segmentOrderId,
        'exclude_total' => 2,
      ]
    );

    $counts = CRM_Segmentation_Logic::getSegmentCounts($this->_campaignId);
    $this->assertEquals(8, $counts[$this->_firstSegmentId], 'First segment should have 8 contacts');
    $this->assertEquals(10, $counts[$this->_secondSegmentId], 'Second segment should have 10 contacts');
    $this->assertEquals(2, count($counts), 'Two segments should have assigned contacts');
    $this->assertEquals(2, count(CRM_Segmentation_Logic::getSegmentOrder($this->_campaignId)), 'Two segments should be assigned to campaign');
  }

  public function testExclusionPercentage() {
    $this->callApiSuccess(
      'SegmentationOrder',
      'split',
      [
        'id' => $this->_segmentOrderId,
        'exclude_percentage' => 30,
      ]
    );

    $counts = CRM_Segmentation_Logic::getSegmentCounts($this->_campaignId);
    $this->assertEquals(7, $counts[$this->_firstSegmentId], 'Segment should have 7 contacts');
  }

  public function testExclusionPercentageWithUpperLimitNotHit() {
    $this->callApiSuccess(
      'SegmentationOrder',
      'split',
      [
        'id' => $this->_segmentOrderId,
        'exclude_total' => 5,
        'exclude_percentage' => 20,
      ]
    );

    $counts = CRM_Segmentation_Logic::getSegmentCounts($this->_campaignId);
    $this->assertEquals(8, $counts[$this->_firstSegmentId], 'Segment should have 8 contacts');
  }

  public function testExclusionPercentageWithUpperLimitHit() {
    $this->callApiSuccess(
      'SegmentationOrder',
      'split',
      [
        'id' => $this->_segmentOrderId,
        'exclude_total' => 4,
        'exclude_percentage' => 60,
      ]
    );

    $counts = CRM_Segmentation_Logic::getSegmentCounts($this->_campaignId);
    $this->assertEquals(6, $counts[$this->_firstSegmentId], 'Segment should have 6 contacts');
  }
}
