<?php

/**
 * Updates the order of the segment items
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_segmentation_sort($params) {
  $campaign = new CRM_Campaign_BAO_Campaign();
  $campaign->id = $params['campaign_id'];
  if (empty($campaign->find(TRUE))) {
    return civicrm_api3_create_error('Campaign(id=' . $params['campaign_id'] . ') does not exist.');
  }

  if (!is_array($params['new_order_of_segments'])) {
    return civicrm_api3_create_error('Field "new_order_of_segments" must be array of "segment" ids.');
  }

  $campaignId = $params['campaign_id'];
  $newOrderOfSegment = $params['new_order_of_segments'];
  $currentOrderOfSegment = CRM_Segmentation_Logic::getSegmentOrder($campaignId);

  $parsedNewOrderOfSegment = [];
  foreach ($newOrderOfSegment as $segmentId) {
    $parsedNewOrderOfSegment[] = (int) $segmentId;
  }

  if (_segmentation_is_valid_new_order_of_segment($currentOrderOfSegment, $parsedNewOrderOfSegment)) {
    return civicrm_api3_create_error('Field "new_order_of_segments" is invalid. The field must be array of "segment" ids.');
  }

  CRM_Segmentation_Logic::setSegmentOrder($campaignId, $parsedNewOrderOfSegment);

  $segments = CRM_Segmentation_SegmentationOrder::getSegmentationOrderByCampaignAndSegmentList($campaignId, $parsedNewOrderOfSegment);
  foreach (CRM_Segmentation_Logic::getSegmentCounts($campaignId, $parsedNewOrderOfSegment) as $segmentId => $segmentCount) {
    $segments[$segmentId]['count'] = $segmentCount;
  }
  foreach (CRM_Segmentation_Logic::getExcludedCounts($campaignId, $parsedNewOrderOfSegment) as $segmentId => $segmentCount) {
    $segments[$segmentId]['excluded_count'] = $segmentCount;
  }

  $reorderedSegments = [];
  foreach ($segments as $segmentId => $segment) {
    $segment_exclude        = CRM_Utils_Array::value('exclude',        $segment, 0);
    $segment_count          = CRM_Utils_Array::value('count',          $segment, 0);
    $segment_excluded_count = CRM_Utils_Array::value('excluded_count', $segment, 0);
    $reorderedSegments[] = [
      'segment_id' => $segmentId,
      'segment_count' => $segment['count'],
      'is_show_split_btn' => ($segment_exclude != 1 && $segment_count > 0 && $segment_excluded_count == 0) ? 1 : 0,
    ];
  }

  return civicrm_api3_create_success($reorderedSegments);
}

/**
 * Checks if entered(field 'new_order_of_segments') ids of segments and
 * ids of segments from database is equal
 *
 * @param $currentOrder
 * @param $newOrder
 *
 * @return bool
 */
function _segmentation_is_valid_new_order_of_segment($currentOrder, $newOrder) {
  sort($currentOrder);
  sort($newOrder);

  return $currentOrder != $newOrder;
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_segmentation_sort_spec(&$params) {
  $params['new_order_of_segments'] = [
    'name'         => 'new_order_of_segments',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'New order of segment',
    'description'  => 'Array of new segments(id) order. Example: [1, 2, 3].',
  ];

  $params['campaign_id'] = [
    'name'         => 'campaign_id',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Campaign id',
    'description'  => 'Campaign id',
  ];
}
