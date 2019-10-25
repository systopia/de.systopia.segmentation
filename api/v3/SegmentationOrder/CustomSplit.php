<?php

/**
 * Custom split of segments
 *
 * @param array $params
 *
 * @return array
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_segmentation_order_custom_split($params) {
  $segmentationOrder = CRM_Segmentation_SegmentationOrder::getSegmentationOrderData((int) $params['id']);
  if (empty($segmentationOrder)) {
    return civicrm_api3_create_error('SegmentationOrder(id=' . $params['id'] . ') does not exist.');
  }

  if (!($params['mode'] == 'number' || $params['mode'] == 'percent')) {
    return civicrm_api3_create_error('Invalid value for \'mode\' field. Available values: \'percent\' or \'number\'.');
  }

  if (!is_array($params['new_segments_data']) || (is_array($params['new_segments_data']) && count($params['new_segments_data']) == 0)) {
    return civicrm_api3_create_error('The \'new_segments_data\' field must be array with segments data.');
  }

  foreach ($params['new_segments_data'] as $segment) {
    if (!isset($segment['number'])
      || ($params['mode'] == 'number' && !isset($segment['number']))
      || ($params['mode'] == 'percent' && !isset($segment['percent']))){
      $message = 'The \'new_segments_data\' field must be array with segments data.';
      $message .= 'The data must contain fields: "name" and "number"/"percent"(depends on mode).';
      return civicrm_api3_create_error($message);
    }
  }

  $segmentCounts = CRM_Segmentation_Logic::getSegmentCounts($segmentationOrder->campaign_id, CRM_Segmentation_Logic::getSegmentOrder($segmentationOrder->campaign_id));
  if (!isset($segmentCounts[$segmentationOrder->segment_id])) {
    return civicrm_api3_create_error("Segment does not have any contacts.");
  }

  $segmentCountOfContact = $segmentCounts[$segmentationOrder->segment_id];

  $segmentNames = [];
  $sumOfContacts = 0;
  foreach ($params['new_segments_data'] as $segment) {
    if (!CRM_Segmentation_SegmentationOrder::isSegmentNameAvailable($segmentationOrder->campaign_id, $segment['name'], $segmentationOrder->segment_id)) {
      return civicrm_api3_create_error("Segment with name {$segment['name']} already exists in this campaign.");
    }
    if ($params['mode'] == 'number') {
      $sumOfContacts += $segment['number'];
    }
    $segmentNames[] = $segment['name'];
  }

  if ($params['mode'] == 'number' && $sumOfContacts > $segmentCounts) {
    return civicrm_api3_create_error("Sum of contacts can not be more than " . $segmentCountOfContact);
  }

  if (count($segmentNames) !== count(array_unique($segmentNames))) {
    return civicrm_api3_create_error(ts("Segment names are not unique."));
  }

  CRM_Segmentation_Logic::verifySegmentOrder($segmentationOrder->campaign_id);
  $segmentCount = count($params['new_segments_data']);
  CRM_Segmentation_Logic::moveSegmentOrderNumber($segmentCount, $segmentationOrder->campaign_id, $segmentationOrder->order_number);
  CRM_Segmentation_SegmentationOrder::delete($params['id']);

  if ($params['mode'] == 'percent') {
    $params['new_segments_data'] = CRM_Segmentation_Logic::convertPercentToCountOfContact($params['new_segments_data'], $segmentCountOfContact);
  }

  $splitSegments = [];
  $i = 0;
  foreach ($params['new_segments_data'] as $segment) {
    $segmentation = civicrm_api3('Segmentation', 'getsegmentid', ['name' => $segment['name']]);
    $splitSegments[] = reset(
      civicrm_api3('SegmentationOrder', 'create',
        [
          'campaign_id' => $segmentationOrder->campaign_id,
          'segment_id' => $segmentation['id'],
          'order_number' => $segmentationOrder->order_number + $i,
          'bundle' => (string) $segmentationOrder->bundle,
          'text_block' => (string) $segmentationOrder->text_block,
        ]
      )['values']
    );

    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_segmentation
      SET segment_id = %0
      WHERE segment_id = %1 AND campaign_id = %2
      ORDER BY RAND() LIMIT %3
      ",
      [
        [$segmentation['id'], 'Integer'],
        [$segmentationOrder->segment_id, 'Integer'],
        [$segmentationOrder->campaign_id, 'Integer'],
        [$segment['number'], 'Integer']
      ]);
    $i++;
  }

  return civicrm_api3_create_success([$splitSegments], $params, 'SegmentationOrder', 'custom_split');
}

/**
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_segmentation_order_custom_split_spec(&$spec) {
  $spec = [
    'id' => [
      'title' => 'Segmentation Order ID',
      'description' => ts('Segmentation Order ID'),
      'api.required' => 1,
      'type' => CRM_Utils_Type::T_INT,
    ],
    'new_segments_data' => [
      'title' => 'New segments data',
      'description' => ts(' The \'new_segments_data\' field must be array with segments data. The data must contain fields: "name" and "number"/"percent"(depends on mode).'),
      'api.required' => 1,
      'type' => CRM_Utils_Type::T_STRING,
    ],
    'mode' => [
      'title' => 'Split mode',
      'description' => ts('Split mode. Available values: \'percent\' or \'number\'.'),
      'api.required' => 1,
      'type' => CRM_Utils_Type::T_STRING,
      'options'      => [
        'percent'  => 'percent',
        'number'  => 'number',
      ],
    ],
  ];
}
