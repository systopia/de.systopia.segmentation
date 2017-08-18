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
class CRM_Segmentation_Form_Task_Detach extends CRM_Contact_Form_Task {

  /**
   * Compile task form
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Detach %1 Contacts', array(
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
    $this->addElement('select',
                      'segment_list',
                      ts('Segment', array('domain' => 'de.systopia.segmentation')),
                      array('' => 'all'), // will be filled via AJAX
                      array('class' => 'huge'));

    CRM_Core_Form::addDefaultButtons("Detach");
  }

  /**
   * process results
   */
  function postProcess() {
    $values = $this->exportValues();

    if (!empty($this->_contactIds) && !empty($values['campaign_id'])) {
      $contact_id_list = implode(',', $this->_contactIds);
      $campaign = civicrm_api3('Campaign', 'getsingle', array(
        'id' => $values['campaign_id'],
        'return' => 'id,title'));

      if (empty($values['segment_list'])) {
        $segment_clause = 'TRUE';
      } else {
        $segment_id = (int) $values['segment_list'];
        $segment_clause = "segment_id = {$segment_id}";
      }

      // execute the delete
      CRM_Core_DAO::executeQuery("
        DELETE FROM `civicrm_segmentation`
        WHERE `entity_id` IN ({$contact_id_list})
          AND `campaign_id` = {$campaign['id']}
          AND {$segment_clause}");

      // create notice
      $variables = array(
        1 => count($this->_contactIds),
        2 => $campaign['title']);
      CRM_Core_Session::setStatus(ts("Detached %1 contacts from campaign '%2'", $variables), ts("Success"), "info");
    }
  }
}
