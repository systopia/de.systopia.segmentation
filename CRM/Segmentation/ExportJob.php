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

define('SEGMENTATION_EXPORT_JOB_SIZE', 500);

/**
 * QueueJob for segmentation export
 */
class CRM_Segmentation_ExportJob {

  public $title           = NULL;
  protected $exporter_id  = NULL;
  protected $campaign_id  = NULL;
  protected $segment_list = NULL;
  protected $tmp_file     = NULL;
  protected $offset       = NULL;
  protected $count        = NULL;
  protected $is_last      = NULL;


  protected function __construct($exporter_id, $campaign_id, $segment_list, $tmp_file, $offset, $count, $is_last) {
    $this->exporter_id   = $exporter_id;
    $this->campaign_id   = $campaign_id;
    $this->segment_list  = $segment_list;
    $this->tmp_file      = $tmp_file;
    $this->is_last       = $is_last;
    $this->offset        = $offset;
    $this->count         = $count;

    // set title
    $this->title = ts("Exporting contacts %1-%2", array(
          1 => $this->offset, 2 => $this->offset + $this->count, 'domain' => 'de.systopia.segmentation'));
  }

  public function run($context) {
    // load exporter
    $exporter = CRM_Segmentation_Exporter::getExporter($this->exporter_id);
    if (!$exporter) {
      return FALSE;
    }

    $exporter->resumeTmpFile($this->tmp_file);
    $exporter->generateFile(
      $this->campaign_id,
      $this->segment_list,
      $this->offset,
      $this->count,
      $this->is_last,
      TRUE);

    // update filename
    $filename_handle = fopen($this->tmp_file . '_filename', 'w');
    fwrite($filename_handle, $exporter->getFileName());
    fclose($filename_handle);

    return TRUE;
  }

  /**
   * Use CRM_Queue_Runner to do the SDD group update
   * This doesn't return, but redirects to the runner
   */
  public static function launchExportRunner($campaign_id, $segment_list, $exporter_id) {
    // load campaign
    $campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $campaign_id));

    // get total count
    $total_count_sql = CRM_Segmentation_Configuration::getContactCount($campaign_id, $segment_list);
    $total_count = CRM_Core_DAO::singleValueQuery($total_count_sql);
    error_log("Count is: $total_count");

    // generate tmpfile
    $tmp_file = tempnam(sys_get_temp_dir(), "segmentation_export_{$campaign_id}_" . substr(sha1(rand()), 0, 8) . '_');
    error_log("Writing to file: {$tmp_file}");

    // create a queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'type'  => 'Sql',
      'name'  => 'segmentation_export',
      'reset' => TRUE,
    ));

    // create the items
    for ($offset=0; $offset < $total_count; $offset += SEGMENTATION_EXPORT_JOB_SIZE) {
      $queue->createItem(new CRM_Segmentation_ExportJob(
                                        $exporter_id,
                                        $campaign_id,
                                        $segment_list,
                                        $tmp_file,
                                        $offset,
                                        SEGMENTATION_EXPORT_JOB_SIZE,
                                        ($offset += SEGMENTATION_EXPORT_JOB_SIZE >= $total_count)
                                        ));
    }

    // generate donwload URL
    $tmp_file_name = basename($tmp_file);
    $download_url = CRM_Utils_System::url('civicrm/segmentation/download', "file={$tmp_file_name}&cid={$campaign_id}");
    $download_url = str_replace('&amp;', '&', $download_url); // why does this happen?

    // create a runner and launch it
    $runner = new CRM_Queue_Runner(array(
      'title'     => ts("Export Campaign '%1'", array(1 => $campaign['title'], 'domain' => 'org.project60.sepa')),
      'queue'     => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl'  => $download_url,
    ));
    $runner->runAllViaWeb(); // does not return
  }}
