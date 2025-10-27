<?php

use CRM_Segmentation_ExtensionUtil as E;

/**
 * SegmentationOrder.Split API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_segmentation_order_split_spec(&$spec) {
  $spec = [
    'id' => [
      'title' => 'Segmentation Order ID',
      'api.required' => TRUE,
      'type' => CRM_Utils_Type::T_INT,
    ],
    'buckets' => [
      'title' => 'Buckets',
      'description' => "Array of bucket names",
    ],
    'exclude_total' => [
      'title' => 'Exclude Number of Contacts',
      'description' => "Total number of contacts to exclude. Serves as an upper limit if exclude_percentage is present.",
      'type' => CRM_Utils_Type::T_INT,
    ],
    'exclude_percentage' => [
      'title' => 'Exclude Percentage of Contacts',
      'description' => "Percentage of contacts to exclude. Can be capped via exclude_total.",
      'type' => CRM_Utils_Type::T_INT,
    ],
    'test_percentage' => [
        'title' => 'Test Percentage of Contacts',
        'description' => "Percentage of contacts to test on. The rest will be assigned to the last (Main) group.",
        'type' => CRM_Utils_Type::T_INT,
    ],
  ];
}

/**
 * Perform the BUCKET (equal) split
 * @param $params
 * @return array
 * @throws CRM_Core_Exception
 */
function _civicrm_api3_segmentation_order_split_buckets($params) {
  $segmentationOrder = CRM_Segmentation_SegmentationOrder::getSegmentationOrderData((int) $params['id']);
  if (empty($segmentationOrder)) {
    return civicrm_api3_create_error('SegmentationOrder(id=' . $params['id'] . ') does not exist.');
  }

  $campaign_id  = $segmentationOrder->campaign_id;
  $segment_id   = $segmentationOrder->segment_id;
  $order_number = $segmentationOrder->order_number;
  $bundle       = $segmentationOrder->bundle;
  $text_block   = $segmentationOrder->text_block;

  // are the bucket names unique?
  if (count($params['buckets']) !== count(array_unique($params['buckets']))) {
    return civicrm_api3_create_error("Bucket names are not unique.");
  }

  // are the bucket names already in use as segment names in this campaign?
  foreach ($params['buckets'] as $bucket) {
    if (!CRM_Segmentation_SegmentationOrder::isSegmentNameAvailable($campaign_id, $bucket, $segment_id)) {
      return civicrm_api3_create_error("Segment with name {$bucket} already exists in this campaign.");
    }
  }

  // make sure the segment order is clean
  CRM_Segmentation_Logic::verifySegmentOrder($campaign_id);

  $bucketCount = count($params['buckets']);

  CRM_Segmentation_Logic::moveSegmentOrderNumber($bucketCount, $campaign_id, $order_number);

  $counts = CRM_Segmentation_Logic::getSegmentCounts($campaign_id, CRM_Segmentation_Logic::getSegmentOrder($campaign_id));
  if (array_key_exists($segment_id, $counts)) {
    $countPerBucket = round($counts[$segment_id] / $bucketCount);
  }
  else {
    $countPerBucket = 0;
  }

  CRM_Segmentation_SegmentationOrder::delete($params['id']);

  $buckets = [];
  $i = 0;
  foreach ($params['buckets'] as $bucket) {
    $segment = civicrm_api3('Segmentation', 'getsegmentid', ['name' => $bucket]);
    $buckets[] = reset(
      civicrm_api3(
        'SegmentationOrder',
        'create',
        [
          'campaign_id' => $campaign_id,
          'segment_id' => $segment['id'],
          'order_number' => $order_number + $i,
          'bundle' => (string) $bundle,
          'text_block' => (string) $text_block,
        ]
      )['values']
    );

    $limitSql = '';
    $data = [
      [$segment['id'], 'Integer'],
      [$segment_id, 'Integer'],
      [$campaign_id, 'Integer'],
    ];
    // set limit for everything but last bucket
    if ($i + 1 != $bucketCount) {
      $limitSql = 'ORDER BY RAND() LIMIT %3';
      $data[] = [$countPerBucket, 'Integer'];
    }
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_segmentation
      SET segment_id=%0
      WHERE segment_id=%1 AND campaign_id=%2
      {$limitSql}",
      $data
    );
    $i++;
  }

  return civicrm_api3_create_success(
    [$buckets],
    $params,
    'SegmentationOrder',
    'Split'
  );
}

/**
 * Performs the A/B/Main split, where only a certain percentage gets pushed in the non-Main segment
 * @param $params
 * @return array
 * @throws CRM_Core_Exception
 */
function _civicrm_api3_segmentation_order_split_test_buckets($params) {
  $segmentationOrder = CRM_Segmentation_SegmentationOrder::getSegmentationOrderData((int) $params['id']);
  if (empty($segmentationOrder)) {
    return civicrm_api3_create_error('SegmentationOrder(id=' . $params['id'] . ') does not exist.');
  }

  $campaign_id  = $segmentationOrder->campaign_id;
  $segment_id   = $segmentationOrder->segment_id;
  $order_number = $segmentationOrder->order_number;
  $bundle       = $segmentationOrder->bundle;
  $text_block   = $segmentationOrder->text_block;

  // are the bucket names unique?
  if (count($params['buckets']) !== count(array_unique($params['buckets']))) {
    return civicrm_api3_create_error("Bucket names are not unique.");
  }

  // are the bucket names already in use as segment names in this campaign?
  foreach ($params['buckets'] as $bucket) {
    if (!CRM_Segmentation_SegmentationOrder::isSegmentNameAvailable($campaign_id, $bucket, $segment_id)) {
      return civicrm_api3_create_error("Segment with name {$bucket} already exists in this campaign.");
    }
  }

  // make sure the segment order is clean
  CRM_Segmentation_Logic::verifySegmentOrder($campaign_id);

  $bucketCount = count($params['buckets']);

  CRM_Segmentation_Logic::moveSegmentOrderNumber($bucketCount, $campaign_id, $order_number);

  $counts = CRM_Segmentation_Logic::getSegmentCounts($campaign_id, CRM_Segmentation_Logic::getSegmentOrder($campaign_id));
  if (array_key_exists($segment_id, $counts)) {
    $countPerBucket = round($counts[$segment_id] * $params['test_percentage'] / 100.0 / ($bucketCount - 1));
  }
  else {
    $countPerBucket = 0;
  }

  CRM_Segmentation_SegmentationOrder::delete($params['id']);

  $buckets = [];
  $i = 0;
  foreach ($params['buckets'] as $bucket) {
    $segment = civicrm_api3('Segmentation', 'getsegmentid', ['name' => $bucket]);
    $buckets[] = reset(
        civicrm_api3(
            'SegmentationOrder',
            'create',
            [
                'campaign_id'  => $campaign_id,
                'segment_id'   => $segment['id'],
                'order_number' => $order_number + $i,
                'bundle'       => (string)$bundle,
                'text_block'   => (string)$text_block,
            ]
        )['values']
    );

    $limitSql = '';
    $data = [
        [$segment['id'], 'Integer'],
        [$segment_id, 'Integer'],
        [$campaign_id, 'Integer'],
    ];
    // set limit for everything but last bucket
    if ($i + 1 != $bucketCount) {
      $limitSql = 'ORDER BY RAND() LIMIT %3';
      $data[] = [$countPerBucket, 'Integer'];
    }
    CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_segmentation
      SET segment_id=%0
      WHERE segment_id=%1 AND campaign_id=%2
      {$limitSql}",
        $data
    );
    $i++;
  }

  return civicrm_api3_create_success(
      [$buckets],
      $params,
      'SegmentationOrder',
      'Split'
  );
}

/**
 * Perform the exclusion of a subset
 * @param $params
 * @return array
 * @throws \Civi\Core\Exception\DBQueryException
 */
function _civicrm_api3_segmentation_order_split_exclude($params) {
  $segmentationOrder = CRM_Segmentation_SegmentationOrder::getSegmentationOrderData((int) $params['id']);
  if (empty($segmentationOrder)) {
    return civicrm_api3_create_error('SegmentationOrder(id=' . $params['id'] . ') does not exist.');
  }

  $campaign_id = $segmentationOrder->campaign_id;
  $segment_id = $segmentationOrder->segment_id;
  $temp_table  = "temp_exclude_{$params['id']}_" . microtime();

  // make sure the segment order is clean
  CRM_Segmentation_Logic::verifySegmentOrder($campaign_id);

  $counts = CRM_Segmentation_Logic::getSegmentCounts($campaign_id, CRM_Segmentation_Logic::getSegmentOrder($campaign_id));
  if (!array_key_exists($segment_id, $counts)) {
    return civicrm_api3_create_error("Cannot run exclusion test on segment without contacts.");
  }

  if (!empty($params['exclude_total']) && empty($params['exclude_percentage'])) {
    $limit = $params['exclude_total'];
  }
  elseif (empty($params['exclude_total']) && !empty($params['exclude_percentage'])) {
    $limit = round($params['exclude_percentage'] * $counts[$segment_id] / 100);
  }
  else {
    $limit = min(round($params['exclude_percentage'] * $counts[$segment_id] / 100), $params['exclude_total']);
  }

  CRM_Core_DAO::executeQuery(
    "CREATE TEMPORARY TABLE `{$temp_table}` AS
      SELECT entity_id as contact_id, membership_id, datetime as created_date
      FROM civicrm_segmentation
      WHERE segment_id = %0 AND campaign_id = %1
      ORDER BY RAND()
      LIMIT %2",
    [[$segment_id, 'Integer'], [$campaign_id, 'Integer'], [$limit, 'Integer']]
  );

  CRM_Core_DAO::executeQuery(
    "INSERT INTO civicrm_segmentation_exclude (campaign_id, segment_id, contact_id, membership_id, created_date)
      SELECT %0, %1, contact_id, membership_id, created_date
      FROM `{$temp_table}`",
    [[$campaign_id, 'Integer'], [$segment_id, 'Integer']]
  );

  CRM_Core_DAO::executeQuery(
    "DELETE FROM civicrm_segmentation
      WHERE segment_id = %0 AND campaign_id = %1 AND entity_id IN (SELECT contact_id FROM `{$temp_table}`)",
    [[$segment_id, 'Integer'], [$campaign_id, 'Integer']]
  );

  CRM_Core_DAO::executeQuery("DROP TEMPORARY TABLE IF EXISTS `{$temp_table}`;");

}

/**
 * SegmentationOrder.Split API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws CRM_Core_Exception
 */
function civicrm_api3_segmentation_order_split($params) {
  if (empty($params['id'])) {
    return civicrm_api3_create_error('Parameter "id" is required.');
  }

  if (!empty($params['buckets']) && (!empty($params['exclude_total']) || !empty($params['exclude_percentage']))) {
    return civicrm_api3_create_error('Parameters "buckets" and "exclude" are mutually exclusive.');
  }

  if (!empty($params['buckets']) && count($params['buckets']) > 1 && !empty($params['test_percentage'])) {
    return _civicrm_api3_segmentation_order_split_test_buckets($params);
  }

  if (!empty($params['buckets']) && count($params['buckets']) > 1) {
    return _civicrm_api3_segmentation_order_split_buckets($params);
  }

  if (!empty($params['exclude_total']) || !empty($params['exclude_percentage'])) {
    return _civicrm_api3_segmentation_order_split_exclude($params);
  }

  return civicrm_api3_create_error('One of "buckets" or "exclude" is required.');
}
