<?php
/*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

define('ASSIGN_MEMBERSHIP__PREVIEW_SAMPLE_SIZE', 500);

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Segmentation_Form_Task_AssignContactMembership extends CRM_Contact_Form_Task {

  /**
   * Compile task form
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts("Assign Contacts' Memberships", ['domain' => 'de.systopia.segmentation']));

    // campaign selector
    $this->addElement('select',
                      'campaign_id',
                      ts('Campaign', ['domain' => 'de.systopia.segmentation']),
                      CRM_Segmentation_Form_Task_Assign::getCampaigns(),
                      ['class' => 'crm-select2 huge']);
    $this->addRule('campaign_id', ts('You have to select a campaign', ['domain' => 'de.systopia.segmentation']), 'required');

    // segment options
    $generic_segments = CRM_Segmentation_Form_Task_Assign::getGenericSegments();
    $this->assign('generic_segments', json_encode($generic_segments));
    $this->addElement('select',
                      'segment_list',
                      ts('Segment Suggestions', ['domain' => 'de.systopia.segmentation']),
                      $generic_segments,
                      ['class' => 'crm-select2 huge']);

    // segment field
    $this->addElement('text',
                      'segment',
                      ts('Segment', ['domain' => 'de.systopia.segmentation']),
                      ['class' => 'huge']);
    $this->addRule('segment', ts('Please select a segment or enter a new name.', ['domain' => 'de.systopia.segmentation']), 'required');

    // add membership types
    $this->addElement('select',
                      'membership_type_id',
                      ts('Membership Type', ['domain' => 'de.systopia.segmentation']),
                      self::getMembershipTypes(),
                      ['class' => 'crm-select2 huge', 'multiple' => 'multiple']);

    // add membership types
    $this->addElement('select',
                      'membership_status_id',
                      ts('Membership Status', ['domain' => 'de.systopia.segmentation']),
                      self::getMembershipStatuses(),
                      ['class' => 'crm-select2 huge', 'multiple' => 'multiple']);


    // add segments URL
    $group_id = CRM_Segmentation_Configuration::segmentsGroupID();
    $this->assign('segments_url', CRM_Utils_System::url('civicrm/admin/options', "reset=1&gid={$group_id}"));

    // create a lookup sample
    $sample = [];
    $count  = count($this->_contactIds);
    if ($count > ASSIGN_MEMBERSHIP__PREVIEW_SAMPLE_SIZE) {
      $indexes = array_rand ($this->_contactIds, ASSIGN_MEMBERSHIP__PREVIEW_SAMPLE_SIZE);
      foreach ($indexes as $index) {
        $sample[] = $this->_contactIds[$index];
      }
    } else {
      $sample = $this->_contactIds;
    }
    $this->assign('contact_sample', json_encode($sample));
    $this->assign('contact_sample_complete', (int) ($count <= ASSIGN_MEMBERSHIP__PREVIEW_SAMPLE_SIZE));
    $this->assign('contact_sample_factor', $count / ASSIGN_MEMBERSHIP__PREVIEW_SAMPLE_SIZE);
    $this->assign('contact_count',  $count);

    CRM_Core_Form::addDefaultButtons("Assign Memberships");
  }


  /**
   * Execute
   */
  function postProcess() {
    $values = $this->exportValues();

    if (!empty($this->_contactIds)) {
      // look up segment ID
      $segment = civicrm_api3('Segmentation', 'getsegmentid', [
        'name' => $values['segment']]);

      // derive status clause
      if (!empty($values['membership_status_id']) && is_array($values['membership_status_id'])) {
        $status_list = implode(',', $values['membership_status_id']);
        $membership_status_clause = "civicrm_membership.status_id IN ($status_list)";
      } else {
        $membership_status_clause = "TRUE";
      }

      // derive type clause
      if (!empty($values['membership_type_id']) && is_array($values['membership_type_id'])) {
        $type_list = implode(',', $values['membership_type_id']);
        $membership_type_clause = "civicrm_membership.membership_type_id IN ($type_list)";
      } else {
        $membership_type_clause = "TRUE";
      }

      $contact_id_list = implode(',', $this->_contactIds);
      CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id,datetime,campaign_id,segment_id,test_group,membership_id)
          SELECT civicrm_membership.contact_id AS entity_id,
                 NOW()                         AS datetime,
                 %1                            AS campaign_id,
                 %2                            AS segment_id,
                 NULL                          AS test_group,
                 civicrm_membership.id         AS membership_id
          FROM civicrm_membership
          WHERE civicrm_membership.contact_id IN ({$contact_id_list})
            AND {$membership_status_clause}
            AND {$membership_type_clause}",
          [
            1 => [$values['campaign_id'], 'Integer'],
            2 => [$segment['id'],         'Integer'],
          ]
        );

      // add segement to order
      CRM_Segmentation_Logic::addSegmentToCampaign($segment['id'], $values['campaign_id']);
    }
  }


  /**
   * Return a dropdown list of all membership statuses
   */
  protected static function getMembershipStatuses() {
    $statuses = [];
    $query = civicrm_api3('MembershipStatus', 'get', [
      'is_active'    => 1,
      'return'       => 'id,label',
      'option.limit' => 0]);
    foreach ($query['values'] as $membership_status) {
      $statuses[$membership_status['id']] = $membership_status['label'];
    }
    return $statuses;
  }

  /**
   * Return a dropdown list of all membership statuses
   */
  protected static function getMembershipTypes() {
    $types = [];
    $query = civicrm_api3('MembershipType', 'get', [
      'is_active'    => 1,
      'return'       => 'id,name',
      'option.limit' => 0]);
    foreach ($query['values'] as $membership_type) {
      $types[$membership_type['id']] = $membership_type['name'];
    }
    return $types;
  }
}
