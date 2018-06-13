<?php

use CRM_Segmentation_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests the creation of ActivityContact segmentation rows
 * (civicrm_activity_contact_segmentation) in various scenarios
 *
 * @group headless
 */
class CRM_Segmentation_ActivityContactTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  private $_campaignId;
  private $_segmentId;
  private $_sourceContactId;
  private $_activityId;

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
      'name' => 'Test Segment',
    ])['id'];

    CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id,datetime,campaign_id,segment_id,test_group,membership_id)
          SELECT civicrm_contact.id AS entity_id,
                 NOW()              AS datetime,
                 %0                 AS campaign_id,
                 %1                 AS segment_id,
                 NULL               AS test_group,
                 NULL               AS membership_id
          FROM civicrm_contact WHERE civicrm_contact.id IN (1, 2)",
      [
        [$this->_campaignId, 'Integer'],
        [$this->_segmentId, 'Integer'],
      ]
    );

    $this->_sourceContactId = $this->callApiSuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Dummy source contact',
    ])['id'];

    $this->_activityId = $this->callApiSuccess('Activity', 'create', [
      'campaign_id' => $this->_campaignId,
      'activity_type_id' => 1,
      'source_contact_id' => $this->_sourceContactId,
    ])['id'];

    // add segement to order
    CRM_Segmentation_Logic::addSegmentToCampaign($this->_segmentId, $this->_campaignId);
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testActivityContactCreate() {
    $this->callApiSuccess('ActivityContact', 'create', [
      'activity_id' => $this->_activityId,
      'contact_id' => 1,
    ]);

    $query = CRM_Core_DAO::executeQuery("SELECT COUNT(*) AS count FROM civicrm_activity_contact_segmentation acs
                             INNER JOIN civicrm_activity_contact ac ON ac.id = acs.activity_contact_id
                             WHERE ac.contact_id = 1 AND ac.activity_id = {$this->_activityId}");
    $query->fetch();
    $this->assertEquals(1, $query->count, 'ActivityContact.create should create segmentation');

    $query = CRM_Core_DAO::executeQuery("SELECT COUNT(*) AS count FROM civicrm_activity_contact_segmentation acs
                             INNER JOIN civicrm_activity_contact ac ON ac.id = acs.activity_contact_id
                             WHERE ac.contact_id = 2 AND ac.activity_id = {$this->_activityId}");
    $query->fetch();
    $this->assertEquals(0, $query->count, 'ActivityContact.create with contact_id=1 should not create segmentation contact_id=2');
  }

  public function testActivityCreateWithContact() {
    $activity_id = $this->callApiSuccess('Activity', 'create', [
      'campaign_id' => $this->_campaignId,
      'activity_type_id' => 1,
      'source_contact_id' => $this->_sourceContactId,
      'target_id' => 2
    ])['id'];

    $query = CRM_Core_DAO::executeQuery("SELECT COUNT(*) AS count FROM civicrm_activity_contact_segmentation acs
                             INNER JOIN civicrm_activity_contact ac ON ac.id = acs.activity_contact_id
                             WHERE ac.contact_id = 2 AND ac.activity_id = {$activity_id}");
    $query->fetch();
    $this->assertEquals(1, $query->count, 'Activity.create with target_id should create segmentation');

    $query = CRM_Core_DAO::executeQuery("SELECT COUNT(*) AS count FROM civicrm_activity_contact_segmentation acs
                             INNER JOIN civicrm_activity_contact ac ON ac.id = acs.activity_contact_id
                             WHERE ac.contact_id = 1 AND ac.activity_id = {$activity_id}");
    $query->fetch();
    $this->assertEquals(0, $query->count, 'Activity.create with target_id=2 should not create segmentation for contact_id=1');
  }

  public function testMassActivity() {
    $query = "INSERT IGNORE INTO civicrm_activity_contact
                   (SELECT
                      NULL               AS id,
                      {$this->_activityId}  AS activity_id,
                      civicrm_contact.id AS contact_id,
                      3                  AS record_type
                    FROM civicrm_segmentation
                    LEFT JOIN civicrm_contact ON civicrm_contact.id = civicrm_segmentation.entity_id
                    WHERE campaign_id = {$this->_campaignId}
                      AND civicrm_contact.is_deleted = 0)";
    CRM_Core_DAO::executeQuery($query);

    CRM_Segmentation_Logic::addSegmentForMassActivity($this->_activityId, $this->_campaignId);

    $query = CRM_Core_DAO::executeQuery("SELECT COUNT(*) AS count FROM civicrm_activity_contact_segmentation acs
                             INNER JOIN civicrm_activity_contact ac ON ac.id = acs.activity_contact_id
                             WHERE ac.activity_id = {$this->_activityId}");
    $query->fetch();
    $this->assertEquals(2, $query->count, 'CRM_Segmentation_Logic::addSegmentForMassActivity should create segmentation for two contacts');
  }

}
