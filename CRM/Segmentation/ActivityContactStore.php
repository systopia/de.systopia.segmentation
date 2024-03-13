<?php

class CRM_Segmentation_ActivityContactStore {

  private $_contacts = [];

  public static function getInstance() {
    static $instance = NULL;
    if (NULL === $instance) {
      $instance = new static();
    }

    return $instance;
  }

  /**
   * @param array $contacts
   */
  public function setContacts(array $contacts) {
    $this->_contacts = $contacts;
  }

  public function popContacts() {
    $contacts = $this->_contacts;
    $this->_contacts = [];
    return $contacts;
  }

  protected function __construct() {
  }

  public function __wakeup() {
  }

}
