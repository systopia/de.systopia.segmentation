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

    // prevent .excel default suffix
    return preg_replace('#excel$#', 'csv', $filename);
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
    $this->writeExcelLine($row);
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
    // iconv may produce bad transliteration results with some locales. set it
    // to en_US.UTF-8 temporarily so we produce consistent results
    $originalLocale = setlocale(LC_CTYPE, 0);
    setlocale(LC_CTYPE, 'en_US.UTF-8');
    $values = array();
    foreach ($data_array as $value) {
      // first: make sure there's no ';' in the value
      $value = str_replace(';', ',', $value);

      // then: encode
      if (function_exists('iconv') && defined('ICONV_IMPL') && ICONV_IMPL != 'libiconv') {
        // iconv is available, use with transliteration
        // note: the libiconv implementation (shipped e.g. with macOS) produces
        // bad results during transliteration, so we're not using it.
        // see https://stackoverflow.com/questions/57648563/iconv-separates-accents-from-letter-when-using-libiconv
        $values[] = iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $value);
      }
      else {
        $values[] = mb_convert_encoding($value, 'CP1252');
      }

    }

    // write to file
    fwrite($this->tmpFileHandle, implode(';', $values));
    fwrite($this->tmpFileHandle, "\r\n");

    // restore original locale
    setlocale(LC_CTYPE, $originalLocale);
  }
}
