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

// This file declares a managed database record of type "CustomSearch".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name'   => 'CRM_Segmentation_Form_Search_SegmentSearch',
    'entity' => 'CustomSearch',
    'params' =>
    array (
      'version'     => 3,
      'label'       => ts('SegmentSearch'),
      'description' => ts('Search for Contacts in Segments (de.systopia.segmentation)'),
      'class_name'  => 'CRM_Segmentation_Form_Search_SegmentSearch',
    ),
  ),
);
