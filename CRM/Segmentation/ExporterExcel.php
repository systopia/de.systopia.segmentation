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
 * Excel Export Logic (CSV, encoding=CP1252, delimiter=;, no escape)
 */
class CRM_Segmentation_ExporterExcel extends CRM_Segmentation_Exporter {

  /**
   * write the file header to the stream ($this->tmpFileHandle)
   */
  public function exportHeader() {
    $this->writeExcelLine($this->config['columns']);
  }

  /**
   * provides the file name for download
   * should be overwritten by the implementation
   */
  public function getFileName() {
    // calculate filename
    $filename = parent::getFileName();

    // prevent .execl default suffix
    return preg_replace('#excel$#', 'csv', $filename);
  }

  /**
   * write the data to the stream ($this->tmpFileHandle)
   *
   * @param $chunk a number of segmentation lines to
   */
  public function exportChunk($chunk) {
    foreach ($chunk as $segmentation_line) {
      // execute rules to get all data
      $data = $this->executeRules($segmentation_line);

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
      $this->writeExcelLine($row);
    }
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
  protected function writeExcelLine($data_array) {
    $values = array();
    foreach ($data_array as $value) {
      // first: make sure there's no ';' in the value
      $value = str_replace(';', ',', $value);

      // then: encode
      $values[] = mb_convert_encoding($value, 'CP1252');
    }

    // write to file
    fwrite($this->tmpFileHandle, implode(';', $values));
    fwrite($this->tmpFileHandle, "\r\n");
  }
}
