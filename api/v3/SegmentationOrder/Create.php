<?php
use CRM_Segmentation_ExtensionUtil as E;

/**
 * SegmentationOrder.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_segmentation_order_Create_spec(&$spec) {
  $spec = [
    'id' => [
      'title' => 'Segmentation Order ID',
      'api.required' => TRUE,
      'type' => CRM_Utils_Type::T_INT,
    ],
    'order_number' => [
      'title' => 'Order Number',
      'type' => CRM_Utils_Type::T_INT,
    ],
    'bundle' => [
      'title' => 'Bundle',
      'type' => CRM_Utils_Type::T_TEXT
    ],
    'text_block' => [
      'title' => 'Text Block',
      'type' => CRM_Utils_Type::T_TEXT
    ],
  ];
}

/**
 * SegmentationOrder.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_segmentation_order_Create($params) {
  if (empty($params['id'])) {
    return civicrm_api3_create_error('Creating new SegmentationOrder entities is not supported yet.');
  }
  return civicrm_api3_create_success(
    [CRM_Segmentation_SegmentationOrder::updateSegmentationOrder($params['id'], $params)],
    $params,
    'SegmentationOrder',
    'Create'
  );
}
