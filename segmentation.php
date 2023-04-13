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

require_once 'segmentation.civix.php';

/**
 * add the "start campaign" action to campaigns
 */
function segmentation_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ($objectName == 'Campaign' && ($op == 'campaign.selector.row' || $op == 'campaign.dashboard.row')) {
    $links[] = array(
            'name'  => ts('Contacts'),
            'url'   => CRM_Segmentation_Form_Search_SegmentSearch::generateSearchLink($objectId),
            'title' => ts('Find Contacts'),
            'class' => 'no-popup',
          );
    $links[] = array(
            'name'  => ts('Activity'),
            'url'   => CRM_Utils_System::url('civicrm/segmentation/createactivity', "cid={$objectId}"),
            'title' => ts('Create Activity'),
          );

    $campaign = civicrm_api3('Campaign', 'getsingle', array(
      'id'     => $objectId,
      'return' => 'status_id'));
    if ($campaign['status_id'] == 1) {
      $links[] = array(
              'name'  => ts('Start'),
              'url'   => CRM_Utils_System::url('civicrm/segmentation/start', "cid={$objectId}"),
              'title' => ts('Start Campaign'),
              'class' => 'no-popup',
            );
    } else {
      $links[] = array(
              'name'  => ts('Export'),
              'url'   => CRM_Utils_System::url('civicrm/segmentation/export', "cid={$objectId}&reset=1"),
              'title' => ts('Export Segments'),
              'class' => 'no-popup',
            );
    }
  }
}

/**
* Add an "Assign to Campaign" for contact / membership search results
*
* @param string $objectType specifies the component
* @param array $tasks the list of actions
*
* @access public
*/
function segmentation_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'contact') {
    if (CRM_Core_Permission::check('manage campaign')) {
      $tasks[] = array(
          'title' => ts('Assign Contacts to Campaign', array('domain' => 'de.systopia.segmentation')),
          'class' => 'CRM_Segmentation_Form_Task_Assign',
          'result' => false);
      $tasks[] = array(
          'title' => ts('Assign Memberships to Campaign', array('domain' => 'de.systopia.segmentation')),
          'class' => 'CRM_Segmentation_Form_Task_AssignContactMembership',
          'result' => false);
      $tasks[] = array(
          'title' => ts('Detach from Campaign', array('domain' => 'de.systopia.segmentation')),
          'class' => 'CRM_Segmentation_Form_Task_Detach',
          'result' => false);
    }

  } elseif ($objectType == 'membership') {
    if (CRM_Core_Permission::check('manage campaign')) {
      // this gets called multiple types -> check if already in there
      foreach ($tasks as $task) {
        if (isset($task['class']) && $task['class'] == 'CRM_Segmentation_Form_Task_AssignMembership') {
          return; // it's already in there
        }
      }
      // it's not in here yet -> add
      $tasks[] = array(
          'title' => ts('Assign to Campaign', array('domain' => 'de.systopia.segmentation')),
          'class' => 'CRM_Segmentation_Form_Task_AssignMembership',
          'result' => false);
    }
  }
}

/**
 * implement the hook to customize the rendered tab of our custom group
 */
function segmentation_civicrm_pageRun( &$page ) {
  if ($page->getVar('_name') == 'CRM_Contact_Page_View_CustomData') {
    $segmentation_group_id = CRM_Segmentation_Configuration::groupID();
    if ($page->_groupId == $segmentation_group_id) {
      // this is the right view -> inject JS
      CRM_Segmentation_Page_View_CustomData::adjustPage($page, __DIR__);
    }
  }
}

/**
 * implement hook to set permissions for API calls
 */
function segmentation_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['segmentation']['segmentlist'] = array('manage campaign');
  $permissions['segmentation']['getsegmentid'] = array('manage campaign');
  $permissions['segmentationorder']['create'] = array('manage campaign');
  $permissions['segmentation_order']['update'] = array('manage campaign');
}

/**
 * implement hook to add KPI
 */
function segmentation_civicrm_campaignKpis ($campaign_id, &$kpi_array, $tree_level) {
  // TODO: make more performant
  // calculate segmentation data
  $segmentation_data = array();
  $segment_counts = CRM_Segmentation_Logic::getSegmentCounts($campaign_id);
  $segment_titles = CRM_Segmentation_Logic::getSegmentTitles(array_keys($segment_counts));
  $total_count    = array_sum($segment_counts);
  foreach ($segment_counts as $segment_id => $segment_count) {
    if ($segment_count > 0) {
      $segmentation_data[] = array(
        'label' => $segment_titles[$segment_id] . " ({$segment_count})",
        'value' => ((float) $segment_count) / (float) $total_count);
    }
  }
  if (!empty($segmentation_data)) {
    $kpi_array["segmentation"] = array(
      "id" => "segmentation",
      "title" => ts("Contact Segmentation"),
      "kpi_type" => "hidden",
      "vis_type" => "pie_chart",
      "description" => ts("Displays the contacts assigned via the SYSTOPIA segmentation extension."),
      "value" => $segmentation_data,
      "link" => ""
    );
  }
  $kpi_array["contact_count"] = array(
    "id" => "contact_count",
    "title" => ts("Contact Count"),
    "kpi_type" => "number",
    "vis_type" => "",
    "description" => ts("Number of contacts assigned via the SYSTOPIA segmentation extension."),
    "value" => $total_count,
    "link" => ""
  );
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function segmentation_civicrm_config(&$config) {
  _segmentation_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function segmentation_civicrm_install() {
  _segmentation_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function segmentation_civicrm_enable() {
  _segmentation_civix_civicrm_enable();


  // create semgentation index table
  $config = CRM_Core_Config::singleton();
  $sqlfile = dirname( __FILE__ ) . '/sql/civicrm_segment_index.sql';
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile);

  // create segmentation exclude table
  $sqlfile = dirname( __FILE__ ) . '/sql/create_segmentation_exclude.sql';
  CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile);

  // add custom fields
  require_once 'CRM/Utils/CustomData.php';
  $customData = new CRM_Utils_CustomData('de.systopia.segmentation');
  $customData->syncOptionGroup(__DIR__ . '/resources/segments_option_group.json');
  $customData->syncOptionGroup(__DIR__ . '/resources/activity_type_option_group.json');
  $customData->syncCustomGroup(__DIR__ . '/resources/segmentation_custom_group.json');
}

function segmentation_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName == 'Activity' && $op == 'create') {
    if (isset($params['target_contact_id'])) {
      // Maintain a global copy of target_contact_ids added via the Activity.
      // If there is a universe where _pre and _post isn't called sequentially
      // on two Activity.create API operations, this will break spectacularly.
      $store = CRM_Segmentation_ActivityContactStore::getInstance();
      if (is_array($params['target_contact_id'])) {
        $store->setContacts($params['target_contact_id']);
      }
      else {
        $store->setContacts([$params['target_contact_id']]);
      }
    }
  }
}

function segmentation_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == 'Activity' && $op == 'create') {
    $store = CRM_Segmentation_ActivityContactStore::getInstance();
    foreach ($store->popContacts() as $contact_id) {
      if (!empty($objectId) && !empty($contact_id)) {
        CRM_Segmentation_Logic::addSegmentForActivityContact($objectId, $contact_id);
      }
    }
  }
}

function segmentation_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  if ($apiRequest['entity'] == 'ActivityContact' && $apiRequest['action'] == 'create') {
    $wrappers[] = new CRM_Segmentation_ActivityContactCreateAPIWrapper();
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function segmentation_civicrm_navigationMenu(&$menu) {
  _segmentation_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'de.systopia.segmentation')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _segmentation_civix_navigationMenu($menu);
} // */
