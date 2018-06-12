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
    if (isset($result['id'])) {
      CRM_Segmentation_Logic::addSegmentForActivityContact($result['values'][0]['activity_id'], $result['values'][0]['contact_id']);
    }
    return $result;
  }

}
