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

    // delete segment if requested
    $this->processDeleteCommand($campaign_id);

    // get segment order, process, store
    $segment_order  = CRM_Segmentation_Logic::getSegmentOrder($campaign_id);
    $segment_order = $this->processOrderCommands($segment_order);
    CRM_Segmentation_Logic::setSegmentOrder($campaign_id, $segment_order);

    // start the campaign if requested
    $start = CRM_Utils_Request::retrieve('start', 'String');
    if (!empty($start)) {
      CRM_Segmentation_Logic::startCampaign($campaign_id, $segment_order);
      $campaign_view_url = CRM_Utils_System::url("civicrm/a/#/campaign/{$campaign_id}/view");
      CRM_Utils_System::redirect($campaign_view_url);
    }


    // finally: calculate counts based on order, and render page
    $segment_counts = CRM_Segmentation_Logic::getSegmentCounts($campaign_id, $segment_order);
    $segment_titles = CRM_Segmentation_Logic::getSegmentTitles($segment_order);
    $this->assign('segment_order',  $segment_order);
    $this->assign('segment_counts', $segment_counts);
    $this->assign('segment_titles', $segment_titles);
    $this->assign('campaign', $campaign);
    $this->assign('baseurl', $base_url);
    $this->assign('campaign_id', $campaign['id']);
    $this->assign('total_count', array_sum($segment_counts));
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
   * Process a delete command if there is one,
   * deleting an entire segment
   */
  protected function processDeleteCommand($campaign_id) {
    if (empty($campaign_id)) return;

    $segment_id = (int) CRM_Utils_Request::retrieve('delete', 'Integer');
    if ($segment_id) {
      // remove the segment links
      CRM_Core_DAO::executeQuery("
        DELETE FROM `civicrm_segmentation`
        WHERE `campaign_id` = {$campaign_id}
          AND `segment_id`  = {$segment_id}");

      CRM_Core_DAO::executeQuery("
        DELETE FROM `civicrm_segmentation_order`
        WHERE `campaign_id` = {$campaign_id}
          AND `segment_id`  = {$segment_id}");

      // create notice
      CRM_Core_Session::setStatus(ts("Segment sucessfully removed."), ts("Success"), "info");
    }
  }
}
