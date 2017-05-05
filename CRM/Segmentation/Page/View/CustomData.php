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
 * Rendering adjustments to CRM_Contact_Page_View_CustomData
 */
class CRM_Segmentation_Page_View_CustomData {

  public static function adjustPage(CRM_Contact_Page_View_CustomData &$page, $extension_folder) {
    if (!$page->_contactId) {
      return;
    }

    // compile segments data
    $segments_query = civicrm_api3('Segmentation', 'segmentlist', array('contact_id' => $page->_contactId));
    $segments_details = $segments_query['values'];

    // compile campaign data
    $campaign_details = array();
    $campaign_status = array();
    $campaign_query = CRM_Core_DAO::executeQuery("
      SELECT
        civicrm_campaign.id        AS campaign_id,
        civicrm_campaign.title     AS campaign_title,
        civicrm_campaign.status_id AS campaign_status
        FROM civicrm_segmentation
       LEFT JOIN civicrm_campaign ON civicrm_campaign.id = civicrm_segmentation.campaign_id
       WHERE civicrm_segmentation.entity_id = %1
       GROUP BY civicrm_campaign.id;",
       array(1 => array($page->_contactId, 'Integer')));
    while ($campaign_query->fetch()) {
      $campaign_details[$campaign_query->campaign_id] = "{$campaign_query->campaign_title} [{$campaign_query->campaign_id}]";
      $campaign_status[$campaign_query->campaign_id] = $campaign_query->campaign_status;
    }

    // compile membership data
    $membership_details = array();
    $membership_query = CRM_Core_DAO::executeQuery("
      SELECT
        civicrm_membership.id AS membership_id,
        civicrm_membership_type.name AS type_name
        FROM civicrm_segmentation
       LEFT JOIN civicrm_membership ON civicrm_membership.id = civicrm_segmentation.membership_id
       LEFT JOIN civicrm_membership_type ON civicrm_membership.membership_type_id = civicrm_membership_type.id
       WHERE civicrm_segmentation.entity_id = %1
         AND civicrm_membership.id IS NOT NULL
       GROUP BY civicrm_membership.id;",
       array(1 => array($page->_contactId, 'Integer')));
    while ($membership_query->fetch()) {
      $membership_details[$membership_query->membership_id] = "{$membership_query->type_name} [{$membership_query->membership_id}]";
    }


    // inject script
    $script = file_get_contents("{$extension_folder}/js/adjust_segment_tab.js");
    $script = str_replace('SEGMENTS_DETAILS', json_encode($segments_details), $script);
    $script = str_replace('SEGMENTS_FIELD_ID', CRM_Segmentation_Configuration::getFieldID('segment_id'), $script);
    $script = str_replace('MEMBERSHIP_DETAILS', json_encode($membership_details), $script);
    $script = str_replace('MEMBERSHIP_FIELD_ID', CRM_Segmentation_Configuration::getFieldID('membership_id'), $script);
    $script = str_replace('CAMPAIGN_DETAILS',   json_encode($campaign_details), $script);
    $script = str_replace('CAMPAIGN_STATUS',   json_encode($campaign_status), $script);
    $script = str_replace('CAMPAIGN_FIELD_ID', CRM_Segmentation_Configuration::getFieldID('campaign_id'), $script);
    $script = str_replace('SEGMENT_GROUP_ID',   CRM_Segmentation_Configuration::groupID(), $script);
    CRM_Core_Region::instance('page-header')->add(array(
      'script' => $script,
      ));

    // inject CSS
    $css = file_get_contents("{$extension_folder}/css/adjust_segment_tab.css");
    CRM_Core_Region::instance('page-header')->add(array(
      'style' => $css,
      ));
  }
}