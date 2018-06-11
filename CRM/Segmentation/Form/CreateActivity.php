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

/** Show a warning if more the WARNING_COUNT activities should be indiviually created */
define('WARNING_COUNT', '250');


/**
 * Create Activity Form
 */
class CRM_Segmentation_Form_CreateActivity extends CRM_Core_Form {

  /** will store the campaign in question */
  protected $campaign = NULL;
  protected $total_count = NULL;

  /**
   * Build the create activity form
   */
  public function buildQuickForm() {
    // first: load campaign
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    if (!$cid) {
      CRM_Core_Session::setStatus(ts("No campaign ID (cid) given"), ts("Error"), "error");
      $error_url = CRM_Utils_System::url('civicrm/dashboard');
      CRM_Utils_System::redirect($error_url);
    }

    // load some stats
    $total_count_sql = CRM_Segmentation_Configuration::getContactCount($cid);
    $this->total_count = CRM_Core_DAO::singleValueQuery($total_count_sql);
    CRM_Utils_System::setTitle(ts("Create mass activity for %1 contacts", array(1 => $this->total_count)));
    if (!$this->total_count) {
      CRM_Core_Session::setStatus(ts("No contacts assigned to this campaign!"), ts("Warning"), "warn");
    }
    $this->assign('total_count',   $this->total_count);
    $this->assign('warning_count', WARNING_COUNT);

    // load campaign and data
    $this->campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $cid));
    $this->addElement('hidden', 'cid', $cid);

    // compile form
    $this->add(
      'select',
      'activity_type_id',
      ts('Activity Type'),
      $this->getActivityTypes(),
      FALSE,
      array('class' => 'crm-select2')
    );

    // compile form
    $this->add(
      'select',
      'mass_activity',
      ts('Kind of Activity'),
      $this->getActivityKinds(),
      FALSE,
      array('class' => 'huge')
    );

    $this->add(
      'text',
      'subject',
      ts('Activity Subject'),
      array('class' => 'huge'),
      TRUE // is required
    );

    $this->add(
      'select',
      'status_id',
      ts('Activity Status'),
      $this->getActivityStatuses(),
      TRUE // is required
    );

    $this->add(
      'select',
      'medium_id',
      ts('Activity Medium'),
      $this->getActivityMedia(),
      FALSE // is not required
    );

    $this->add(
      'select',
      'campaign_id',
      ts('Campaign'),
      $this->getActivityCampaigns(),
      FALSE,
      array('class' => 'crm-select2')
    );

    $this->addDate(
      'activity_date_time',
      ts('Date'),
      TRUE,
      array('formatType' => 'activityDateTime'));


    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Create'),
        'isDefault' => TRUE,
      ),
    ));


    $this->setDefaults();
    parent::buildQuickForm();
  }

  /**
   * define the default values
   */
  function setDefaults($defaultValues = null, $filter = null) {
    $defaults['subject']            = $this->campaign['title'];
    $defaults['campaign_id']        = $this->campaign['id'];
    $defaults['status_id']          = 2; // completed

    // set date default to now
    list($defaults['activity_date_time'], $defaults['activity_date_time_time'])
        = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');

    parent::setDefaults($defaults);
  }

  /**
   * process confirmation
   */
  public function postProcess() {
    $values = $this->exportValues();

    // compile activity data
    $activity_data = array();
    $activity_fields = array('activity_type_id', 'subject', 'status_id', 'medium_id', 'campaign_id');
    foreach ($activity_fields as $key) {
      if (isset($values[$key])) {
        $activity_data[$key] = $values[$key];
      }
    }

    // compile the date
    $activity_data['activity_date_time'] =
      date('YmdHis', strtotime("{$values['activity_date_time']} {$values['activity_date_time_time']}"));

    if (empty($values['mass_activity'])) {
      // create individual activities
      $sql_query = CRM_Segmentation_Configuration::getSegmentQuery($values['cid']);
      $query = CRM_Core_DAO::executeQuery($sql_query);
      while ($query->fetch()) {
        $activity_data['target_id'] = $query->contact_id;
        $activity = civicrm_api3('Activity', 'create', $activity_data);
      }
      // create popup
      CRM_Core_Session::setStatus(ts("%1 activities created.",
        array(1 => $this->total_count)), ts("Success"), "info");

    } else {
      // create mass activity
      // first: create activity
      $activity = civicrm_api3('Activity', 'create', $activity_data);

      // add all contacts
      if (!empty($values['cid']) && !empty($activity['id'])) {
        $query = "INSERT IGNORE INTO civicrm_activity_contact
                   (SELECT
                      NULL               AS id,
                      {$activity['id']}  AS activity_id,
                      civicrm_contact.id AS contact_id,
                      3                  AS record_type
                    FROM civicrm_segmentation
                    LEFT JOIN civicrm_contact ON civicrm_contact.id = civicrm_segmentation.entity_id
                    WHERE campaign_id = {$values['cid']}
                      AND civicrm_contact.is_deleted = 0)";
        CRM_Core_DAO::executeQuery($query);

        // keep track of segments in activities assigned to contacts
        // @TODO: order?
        $query = "INSERT IGNORE INTO civicrm_activity_contact_segmentation (activity_contact_id, segment_id)
                   (SELECT
                      civicrm_activity_contact.id,
                      civicrm_segmentation.segment_id
                    FROM civicrm_segmentation
                    INNER JOIN civicrm_activity_contact ON civicrm_segmentation.entity_id=civicrm_activity_contact.contact_id
                    WHERE campaign_id = {$values['cid']}
                      AND activity_id={$activity['id']})";
        CRM_Core_DAO::executeQuery($query);
      }
      // create popup
      $activity_edit_url = CRM_Utils_System::url('civicrm/activity/add', "atype=1&action=update&reset=1&id={$activity['id']}");
      CRM_Core_Session::setStatus(ts("New activity created for %1 contacts (<a href='%2'>edit</a>).",
        array(1 => $this->total_count, 2 => $activity_edit_url)), ts("Success"), "info");
    }


    parent::postProcess();

    // go back to where we came from
    // $session = CRM_Core_Session::singleton();
    // $toUrl = $session->popUserContext();
    // CRM_Utils_System::redirect($toUrl);
  }

  /**
   * get all active campaigns to select from
   */
  protected function getActivityCampaigns() {
    $campaign_list = array();
    $query = civicrm_api3('Campaign', 'get', array(
      'is_active'       => 1,
      'option.limit'    => 0,
      'return'          => 'id,title'));
    foreach ($query['values'] as $campaign) {
      $campaign_list[$campaign['id']] = $campaign['title'];
    }
    return $campaign_list;
  }

  /**
   * get options for activity kind (mass/individual)
   */
  protected function getActivityKinds() {
    return array(
      1 => ts("Mass: a single activity linked to all contacts"),
      0 => ts("Individual: one activity per contact"));
  }


  /**
   * get all eligible activity Types
   */
  protected function getActivityTypes() {
    return $this->getOptionValueList('activity_type');
  }
  /**
   * get all eligible activity Types
   */
  protected function getActivityStatuses() {
    return $this->getOptionValueList('activity_status');
  }

  /**
   * get all eligible activity Types
   */
  protected function getActivityMedia() {
    return $this->getOptionValueList('encounter_medium', TRUE);
  }

  /**
   * generic: create list from option values
   */
  protected function getOptionValueList($option_group_name, $noValue = FALSE) {
    if ($noValue) {
      $value_list = array('' => ts("None"));
    } else {
      $value_list = array();
    }

    $query = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => $option_group_name,
      'is_active'       => 1,
      'option.limit'    => 0,
      'return'          => 'value,label'));
    foreach ($query['values'] as $value) {
      $value_list[$value['value']] = $value['label'];
    }
    return $value_list;
  }
}
