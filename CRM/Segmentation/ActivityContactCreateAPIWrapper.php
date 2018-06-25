<?php

class CRM_Segmentation_ActivityContactCreateAPIWrapper implements API_Wrapper {

  /**
   * @inheritDoc
   */
  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  /**
   * Try to add segment if an ActivityContact was created
   *
   * @inheritDoc
   */
  public function toApiOutput($apiRequest, $result) {
    if (isset($result['values'])) {
      foreach ($result['values'] as $activity_contact) {
        CRM_Segmentation_Logic::addSegmentForActivityContact($activity_contact['activity_id'], $activity_contact['contact_id']);
      }
    }
    return $result;
  }

}
