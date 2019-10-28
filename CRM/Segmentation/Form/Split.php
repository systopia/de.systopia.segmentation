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

  /**
   * Count of split segments
   *
   * @var int
   */
  const CUSTOM_SPLIT_SEGMENT_COUNT = 100;

  /**
   * Count of active segments
   *
   * @var int
   */
  const CUSTOM_SPLIT_ACTIVE_SEGMENTS_COUNT = 4;

  /**
   * Minimum segments which will be split
   * Used for validation
   *
   * @var int
   */
  const CUSTOM_SPLIT_MIN_SEGMENTS = 2;

  /**
   * Campaign
   *
   * @var array
   */
  protected $campaign = NULL;

  /**
   * Segmentation
   *
   * @var array
   */
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

    $segmentsCount = CRM_Segmentation_Logic::getSegmentCounts($cid);
    $segmentContactCount = (int) $segmentsCount[$sid];

    $this->campaign = civicrm_api3('Campaign', 'getsingle', ['id' => $cid]);
    $this->segment = civicrm_api3('Segmentation', 'getsingle', ['id' => $sid]);

    CRM_Utils_System::setTitle(ts('Split Segment %1', [$this->segment['name']]));

    $this->addElement('hidden', 'cid', $cid);
    $this->addElement('hidden', 'sid', $sid);

    $this->addRadio(
      'split_type',
      ts('Split Type'),
      [
        'a_b_test' => 'A/B Test',
        'a_b_main_test' => 'A/B/Main Test',
        'exclusion_test' => 'Exclusion Test',
        'custom' => 'Custom',
      ],
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
    $this->addFormRule(['CRM_Segmentation_Form_Split', 'formRule']);
    $this->addFormRule(['CRM_Segmentation_Form_Split', 'validateCustomSplit']);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Split'),
        'isDefault' => TRUE,
      ],
    ]);

    $this->assign('segmentContactCount', $segmentContactCount);
    $this->assign('minSplitSegments', self::CUSTOM_SPLIT_MIN_SEGMENTS);
    $this->assign('maxSplitSegments', $segmentContactCount);

    $this->addInputsForCustomSplit();

    CRM_Core_Resources::singleton()->addStyleFile('de.systopia.segmentation', 'css/split.css');

    $this->setDefaults();
    parent::buildQuickForm();
  }

  private function addInputsForCustomSplit() {
    for ($i = 0; $i < self::CUSTOM_SPLIT_SEGMENT_COUNT; $i++) {
      $this->add(
        'checkbox',
        'is_active_segment[' . $i . ']',
        'Is active segment (#' . $i . ')?'
      );

      $this->add(
        'text',
        'name_of_segment[' . $i . ']',
        'Mame of segment(#' . $i . ')',
        ['data-alphabet-char' => static::getAlphabetChar($i)]
      );

      $this->add(
        'text',
        'segment_count_contact_in_percents[' . $i . ']',
        'Segment count contact in percents(#' . $i . ')',
        ['class' => '.crm-segmentation-segment-percents-input .crm-segmentation-segment-input']
      );
      $this->addRule('segment_count_contact_in_percents[' . $i . ']', ts('Please enter a positive number'), 'positiveInteger');

      $this->add(
        'text',
        'segment_count_contact_in_number[' . $i . ']',
        'Segment count contact in number(#' . $i . ')',
        ['class' => '.crm-segmentation-segment-number-input .crm-segmentation-segment-input']
      );
      $this->addRule('segment_count_contact_in_number[' . $i . ']', ts('Please enter a positive number'), 'positiveInteger');
    }

    $this->add(
      'text',
      'segment_percents_errors',
      ts('Contact percentage errors'),
      ['style' => 'display: none;']
    );

    $this->add(
      'text',
      'segment_number_errors',
      ts('Contact number errors'),
      ['style' => 'display: none;']
    );

    $this->addRadio(
      'custom_split_mode',
      ts('Split mode:'),
      [
        'percent' => ts('Percentage of Contacts'),
        'number' => ts('Number of Contacts')
      ],
      [],
      NULL,
      TRUE
    );

    $this->assign('customSplitSegmentCount', self::CUSTOM_SPLIT_SEGMENT_COUNT);
    $this->assign('customSplitActiveSegmentCount', self::CUSTOM_SPLIT_ACTIVE_SEGMENTS_COUNT);
  }

  public static function formRule($fields) {
    $errors = [];

    // bucket test
    if ($fields['split_type'] == 'a_b_test') {
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

  /**
   * Validates custom split fields
   *
   * @param $fields
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public static function validateCustomSplit($fields) {
    if ($fields['split_type'] != 'custom') {
      return TRUE;
    }

    $splitMode = $fields['custom_split_mode'];
    if (empty($splitMode)) {
      $errors["custom_split_mode"] = ts('Chose split mode.');
    }

    $campaignId = CRM_Utils_Request::retrieve('cid', 'Integer');
    $segmentId = CRM_Utils_Request::retrieve('sid', 'Integer');
    $segmentsCount = CRM_Segmentation_Logic::getSegmentCounts($campaignId);
    $segmentContactCount = (int) $segmentsCount[$segmentId];

    $percentSum = 0;
    $countOfSegments = 0;
    $numberSum = 0;
    $segmentNames = [];

    if (!empty($fields['is_active_segment'])) {
      foreach ($fields['is_active_segment'] as $key => $value) {
        if ($value != 1) {continue;}
        $countOfSegments++;

        if (empty($fields['name_of_segment'][$key])) {
          $errors["name_of_segment[". $key ."]"] = ts('Please enter a segment name.');
        }


        if (!empty($fields['name_of_segment'][$key])) {
          if (in_array($fields['name_of_segment'][$key], $segmentNames)) {
            $errors["name_of_segment[". $key ."]"] = ts('Segment name must be unique.');
          } else {
            $segmentNames[] = $fields['name_of_segment'][$key];
          }
        }

        if (!empty($fields['name_of_segment'][$key]) && !CRM_Segmentation_SegmentationOrder::isSegmentNameAvailable($campaignId, $fields['name_of_segment'][$key], $segmentId)) {
          $errors["name_of_segment[". $key ."]"] = ts("This segment name already exists in this campaign.");
        }

        if (!empty($fields['name_of_segment'][$key]) && strlen($fields['name_of_segment'][$key]) > 255) {
          $errors["name_of_segment[". $key ."]"] = ts('Segment name may contain only 255 characters.');
        }

        if ($splitMode == 'percent') {
          if (empty((int) $fields['segment_count_contact_in_percents'][$key])) {
            $errors["segment_count_contact_in_percents[". $key ."]"] = ts('Please enter the percentage of contacts.');
          } else {
            $percentSum += (int) $fields['segment_count_contact_in_percents'][$key];
          }
        }

        if ($splitMode == 'number') {
          if (empty((int) $fields['segment_count_contact_in_number'][$key])) {
            $errors["segment_count_contact_in_number[". $key ."]"] = ts('Please enter the number of contacts.');
          }
          $numberSum += (int) $fields['segment_count_contact_in_number'][$key];
        }
      }
    }

    $percentModeErrors = '';
    $numberModeErrors = '';

    if ($countOfSegments > $segmentContactCount && $splitMode == 'percent') {
      $percentModeErrors .= ts('The number of segments must not be more than %1.', $segmentContactCount);
    }

    if ($countOfSegments > $segmentContactCount && $splitMode == 'number') {
      $numberModeErrors .= ts('The number of segments must not be more than %1.', $segmentContactCount);
    }

    if ($splitMode == 'percent' && $percentSum != 100) {
      $percentModeErrors .=  ts('Sum of the segment percentage must be 100.');
    }

    if ($countOfSegments < self::CUSTOM_SPLIT_MIN_SEGMENTS && $splitMode == 'number') {
      $numberModeErrors .= ts('The number of segments must be at least %1.', self::CUSTOM_SPLIT_MIN_SEGMENTS);
    }

    if ($countOfSegments >= self::CUSTOM_SPLIT_MIN_SEGMENTS && $splitMode == 'number') {
      if ($segmentContactCount !== $numberSum) {
        $numberModeErrors .= ts('The sum of contacts must be %1.', $segmentContactCount);
      }
    }

    if ($countOfSegments < self::CUSTOM_SPLIT_MIN_SEGMENTS && $splitMode == 'percent') {
      $percentModeErrors .= ts('The number of segments must be at least %1.', self::CUSTOM_SPLIT_MIN_SEGMENTS);
    }

    if (!empty($percentModeErrors))  {
      $errors["segment_percents_errors"] = $percentModeErrors;
    }

    if (!empty($numberModeErrors))  {
      $errors["segment_number_errors"] = $numberModeErrors;
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
    $defaults['split_type'] = 'a_b_test';
    $defaults['exclude_contacts_total'] = '1000';
    $defaults['exclude_contacts_percentage'] = '10';
    $defaults['custom_split_mode'] = 'percent';

    for ($i = 0; $i < self::CUSTOM_SPLIT_SEGMENT_COUNT; $i++) {
      $defaults['is_active_segment'][$i] = ($i < self::CUSTOM_SPLIT_ACTIVE_SEGMENTS_COUNT) ? '1' :'0';
      $defaults['name_of_segment'][$i] = $this->segment['name'] . ' / ' . static::getAlphabetChar($i);
      $defaults['segment_count_contact_in_percents'][$i] = '';
      $defaults['segment_count_contact_in_number'][$i] = '';
    }

    parent::setDefaults($defaults);
  }

  /**
   * Gets char + index from alphabet
   *
   * @param $number
   * @return mixed|string
   */
  public static function getAlphabetChar($number) {
    $alphabet = range('A', 'Z');

    $length = count($alphabet);
    if ($number >= $length) {
      $charIndex = (floor($number / $length) == 0) ? '' : floor($number / $length);
      $char = $alphabet[$number % $length] . $charIndex;
    } else {
      $char = $alphabet[$number];
    }

    return $char;
  }

  public function postProcess() {
    $values = $this->exportValues();
    $segmentOrderId = CRM_Segmentation_SegmentationOrder::getSegmentationOrderId($values['cid'], $values['sid']);
    $splitBuckets = [];

    if ($values['split_type'] == 'a_b_test') {
      foreach ($values['segment'] as $segment) {
        $splitBuckets[] = $segment;
      }
      civicrm_api3('SegmentationOrder', 'split', [
          'id'      => $segmentOrderId,
          'buckets' => $splitBuckets,
      ]);
    }

    if ($values['split_type'] == 'a_b_main_test') {
      foreach ($values['test_segment'] as $segment) {
        $splitBuckets[] = $segment;
      }
      civicrm_api3('SegmentationOrder', 'split', [
          'id'              => $segmentOrderId,
          'buckets'         => $splitBuckets,
          'test_percentage' => $values['test_percentage'],
      ]);
    }

    if ($values['split_type'] == 'exclusion_test') {
      civicrm_api3('SegmentationOrder', 'split', [
          'id'                 => $segmentOrderId,
          'exclude_total'      => $values['exclude_contacts_total'],
          'exclude_percentage' => $values['exclude_contacts_percentage'],
      ]);
    }

    if ($values['split_type'] == 'custom') {
      $segmentsData = $this->prepareSegmentsData($values);
      civicrm_api3('SegmentationOrder', 'custom_split', [
        'id' => $segmentOrderId,
        'new_segments_data' => $segmentsData['segments'],
        'mode' => $segmentsData['mode'],
      ]);
    }

    parent::postProcess();
    CRM_Core_Session::setStatus(ts('Segment successfully split.'), ts('Success'), 'info');
    $start_url = CRM_Utils_System::url('civicrm/segmentation/start', ['cid' => ((int) $values['cid'])]);
    CRM_Utils_System::redirect($start_url);
  }

  /**
   * Prepares segments data
   *
   * @param $values
   *
   * @return array
   */
  private function prepareSegmentsData($values) {
    $segments = [
      'mode' => $values['custom_split_mode'],
      'segments' => []
    ];

    foreach ($values['is_active_segment'] as $key => $value) {
      if ($value != 1) {continue;}
      $segments['segments'][]  = [
        'name' => $values['name_of_segment'][$key],
        'percent' => (int) !empty($values['segment_count_contact_in_percents'][$key]) ? $values['segment_count_contact_in_percents'][$key] : 0,
        'number' => (int) !empty($values['segment_count_contact_in_number'][$key]) ? $values['segment_count_contact_in_number'][$key] : 0,
      ];
    }

    return $segments;
  }

}
