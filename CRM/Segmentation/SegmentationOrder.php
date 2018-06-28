<?php

/**
 * Utility function dealing with SegmentationOrders
 *
 * TODO: This is probably getting complex enough to warrant a proper BAO/DAO
 */
class CRM_Segmentation_SegmentationOrder {

  const BASE_QUERY = "SELECT
                        si.id AS segment_id,
                        so.id AS segmentation_order_id,
                        name,
                        order_number,
                        bundle,
                        text_block
                      FROM civicrm_segmentation_order so
                      INNER JOIN civicrm_segmentation_index si ON si.id=so.segment_id";

  public static function getSegmentationOrder($segmentationOrderId) {
    $query = CRM_Core_DAO::executeQuery(self::BASE_QUERY . " WHERE si.id = %0", [[$segmentationOrderId, 'Integer']]);
    $query->fetch();
    return (array) $query;
  }

  /**
   * @param $segment_ids list of segment orders to fetch
   * @param $campaign_id ID of campaign
   *
   * @return array segment_id, name, bundle, text_block, order
   */
  public static function getSegmentationOrderByCampaignAndSegmentList($campaignId, array $segmentIds) {
    $campaignId = (int) $campaignId;
    $segmentIdList = implode(',', array_map('intval', $segmentIds));
    if (empty($segmentIdList)) {
      return [];
    }

    $query = CRM_Core_DAO::executeQuery(self::BASE_QUERY . "
               WHERE so.campaign_id = {$campaignId} AND so.segment_id IN ({$segmentIdList})
               ORDER BY order_number ASC, so.id ASC");

    $segments = [];
    while ($query->fetch()) {
      $segments[$query->segment_id] = (array) $query;
    }
    return $segments;
  }

  public static function updateSegmentationOrder($segmentationOrderId, array $params) {
    $i = 0;
    $updateFields = [];
    if (isset($params['order_number'])) {
      $updateFields[] = "order_number = %{$i}";
      $dbData[$i] = [$params['order_number'], 'Integer'];
      $i++;
    }

    if (isset($params['bundle'])) {
      $updateFields[] = "bundle = %{$i}";
      $dbData[$i] = [$params['bundle'], 'String'];
      $i++;
    }

    if (isset($params['text_block'])) {
      $updateFields[] = "text_block = %{$i}";
      $dbData[$i] = [$params['text_block'], 'String'];
      $i++;
    }

    if (count($updateFields) == 0) {
      // nothing to do
      return self::getSegmentationOrder($segmentationOrderId);
    }

    $dbData[$i] = [$segmentationOrderId, 'Integer'];
    $updateQuery = implode(', ', $updateFields);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_segmentation_order
                                                SET {$updateQuery}
                                                WHERE id = %{$i}", $dbData);
    return self::getSegmentationOrder($segmentationOrderId);
  }

  public static function createSegmentationOrder(array $params) {
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_segmentation_order (campaign_id, segment_id, order_number, bundle, text_block) VALUES (%0, %1, %2, %3, %4)",
      [
        [$params['campaign_id'], 'Integer'],
        [$params['segment_id'], 'Integer'],
        [$params['order_number'], 'Integer'],
        [$params['bundle'], 'String'],
        [$params['text_block'], 'String'],
      ]
    );
    return current(CRM_Segmentation_SegmentationOrder::getSegmentationOrderByCampaignAndSegmentList($params['campaign_id'], [$params['segment_id']]));
  }

  /**
   * Check whether a segment name is in use in a campaign.
   *
   * Optionally, you can exclude a specific segment using $excludedSegmentId.
   *
   * @param $campaignId
   * @param $name
   * @param null $excludedSegmentId
   *
   * @return bool whether the segment name is available
   */
  public static function isSegmentNameAvailable($campaignId, $name, $excludedSegmentId = NULL) {
    $query = "SELECT COUNT(*) AS count FROM civicrm_segmentation_order
                INNER JOIN civicrm_segmentation_index ON civicrm_segmentation_index.id = civicrm_segmentation_order.segment_id
                WHERE campaign_id = %0 AND name = %1";
    $data = [[$campaignId, 'Integer'], [$name, 'String']];
    if (!is_null($excludedSegmentId)) {
      $query .= " AND segment_id != %2";
      $data[] = [$excludedSegmentId, 'Integer'];
    }
    return 0 == CRM_Core_DAO::singleValueQuery($query, $data);
  }

}
