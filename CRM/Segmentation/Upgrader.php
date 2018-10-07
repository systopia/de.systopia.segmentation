<?php

use CRM_Segmentation_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Segmentation_Upgrader extends CRM_Segmentation_Upgrader_Base {

  public function upgrade_0900() {
    $this->ctx->log->info('Updating segmentation schema to 0.9.0');
    $this->executeSqlFile('sql/create_activity_contact_segmentation.sql');
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return TRUE;
  }

  public function upgrade_0910() {
    $this->ctx->log->info('Updating segmentation schema to 0.9.1');
    $this->executeSqlFile('sql/add_bundle_and_text_block.sql');
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return TRUE;
  }

  public function upgrade_0920() {
    $this->ctx->log->info('Updating segmentation schema to 0.9.2');
    $this->executeSqlFile('sql/create_segmentation_exclude.sql');
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return TRUE;
  }

  public function upgrade_0930() {
    $this->ctx->log->info('Updating segmentation schema to 0.9.3');
    $customData = new CRM_Utils_CustomData('de.systopia.segmentation');
    $customData->syncOptionGroup(__DIR__ . '/../../resources/activity_type_option_group.json');
    return TRUE;
  }

}
