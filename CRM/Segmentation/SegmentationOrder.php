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
  public static function getSegmentationOrderByCampaignAndSegmentList($campaignId, $segmentIds) {
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

  public static function updateSegmentationOrder($segmentationOrderId, $data) {
    $i = 0;
    $updateFields = [];
    if (isset($data['order_number'])) {
      $updateFields[] = "order_number = %{$i}";
      $dbData[$i] = [$data['order_number'], 'Integer'];
      $i++;
    }

    if (isset($data['bundle'])) {
      $updateFields[] = "bundle = %{$i}";
      $dbData[$i] = [$data['bundle'], 'String'];
      $i++;
    }

    if (isset($data['text_block'])) {
      $updateFields[] = "text_block = %{$i}";
      $dbData[$i] = [$data['text_block'], 'String'];
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

}
