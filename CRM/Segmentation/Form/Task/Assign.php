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
    CRM_Utils_System::setTitle(ts('Assign to Campaign', array('domain' => 'de.systopia.segmentation')));

    // campaign selector
    $this->addElement('select',
                      'campaign_id',
                      ts('Campaign', array('domain' => 'de.systopia.segmentation')),
                      $this->getCampaigns(),
                      array('class' => 'crm-select2'));

    // campaign selector
    $this->addElement('select',
                      'segment_id',
                      ts('Segment', array('domain' => 'de.systopia.segmentation')),
                      $this->getSegments(),
                      array('class' => 'crm-select2'));

    CRM_Core_Form::addDefaultButtons("Assign");
  }

  /**
   * get all active segments
   */
  protected function getSegments() {
    return CRM_Core_OptionGroup::values('segments');
  }

  /**
   * get all active campaigns
   */
  protected function getCampaigns() {
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
    foreach ($this->_contactIds as $contact_id) {
      CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id,datetime,campaign_id,segment_id)
          VALUES (%1, NOW(), %2, %3);",
          array(
            1 => array($contact_id,            'Integer'),
            2 => array($values['campaign_id'], 'Integer'),
            3 => array($values['segment_id'],  'String'),
          )
        );
    }
  }
}
