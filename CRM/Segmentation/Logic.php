<?php
/*-------------------------------------------------------+
| SYSTOPIA Contact Segmentation Extension                |
| Copyright (C) 2017 SYSTOPIA                            |
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
 * Basic configuration hub
 */
class CRM_Segmentation_Logic {

  /**
   * will do the following:
   *   - Set the status to 'In Progress'
   *   - If the campaign's start date is in the past, it will be set to now
   *   - Freeze the contact segments, i.e. each contact is only linked with the highest segment as of the table below.
   */
  public static function startCampaign($campaign_id, $segment_order) {
    $campaign = civicrm_api3('Campaign', 'getsingle', array('campaign_id' => $campaign_id));
    if ($campaign['status_id'] != 1) {
      throw new Exception("Only campaigns with status 'planned' [1] can be started.");
    }

    // call another function to consolidate
    self::consolidateSegments($campaign_id, $segment_order);

    // finally: update campaign
    $campaign_update = array(
      'id'        => $campaign_id,
      'status_id' => 2);
    if (  empty($campaign['start_date'])
       || strtotime($campaign['start_date']) < strtotime('now')) {
      $campaign_update['start_date'] = date('YmdHis');
    }
    civicrm_api3('Campaign', 'create', $campaign_update);
  }

  /**
   * delete all inferior (according to the given order) entries for contacts
   */
  public static function consolidateSegments($campaign_id, $segment_order) {
    $segments_settled = array();
    foreach ($segment_order as $segment_id) {
      if (!empty($segments_settled)) {
        // for each step delete the contact entries that already
        //  in one of the settled segments
        $segments_settled_list = implode(',', $segments_settled);
        CRM_Core_DAO::executeQuery("
          DELETE FROM civicrm_segmentation
          WHERE civicrm_segmentation.campaign_id = {$campaign_id}
            AND civicrm_segmentation.segment_id  = {$segment_id}
            AND civicrm_segmentation.entity_id IN (SELECT * FROM
                  (SELECT settled.entity_id
                     FROM civicrm_segmentation settled
                    WHERE settled.campaign_id = {$campaign_id}
                      AND settled.segment_id IN ({$segments_settled_list})
                  ) tmpdata )");

      }
      $segments_settled[] = $segment_id;
    }

    $segments_to_exclude = array(0);
    foreach ($segment_order as $segment_id) {
      $exclude_list = implode(',', $segments_to_exclude);
      $segment_count = CRM_Core_DAO::singleValueQuery("
        SELECT COUNT(DISTINCT(positive.entity_id))
        FROM civicrm_segmentation positive
        LEFT JOIN civicrm_segmentation negative ON positive.entity_id = negative.entity_id
                                               AND negative.campaign_id = {$campaign_id}
                                               AND negative.segment_id IN ({$exclude_list})
        WHERE positive.segment_id = {$segment_id}
          AND positive.campaign_id = {$campaign_id}
          AND negative.segment_id IS NULL");
      $segment_counts[$segment_id] = $segment_count;
      $segments_to_exclude[] = $segment_id;
    }
    return $segment_counts;

  }

  /**
   * get a list segment_id -> contact_count for the given campaign
   *
   * @todo optimise into one query?
   * @todo move to another class
   */
  public static function getSegmentCounts($campaign_id, $segment_order) {
    $segment_counts = array();
    $segments_to_exclude = array(0);
    foreach ($segment_order as $segment_id) {
      $exclude_list = implode(',', $segments_to_exclude);
      $segment_count = CRM_Core_DAO::singleValueQuery("
        SELECT COUNT(DISTINCT(positive.entity_id))
        FROM civicrm_segmentation positive
        LEFT JOIN civicrm_segmentation negative ON positive.entity_id = negative.entity_id
                                               AND negative.campaign_id = {$campaign_id}
                                               AND negative.segment_id IN ({$exclude_list})
        WHERE positive.segment_id = {$segment_id}
          AND positive.campaign_id = {$campaign_id}
          AND negative.segment_id IS NULL");
      $segment_counts[$segment_id] = $segment_count;
      $segments_to_exclude[] = $segment_id;
    }
    return $segment_counts;
  }

  /**
   * get a list segment_id -> segment_title for the given segment ids
   *
   * @todo move to another class
   */
  public static function getSegmentTitles($segment_ids) {
    if (empty($segment_ids)) return array();

    $segment_id_list = implode(',', $segment_ids);
    $query = CRM_Core_DAO::executeQuery("
      SELECT
        id    AS segment_id,
        name  AS segment_title
      FROM civicrm_segmentation_index
      WHERE id IN ({$segment_id_list})");

    $segment_titles = array();
    while ($query->fetch()) {
      $segment_titles[$query->segment_id] = $query->segment_title;
    }
    return $segment_titles;
  }

  /**
   * get a list segment_id -> contact_count for the given campaign
   *
   * @todo move to another class
   */
  public static function getCampaignSegments($campaign_id) {
    $query = CRM_Core_DAO::executeQuery("
      SELECT
        segment_id                 AS segment_id,
        COUNT(DISTINCT(entity_id)) AS contact_count
      FROM civicrm_segmentation
      WHERE segment_id IS NOT NULL
        AND campaign_id = %1
      GROUP BY segment_id", array(1 => array($campaign_id, 'Integer')));

    $result = array();
    while ($query->fetch()) {
      $result[$query->segment_id] = $query->contact_count;
    }
    return $result;
  }
}
