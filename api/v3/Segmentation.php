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
 * Get the segment ID for the given name
 * If the segement doesn't exist yet, it'll be created.
 */
function civicrm_api3_segmentation_getsegmentid($params) {
  if (empty($params['name'])) {
    return civicrm_api3_create_error("Required field 'name' was not properly set.");
  }

  // first try to find it
  $segment_id = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_segmentation_index WHERE name = %1;",
    array(1 => array($params['name'], 'String')));

  // if it doesn't exist: create
  if (!$segment_id) {
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_segmentation_index SET name = %1 ",
      array(1 => array($params['name'], 'String')));
    // then reload it
    $segment_id = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_segmentation_index WHERE name = %1;",
      array(1 => array($params['name'], 'String')));
  }

  if (!$segment_id) {
    return civicrm_api3_create_error("Internal error, please contact segmentation extension maintainer.");
  } else {
    // $result = array('id' => $segment_id, 'name' => $params['name']);
    // $noDAO = NULL;
    // return civicrm_api3_create_success($result, $params, NULL, NULL, $noDAO, array('id' => $segment_id));
    return civicrm_api3_create_success(array($segment_id => $params['name']));
  }
}

/**
 * API3 action specs
 */
function _civicrm_api3_segmentation_getsegmentid_spec(&$params) {
  $params['name']['title'] = "Segment Name";
  $params['name']['api.required'] = 1;
}


/**
 * List the different segments for all assignments
 * matching the given parameters
 */
function civicrm_api3_segmentation_segmentlist($params) {

  // build query
  $where_clauses = array();
  $where_params = array();

  if (!empty($params['contact_id'])) {
    $index = count($where_clauses) + 1;
    $where_clauses[] = "entity_id = %{$index}";
    $where_params[$index] = array($params['contact_id'], 'Integer');
  }

  if (!empty($params['campaign_id'])) {
    $index = count($where_clauses) + 1;
    $where_clauses[] = "campaign_id = %{$index}";
    $where_params[$index] = array($params['campaign_id'], 'Integer');
  }

  if (!empty($params['campaign_ids']) && is_array($params['campaign_ids'])) {
    $campaign_id_list = implode(',', $params['campaign_ids']);
    $where_clauses[] = "campaign_id IN ({$campaign_id_list})";
    $where_params[$index] = array($params['campaign_id'], 'Integer');
  }

  if (!empty($params['membership_id'])) {
    $index = count($where_clauses) + 1;
    $where_clauses[] = "membership_id = %{$index}";
    $where_params[$index] = array($params['membership_id'], 'Integer');
  }

  if (!empty($params['test_group'])) {
    $index = count($where_clauses) + 1;
    $where_clauses[] = "test_group = %{$index}";
    $where_params[$index] = array($params['test_group'], 'String');
  }

  if (empty($where_clauses)) {
    $sql_where = '';
  } else {
    $sql_where = 'WHERE ' . implode(' AND ', $where_clauses);
  }

  // now generate and execute query
  $query = CRM_Core_DAO::executeQuery("
      SELECT
        civicrm_segmentation_index.name AS segment,
        civicrm_segmentation.segment_id AS segment_id
      FROM civicrm_segmentation
      LEFT JOIN civicrm_segmentation_index ON civicrm_segmentation.segment_id = civicrm_segmentation_index.id
      {$sql_where}
      GROUP BY civicrm_segmentation.segment_id
      ;", $where_params);

  $segments = array();
  while ($query->fetch()) {
    if ($query->segment !== NULL) {
      $segments[$query->segment_id] = $query->segment;
    }
  }

  return civicrm_api3_create_success($segments);
}

/**
 * API3 action specs
 */
function _civicrm_api3_segmentation_segmentlist_spec(&$params) {
  $params['contact_id']['title'] = "Contact ID";
  $params['campaign_id']['title'] = "Campaign ID";
  $params['campaign_ids']['title'] = "Campaign IDs";
  $params['membership_id']['title'] = "Membership ID";
  $params['test_group']['title'] = "Test Subgroup";
}
