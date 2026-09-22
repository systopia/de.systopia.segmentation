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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Segmentation_Form_Export extends CRM_Core_Form {

  public function buildQuickForm() {
    // first: load campaign
    $campaign_id = $this->getCampaignID();
    if (!$campaign_id) {
      CRM_Core_Session::setStatus(ts("No campaign ID (cid) given"), ts("Error"), "error");
      $error_url = CRM_Utils_System::url('civicrm/dashboard');
      CRM_Utils_System::redirect($error_url);
    }

    // load campaign and data
    $campaign       = civicrm_api3('Campaign', 'getsingle', ['id' => $campaign_id]);
    $segment_counts = CRM_Segmentation_Logic::getSegmentCounts($campaign_id);
    $segment_titles = CRM_Segmentation_Logic::getSegmentTitles(array_keys($segment_counts));

    $segment_list = [];
    foreach ($segment_counts as $segment_id => $segment_count) {
      $segment_list[$segment_id] = "{$segment_titles[$segment_id]} ({$segment_count})";
    }

    // build page
    CRM_Utils_System::setTitle(ts("Export Campaign '%1'", [1 => $campaign['title']]));

    // campaign selector
    $this->addElement('select',
                      'exporter_id',
                      ts('Select Exporter', ['domain' => 'de.systopia.segmentation']),
                      CRM_Segmentation_Exporter::getExporterList(),
                      ['class' => 'crm-select2 huge']);

    $this->addElement('select',
                      'segments',
                      ts('Segments', ['domain' => 'de.systopia.segmentation']),
                      $segment_list,
                      ['multiple' => "multiple", 'class' => 'crm-select2 huge']);

    $this->addDate('assigned_start_date',
                   ts('Assigned after', ['domain' => 'de.systopia.segmentation']),
                   FALSE,
                   ['formatType' => 'activityDateTime']);

    $this->addDate('assigned_end_date',
                   ts('Assigned before', ['domain' => 'de.systopia.segmentation']),
                   FALSE,
                   ['formatType' => 'activityDateTime']);

    $this->addElement('hidden',
                      'campaign_id',
                      $campaign_id);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Export'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
  }

  /**
   * set the default (=current) values in the form
   */
  public function setDefaultValues() {
    $values = $this->exportValues();
    return [
      'campaign_id' => $this->getCampaignID(),
      'segments'    => $values['segments'] ?? NULL,
      'exporter_id' => $values['exporter_id'] ?? NULL,
      ];
  }


  public function postProcess() {
    parent::postProcess();
    $values = $this->exportValues();

    // compile parameters
    $parameters = [];
    $parameters['segments'] = $values['segments'];
    if (!empty($values['assigned_start_date'])) {
      $parameters['start_date'] = date('YmdHis', strtotime("{$values['assigned_start_date']} {$values['assigned_start_date_time']}"));
    }
    if (!empty($values['assigned_end_date'])) {
      $parameters['end_date'] = date('YmdHis', strtotime("{$values['assigned_end_date']} {$values['assigned_end_date_time']}"));
    }

    CRM_Segmentation_ExportJob::launchExportRunner($values['campaign_id'], $values['exporter_id'], $parameters);
  }

  /**
   * get the campaign ID
   */
  private function getCampaignID() {
    $values = $this->exportValues();
    if (!empty($values['campaign_id'])) {
      return $values['campaign_id'];
    } else {
      $campaign_id = CRM_Utils_Request::retrieve('cid', 'Integer');
      if ($campaign_id) {
        return $campaign_id;
      } else {
        // seriously, wtf, how does it get in here and not the values?
        return CRM_Utils_Request::retrieve('campaign_id', 'Integer');
      }
    }
  }
}
