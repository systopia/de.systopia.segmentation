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
* Add an "Assign to Campaign" for contact / membership search results
*
* @param string $objectType specifies the component
* @param array $tasks the list of actions
*
* @access public
*/
function segmentation_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'contact') {
    if (CRM_Core_Permission::check('reserve campaign contacts')) {
      $tasks[] = array(
          'title' => ts('Assign to Campaign', array('domain' => 'de.systopia.segmentation')),
          'class' => 'CRM_Segmentation_Form_Task_Assign',
          'result' => false);
    }

  } elseif ($objectType == 'membership') {
    if (CRM_Core_Permission::check('reserve campaign contacts')) {
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
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function segmentation_civicrm_config(&$config) {
  _segmentation_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function segmentation_civicrm_xmlMenu(&$files) {
  _segmentation_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function segmentation_civicrm_postInstall() {
  _segmentation_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function segmentation_civicrm_uninstall() {
  _segmentation_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function segmentation_civicrm_enable() {
  _segmentation_civix_civicrm_enable();

  require_once 'CRM/Utils/CustomData.php';
  $customData = new CRM_Utils_CustomData('de.systopia.segmentation');
  $customData->syncOptionGroup(__DIR__ . '/resources/segments_option_group.json');
  $customData->syncCustomGroup(__DIR__ . '/resources/segmentation_custom_group.json');
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function segmentation_civicrm_disable() {
  _segmentation_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function segmentation_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _segmentation_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function segmentation_civicrm_managed(&$entities) {
  _segmentation_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function segmentation_civicrm_caseTypes(&$caseTypes) {
  _segmentation_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function segmentation_civicrm_angularModules(&$angularModules) {
  _segmentation_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function segmentation_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _segmentation_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function segmentation_civicrm_preProcess($formName, &$form) {

} // */

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
