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
class CRM_Segmentation_Configuration {

  protected static $custom_group_id = NULL;
  protected static $option_group_id = NULL;
  protected static $custom_fields = NULL;

  /**
   * Get the ID of the segmentation custom group
   */
  public static function groupID() {
    if (self::$custom_group_id === NULL) {
      $query = civicrm_api3('CustomGroup', 'getvalue', array(
        'return'     => 'id',
        'table_name' => 'civicrm_segmentation'));
      self::$custom_group_id = (int) $query;
    }
    return self::$custom_group_id;
  }


  /**
   * get all custom fileds in the segmentation custom group
   */
  public static function segmentationFields() {
    if (self::$custom_fields === NULL) {
      $query = civicrm_api3('CustomField', 'get', array('custom_group_id' => self::groupID()));
      self::$custom_fields = $query['values'];
    }
    return self::$custom_fields;
  }

  /**
   * get the ID of the segmentation custom field with the given column name
   */
  public static function getFieldID($column_name) {
    $fields = self::segmentationFields();
    foreach ($fields as $field) {
      if ($field['column_name'] == $column_name) {
        return $field['id'];
      }
    }
    // not found
    return NULL;
  }

  /**
   * Get the ID of the segmentation custom group
   */
  public static function segmentsGroupID() {
    if (self::$option_group_id === NULL) {
      $query = civicrm_api3('OptionGroup', 'getvalue', array(
        'return' => 'id',
        'name'   => 'segments'));
      self::$option_group_id = (int) $query;
    }
    return self::$option_group_id;
  }

}
