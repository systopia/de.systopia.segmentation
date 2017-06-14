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
 * CSV Export Logic
 */
class CRM_Segmentation_ExporterCSV extends CRM_Segmentation_Exporter {

  /**
   * write the file header to the stream ($this->tmpFileHandle)
   */
  public function exportHeader() {
    fputcsv($this->tmpFileHandle, $this->encodeFields($this->config['columns']), $this->config['delimiter']);
  }

  /**
   * write the data to the stream ($this->tmpFileHandle)
   *
   * @param $data one line to be exported
   */
  public function exportLine($data) {
    // compile a row
    $row = array();
    foreach ($this->config['columns'] as $column_name) {
      if (isset($data[$column_name])) {
        $row[] = $data[$column_name];
      } else {
        $row[] = '';
      }
    }

    // write row
    fputcsv($this->tmpFileHandle, $this->encodeFields($row), $this->config['delimiter']);
  }

  /**
   * write the end/wrapup data to the stream ($this->tmpFileHandle)
   */
  public function exportFooter() {
    // nothing to do in case of CSV
  }

  /**
   * This function encodes each entry in the array according to the config
   */
  protected function encodeFields($data_array) {
    // TODO: implement
    return $data_array;
  }
}
