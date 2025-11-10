<?php

/**
 * SegmentationOrder.get_segments API
 */
function civicrm_api3_segmentation_order_get_segments($params) {
  $sqlSelect = '
    SELECT 
        civicrm_segmentation_index.name as name,  
        civicrm_segmentation_order.segment_id as id
    FROM civicrm_segmentation_order
    INNER JOIN civicrm_segmentation_index 
        ON civicrm_segmentation_order.segment_id = civicrm_segmentation_index.id';
  $sqlWhere = '';

  if (!empty($params['campaign_id'])) {
    $sqlWhere .= ' ' . CRM_Core_DAO::composeQuery('civicrm_segmentation_order.campaign_id = %1', [
      1 => [$params['campaign_id'] , 'Integer']
    ]);
  }

  if (!empty($sqlWhere)) {
    $sqlWhere = 'WHERE' .  $sqlWhere;
  }

  $fullSql = $sqlSelect . ' ' . $sqlWhere;
  $query = CRM_Core_DAO::executeQuery($fullSql);

  $segments = [];
  while ($query->fetch()) {
    $segments[$query->id] = $query->name;
  }

  return civicrm_api3_create_success($segments);
}

/**
 * API specification
 * This is used for documentation and validation.
 * For SegmentationOrder.get_segments
 */
function _civicrm_api3_segmentation_order_get_segments_spec(&$params) {
  $params['campaign_id'] = [
    'name' => 'campaign_id',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
    'title' => 'Campaign id',
    'description'  => '',
  ];
}
