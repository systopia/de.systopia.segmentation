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
 * This page manages starting a campaign,
 * i.e. chaging the status from "planned" to "running"
 *   which includes considating and freezing the segments.
 */
class CRM_Segmentation_Page_Start extends CRM_Core_Page {

  public function run() {
    $campaign_id = CRM_Utils_Request::retrieve('cid', 'Integer');
    $error_url = CRM_Utils_System::url('civicrm/dashboard');
    $base_url  = CRM_Utils_System::url('civicrm/segmentation/start', "cid={$campaign_id}");

    if (!$campaign_id) {
      CRM_Core_Session::setStatus(ts("No campaign ID (cid) given"), ts("Error"), "error");
      CRM_Utils_System::redirect($error_url);
    }

    // load campaign
    $campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $campaign_id));

    // load segments
    $used_segments  = self::getCampaignSegments($campaign_id);

    // get segment order
    $session = CRM_Core_Session::singleton();
    $segment_order = $session->get("segmentation_order_{$campaign_id}");
    if (empty($segment_order)) {
      // default is simply all segments in random order
      $segment_order = array_keys($used_segments);
    } else {
      // make sure all segments are in
      foreach ($used_segments as $segment_id => $value) {
        if (!in_array($segment_id, $segment_order)) {
          $segment_order[] = $segment_id;
        }
      }
    }

    // process commands to change order
    $segment_order = $this->processOrderCommands($segment_order);

    // store in session to keep alive
    $session->set("segmentation_order_{$campaign_id}", $segment_order);

    // finally: calculate counts based on order, and render page
    $segment_counts = self::getSegmentCounts($campaign_id, $segment_order);
    $segment_titles = self::getSegmentTitles($segment_order);
    $this->assign('segment_order',  $segment_order);
    $this->assign('segment_counts', $segment_counts);
    $this->assign('segment_titles', $segment_titles);
    $this->assign('campaign', $campaign);
    $this->assign('baseurl', $base_url);
    $this->assign('campaign_id', $campaign['id']);
    CRM_Utils_System::setTitle(ts("Start Campaign '%1'", array(1 => $campaign['title'])));

    parent::run();
  }


  /**
   * process any ordering commands to modify the given segment_order
   *
   * @param $segment_order  the current segment order
   * @return the new segment order
   */
  protected function processOrderCommands($segment_order) {
    foreach (array('top', 'up', 'down', 'bottom') as $cmd) {
      $segment_id = CRM_Utils_Request::retrieve($cmd, 'Integer');
      $index = array_search($segment_id, $segment_order);
      if ($index !== FALSE) {
        switch ($cmd) {
          case 'top':
            $new_index = 0;
            break;
          case 'up':
            $new_index = max(0, $index-1);
            break;
          case 'down':
            $new_index = min(count($segment_order)-1, $index+1);
            break;
          default:
          case 'bottom':
            $new_index = count($segment_order)-1;
            break;
        }
        // copied from https://stackoverflow.com/questions/12624153/move-an-array-element-to-a-new-index-in-php
        $out = array_splice($segment_order, $index, 1);
        array_splice($segment_order, $new_index, 0, $out);
      }
    }
    return $segment_order;
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
