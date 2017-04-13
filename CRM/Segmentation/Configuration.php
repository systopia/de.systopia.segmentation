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

}
