<?php

use CRM_Segmentation_ExtensionUtil as E;

/**
 * SegmentationOrder.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_segmentation_order_create_spec(&$spec) {
  $spec = [
    'id' => [
      'title' => 'Segmentation Order ID',
      'type' => CRM_Utils_Type::T_INT,
    ],
    'campaign_id' => [
      'title' => 'Campaign',
      'type' => CRM_Utils_Type::T_INT,
    ],
    'segment_id' => [
      'title' => 'Segment',
      'type' => CRM_Utils_Type::T_INT,
    ],
    'order_number' => [
      'title' => 'Order Number',
      'type' => CRM_Utils_Type::T_INT,
    ],
    'bundle' => [
      'title' => 'Bundle',
      'type' => CRM_Utils_Type::T_TEXT,
      'api.default' => '',
    ],
    'text_block' => [
      'title' => 'Text Block',
      'type' => CRM_Utils_Type::T_TEXT,
      'api.default' => '',
    ],
  ];
}

/**
 * SegmentationOrder.Create API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_segmentation_order_create($params) {
  if (empty($params['id'])) {
    $data = CRM_Segmentation_SegmentationOrder::createSegmentationOrder($params);
  }
  else {
    $data = CRM_Segmentation_SegmentationOrder::updateSegmentationOrder($params['id'], $params);
  }
  return civicrm_api3_create_success(
    [$data],
    $params,
    'SegmentationOrder',
    'Create'
  );
}
