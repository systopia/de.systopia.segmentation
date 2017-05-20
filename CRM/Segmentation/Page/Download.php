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
    $file_name   = CRM_Utils_Request::retrieve('file', 'String');
    $campaign_id = CRM_Utils_Request::retrieve('cid', 'String');
    $download    = CRM_Utils_Request::retrieve('download', 'Integer');

    if (substr($file_name, 0, 20) != 'segmentation_export_') {
      // this is not one of our files!
      CRM_Core_Session::setStatus(ts("This is not a segmentation export file!"), ts("Error"), "error");
      $error_url = CRM_Utils_System::url('civicrm/dashboard');
      CRM_Utils_System::redirect($error_url);
    }

    // load the campaign
    $campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $campaign_id));

    // get file data
    $tmpfolder = dirname(tempnam(sys_get_temp_dir(), '_test_'));
    $file_path = $tmpfolder . DIRECTORY_SEPARATOR . $file_name;
    $mime_type = mime_content_type($file_path);
    $file_size = filesize($file_path);
    $download_name = file_get_contents($file_path . '_filename');
    if (!$download_name) {
      $download_name = 'export';
    }

    if ($download) {
      // RUN THE ACTUAL DOWNLOAD

      // prepare headers
      $buffer_dummy = '';
      CRM_Utils_System::download($download_name, $mime_type, $buffer_dummy, NULL, FALSE);
      header('Content-Length: ' . $file_size);

      // dump file data
      readfile($file_path);
      CRM_Utils_System::civiExit();

    } else {
      // GENERATE NICE DOWNLOAD PAGE

      // generate pretty size string
      $size = $file_size / 1024;
      if ($size >= 1024) {
        $size = $size / 1024;
        $size_string = sprintf("%.1f MB", $size);
      } else {
        $size_string = sprintf("%.1f kB", $size);
      }

      CRM_Utils_System::setTitle(ts("Campaign Export: '%1'", array(1 => $campaign['title'])));
      $this->assign('download_name', $download_name);
      $this->assign('file_size', $size_string);
      $this->assign('download_link', CRM_Utils_System::url('civicrm/segmentation/download', "cid={$campaign_id}&file={$file_name}&download=1"));
      $this->assign('back_link', CRM_Utils_System::url("civicrm/a/#/campaign/{$campaign_id}/view"));

      parent::run();
    }
  }
}
