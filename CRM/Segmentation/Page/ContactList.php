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
 * Renders a simple list of contacts belonging to one campaign
 * and -optionally- one segment
 */
class CRM_Segmentation_Page_ContactList extends CRM_Core_Page {

  public function run() {
    $campaign_id  = CRM_Utils_Request::retrieve('cid', 'Integer');
    $segment_id   = CRM_Utils_Request::retrieve('sid', 'Integer');
    $snippet_mode = CRM_Utils_Request::retrieve('snippet', 'Integer');

    if (!$campaign_id) {
      CRM_Core_Session::setStatus(ts("No campaign ID (cid) given"), ts("Error"), "error");
      $error_url = CRM_Utils_System::url('civicrm/dashboard');
      CRM_Utils_System::redirect($error_url);
    }

    if ($segment_id) {
      $segments = array($segment_id);
      $segment_titles = CRM_Segmentation_Logic::getSegmentTitles($segments);
      $this->assign('segment_name', reset($segment_titles));
    } else {
      $segments = array();
    }

    // load campaign
    $campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $campaign_id));

    // load contacts
    $query_sql = CRM_Segmentation_Configuration::getContactQuery($campaign_id, $segments);
    $query = CRM_Core_DAO::executeQuery($query_sql);
    $contacts = array();
    while ($query->fetch()) {
      $contacts[] = array(
        'contact_id'   => $query->contact_id,
        'display_name' => $query->display_name,
        'contact_type' => $query->contact_type,
        'is_deleted'   => $query->is_deleted,
        // 'segment_id'   => $query->segment_id,
        // 'segment_name' => $query->segment_name,
      );
    }

    $this->assign('contacts', $contacts);
    $this->assign('contact_count', count($contacts));
    $this->assign('campaign', $campaign);
    $this->assign('snippet_mode', $snippet_mode);
    $this->assign('contact_base_url', CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid="));

    // add civicrm.css for snippet mode
    if ($snippet_mode) {
      $res = CRM_Core_Resources::singleton();
      $res->addStyleFile('civicrm', 'css/civicrm.css', -99, 'ajax-snippet');
    }

    parent::run();

  }

}
