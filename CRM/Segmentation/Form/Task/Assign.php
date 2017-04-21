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

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Segmentation_Form_Task_Assign extends CRM_Contact_Form_Task {

  /**
   * Compile task form
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Assign %1 Contacts', array(
      'domain' => 'de.systopia.segmentation',
      1        => count($this->_contactIds))));

    // campaign selector
    $this->addElement('select',
                      'campaign_id',
                      ts('Campaign', array('domain' => 'de.systopia.segmentation')),
                      CRM_Segmentation_Form_Task_Assign::getCampaigns(),
                      array('class' => 'crm-select2 huge'));
    $this->addRule('campaign_id', ts('You have to select a campaign', array('domain' => 'de.systopia.segmentation')), 'required');

    // segment options
    $generic_segments = CRM_Segmentation_Form_Task_Assign::getGenericSegments();
    $this->assign('generic_segments', json_encode($generic_segments));
    $this->addElement('select',
                      'segment_list',
                      ts('Segment Suggestions', array('domain' => 'de.systopia.segmentation')),
                      $generic_segments,
                      array('class' => 'crm-select2 huge'));

    // segment field
    $this->addElement('text',
                      'segment',
                      ts('Segment', array('domain' => 'de.systopia.segmentation')),
                      array('class' => 'huge'));
    $this->addRule('segment', ts('Please select a segment or enter a new name.', array('domain' => 'de.systopia.segmentation')), 'required');

    CRM_Core_Form::addDefaultButtons("Assign");
  }

  /**
   * get all active segments
   */
  public static function getGenericSegments() {
    $segments = array();

    // then: add the other segments used in this campaign
    $query = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'segments',
      'is_active'       => '1',
      'option.limit'    => 0,
      'return'          => 'label'));
    foreach ($query['values'] as $value) {
      $segments[] = $value['label'];
    }

    return $segments;
  }

  /**
   * get all active campaigns
   */
  public static function getCampaigns() {
    $campaign_selection = array();
    $campaign_query = civicrm_api3('Campaign', 'get', array(
      'is_active'    => 1,
      'option.limit' => 0,
      'return'       => 'id,title'
      ));
    foreach ($campaign_query['values'] as $campaign) {
      $campaign_selection[$campaign['id']] = $campaign['title'];
    }

    return $campaign_selection;
  }

  function postProcess() {
    $values = $this->exportValues();

    // TODO: use API?
    if (!empty($this->_contactIds)) {
      $contact_id_list = implode(',', $this->_contactIds);
      CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id,datetime,campaign_id,segment,test_group,membership_id)
          SELECT civicrm_contact.id AS entity_id,
                 NOW()              AS datetime,
                 %1                 AS campaign_id,
                 %2                 AS segment,
                 NULL               AS test_group,
                 NULL               AS membership_id
          FROM civicrm_contact WHERE civicrm_contact.id IN ({$contact_id_list})",
          array(
            1 => array($values['campaign_id'], 'Integer'),
            2 => array($values['segment'],  'String'),
          )
        );
    }
  }
}
