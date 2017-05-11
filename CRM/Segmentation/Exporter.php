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
 * Basic Export Logic
 */
class CRM_Segmentation_Exporter {

  protected $config = NULL;

  function __construct($exporter_id) {
    $exporters = self::getAllExporters();
    if (empty($exporters[$exporter_id])) {
      throw new Exception("Unknown Exporter ID '{$exporter_id}'");
    }
    $this->config = $exporters[$exporter_id];
  }

  /**
   * export the given campaig and segments
   */
  public function export($campaign_id, $segment_list = NULL) {
    // TODO
  }

  /**
   * Get the list of configured exporters
   *
   * @return array( exporter_id => exporter name)
   */
  public static function getExporterList() {
    $exporters = self::getAllExporters();
    $exporter_list = array();
    foreach ($exporters as $exporter_id => $exporter) {
      $exporter_list[$exporter_id] = $exporter['name'];
    }
    return $exporter_list;
  }

  /**
   * Get the list of configured exporters
   *
   * @return array( exporter_id => array<exporter spec>)
   */
  public static function getAllExporters() {
    $exporters = CRM_Core_BAO_Setting::getItem('Segmentation', 'segmentation_exporters');
    if (!is_array($exporters)) {
      // load default file
      $default_exporter_data = file_get_contents(__DIR__ . "/../../resources/default_exporters.json");
      $exporters = json_decode($default_exporter_data, TRUE);
    }

    $exporter_list = array();
    foreach ($exporters as $exporter) {
      $exporter_list[$exporter['id']] = $exporter;
    }
    return $exporter_list;
  }
}
