<?php
/*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2018 SYSTOPIA                            |
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

/**
 * Segment Split Form
 */
class CRM_Segmentation_Form_Split extends CRM_Core_Form {

  protected $campaign = NULL;

  protected $segment = NULL;

  public function buildQuickForm() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    if (!$cid) {
      CRM_Core_Session::setStatus(ts("No campaign ID (cid) given"), ts("Error"), "error");
      $error_url = CRM_Utils_System::url('civicrm/dashboard');
      CRM_Utils_System::redirect($error_url);
    }

    $sid = CRM_Utils_Request::retrieve('sid', 'Integer');
    if (!$sid) {
      CRM_Core_Session::setStatus(ts("No segment ID (sid) given"), ts("Error"), "error");
      $error_url = CRM_Utils_System::url('civicrm/dashboard');
      CRM_Utils_System::redirect($error_url);
    }

    $this->campaign = civicrm_api3('Campaign', 'getsingle', ['id' => $cid]);
    $this->segment = civicrm_api3('Segmentation', 'getsingle', ['id' => $sid]);

    CRM_Utils_System::setTitle(ts('Split Segment %1', [$this->segment['name']]));

    $this->addElement('hidden', 'cid', $cid);
    $this->addElement('hidden', 'sid', $sid);

    $this->addRadio(
      'split_type',
      ts('Split Type'),
      ['A/B Test', 'A/B/Main Test', 'Exclusion Test'],
      [],
      NULL,
      TRUE
    );

    // compile form
    $this->add(
      'text',
      'segment[0]',
      ts('Segment Name'),
      ['class' => 'huge']
    );

    $this->add(
      'text',
      'segment[1]',
      ts('Segment Name'),
      ['class' => 'huge']
    );


    $this->add(
        'text',
        'test_segment[0]',
        ts('Segment Name'),
        ['class' => 'huge']
    );

    $this->add(
        'text',
        'test_segment[1]',
        ts('Segment Name'),
        ['class' => 'huge']
    );

    $this->add(
        'text',
        'test_segment[2]',
        ts('Segment Name'),
        ['class' => 'huge']
    );

    $this->add(
        'text',
        'test_percentage',
        ts('A+B Percentage'),
        ['class' => 'two']
    );

    $this->add(
      'text',
      'exclude_contacts_total',
      ts('Exclude Contacts'),
      ['class' => 'eight']
    );

    $this->add(
      'text',
      'exclude_contacts_percentage',
      ts('Exclude Percentage'),
      ['class' => 'two']
    );

    $this->addRule("exclude_contacts_total", ts('Please enter a valid number.'), 'positiveInteger');
    $this->addRule("test_percentage", ts('Please enter a valid number.'), 'positiveInteger');
    $this->addRule("exclude_contacts_percentage", ts('Please enter a valid number.'), 'positiveInteger');
    $this->addFormRule(array('CRM_Segmentation_Form_Split', 'formRule'));

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Split'),
        'isDefault' => TRUE,
      ],
    ]);

    $this->setDefaults();
    parent::buildQuickForm();
  }

  public static function formRule($fields) {
    $errors = [];

    if ($fields['split_type'] == '0') {
      // bucket test
      foreach ($fields['segment'] as $key => $segment) {
        if (empty($segment)) {
          $errors["segment[{$key}]"] = ts('Please enter a segment name.');
        }
      }
    }
    else {
      // exclusion test
      if (empty($fields['exclude_contacts_total']) && empty($fields['exclude_contacts_percentage'])) {
        $errors["exclude_contacts_total"] = ts('Please provide a number or percentage of contacts to exclude.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  public function setDefaults($defaultValues = NULL, $filter = NULL) {
    $defaults['segment'][0] = $this->segment['name'] . ' / A';
    $defaults['segment'][1] = $this->segment['name'] . ' / B';
    $defaults['test_segment'][0] = 'A';
    $defaults['test_segment'][1] = 'B';
    $defaults['test_segment'][2] = 'Main';
    $defaults['test_percentage'] = '10';
    $defaults['split_type'] = '0';
    $defaults['exclude_contacts_total'] = '1000';
    $defaults['exclude_contacts_percentage'] = '10';
    parent::setDefaults($defaults);
  }

  public function postProcess() {
    $values = $this->exportValues();

    $segmentOrderId = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_segmentation_order
      WHERE campaign_id=%0 AND segment_id=%1",
      [[$values['cid'], 'Integer'], [$values['sid'], 'Integer']]
    );
    $splitBuckets = [];
    if ($values['split_type'] == '0') {
      // perform A/B test
      foreach ($values['segment'] as $segment) {
        $splitBuckets[] = $segment;
      }
      civicrm_api3('SegmentationOrder', 'split', [
          'id'      => $segmentOrderId,
          'buckets' => $splitBuckets,
      ]);
    }
    elseif ($values['split_type'] == '1') {
      // exclusion test
      foreach ($values['test_segment'] as $segment) {
        $splitBuckets[] = $segment;
      }
      civicrm_api3('SegmentationOrder', 'split', [
          'id'              => $segmentOrderId,
          'buckets'         => $splitBuckets,
          'test_percentage' => $values['test_percentage'],
      ]);
    }
    else {
      // exclusion test
      civicrm_api3('SegmentationOrder', 'split', [
          'id'                 => $segmentOrderId,
          'exclude_total'      => $values['exclude_contacts_total'],
          'exclude_percentage' => $values['exclude_contacts_percentage'],
      ]);
    }

    parent::postProcess();
    CRM_Core_Session::setStatus(ts('Segment successfully split.'), ts('Success'), 'info');
    $start_url = CRM_Utils_System::url('civicrm/segmentation/start', ['cid' => ((int) $values['cid'])]);
    CRM_Utils_System::redirect($start_url);
  }

}
