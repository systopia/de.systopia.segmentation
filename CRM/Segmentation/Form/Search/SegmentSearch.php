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
 * Find contacts based on segmentation data
 */
class CRM_Segmentation_Form_Search_SegmentSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  /** cached entity representing this search */
  private static $search_entity = NULL;

  function __construct(&$formValues) {
    // pass campaign_id from URL
    $campaign_id_preset = CRM_Utils_Request::retrieve('cid', 'Integer');
    if ($campaign_id_preset && empty($formValues['campaign_id'])) {
      $formValues['campaign_id'] = $campaign_id_preset;
    }

    parent::__construct($formValues);
  }

  /**
   * create a link to this search with a campaign_id pre-filled
   */
  public static function generateSearchLink($campaign_id) {
    $campaign_id = (int) $campaign_id;
    if (self::$search_entity == NULL) {
      self::$search_entity = civicrm_api3('CustomSearch', 'getsingle', array('name' => "CRM_Segmentation_Form_Search_SegmentSearch"));
    }
    $search = self::$search_entity;
    return CRM_Utils_System::url('civicrm/contact/search/custom', "reset=1&csid={$search['value']}&cid={$campaign_id}");
  }

  /**
   * Prepare the search form
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts("Segmentation Search"));

    // should have: campaing selector, segment selector,
    //   preselect campaign (linked from campaign view)

    // campaign selector
    $form->addElement('select',
                      'campaign_id',
                      ts('Campaign', array('domain' => 'de.systopia.segmentation')),
                      CRM_Segmentation_Logic::getAllCampaigns(),
                      array('class' => 'crm-select2 huge'));

    // segment options
    $form->addElement('select',
                      'segment_list',
                      ts('Segment', array('domain' => 'de.systopia.segmentation')),
                      array(),
                      array('class' => 'crm-select2 huge'));

    // hidden field for value
    $form->addElement('text', // hidden doesn't work...
                      'segment_id',
                      ts('Segment ID', array('domain' => 'de.systopia.segmentation')));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('campaign_id', 'segment_list', 'segment_id'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total'   => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    $columns = array(
      // ts('Contact ID')   => 'civicrm_contact_id',
      ts('Contact Name') => 'contact_name',
      ts('Segment')      => 'segment',
      // ts('Campaign ID')  => 'campaign_id',
      ts('Campaign')     => 'campaign_name',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      contact_a.id                    AS civicrm_contact_id,
      contact_a.display_name          AS contact_name,
      civicrm_segmentation_index.name AS segment,
      civicrm_segmentation.entity_id  AS contact_id,
      civicrm_campaign.id             AS campaign_id,
      civicrm_campaign.title          AS campaign_name
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "
      FROM civicrm_segmentation
      LEFT JOIN civicrm_segmentation_index    ON civicrm_segmentation_index.id = civicrm_segmentation.segment_id
      LEFT JOIN civicrm_contact AS contact_a  ON contact_a.id = civicrm_segmentation.entity_id
      LEFT JOIN civicrm_campaign              ON civicrm_campaign.id = civicrm_segmentation.campaign_id
      LEFT JOIN civicrm_membership            ON civicrm_membership.id = civicrm_segmentation.membership_id
      ";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $clauses = array();

    // exclude deleted contacts
    $clauses[] = "contact_a.is_deleted = 0";

    // restrict to campaign
    if (!empty($this->_formValues['campaign_id'])) {
      $campaign_id = (int) $this->_formValues['campaign_id'];
      $clauses[] = "civicrm_campaign.id = {$campaign_id}";
    }

    // restrict to segment
    if (!empty($this->_formValues['segment_id'])) {
      $segment_id = (int) $this->_formValues['segment_id'];
      $clauses[] = "civicrm_segmentation.segment_id = {$segment_id}";
    }

    return '(' . implode(') AND (', $clauses) . ')';
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Segmentation/Form/Search/SegmentSearch.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    // $row['sort_name'] .= ' ( altered )';
  }
}
