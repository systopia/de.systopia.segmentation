<?php

use CRM_Segmentation_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Segmentation_Upgrader extends CRM_Segmentation_Upgrader_Base {

  public function upgrade_0900() {
    $this->ctx->log->info('Updating segmentation schema to 0.9.0');
    $this->executeSqlFile('sql/create_activity_contact_segmentation.sql');
    return TRUE;
  }

}
