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
 * Triggers the download of an export file
 *  as created by ExportJob class
 */
class CRM_Segmentation_Page_Download extends CRM_Core_Page {

  public function run() {
    $file_name = CRM_Utils_Request::retrieve('file', 'String');
    $download = CRM_Utils_Request::retrieve('download', 'Integer');

    if (substr($file_name, 20) != 'segmentation_export_') {
      // this is not one of our files!

      // TODO: error, redirect

    }

    // get file data
    $tmpfolder = dirname(tempnam(sys_get_temp_dir(), '_test_'));
    $file_path = $tmpfolder . DIRECTORY_SEPARATOR . $file_name;
    $mime_type = mime_content_type($file_path);
    $download_name = file_get_contents($file_path . '_filename');
    if (!$download_name) {
      $download_name = 'export';
    }

    if ($download) {
      // RUN THE ACTUAL DOWNLOAD
      // prepare headers
      $buffer_dummy = '';
      CRM_Utils_System::download($download_name, $mime_type, $buffer_dummy, FALSE);

      // download file
      readfile($file_path); // dumps file to client
      CRM_Utils_System::civiExit();

    } else {
      // GENERATE NICE DOWNLOAD PAGE

      // TODO: show page with download/back button

      parent::run();
    }
  }
}
