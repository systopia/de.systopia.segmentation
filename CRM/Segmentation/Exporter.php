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

define('SEGMENTATION_EXPORT_CHUNK_SIZE', 100);

/**
 * Basic Export Logic
 */
abstract class CRM_Segmentation_Exporter {

  protected $config = NULL;
  protected $chunk_size = SEGMENTATION_EXPORT_CHUNK_SIZE;
  protected $tmpFileHandle = NULL;
  protected $tmpFileName   = NULL;
  protected $filename      = NULL;

  // cached entities
  protected $_contact      = NULL;
  protected $_campaign     = NULL;
  protected $_phone_phone  = NULL;
  protected $_phone_mobile = NULL;

  /**
   * constructor is protected -> use CRM_Segmentation_Exporter::getExporter() (below)
   */
  protected function __construct($config) {
    $this->config = $config;
  }


  /*************************************************
   **    Abstract functions to be implemented     **
   *************************************************/

  /**
   * write the file header to the stream ($this->tmpFileHandle)
   */
  public abstract function exportHeader();

  /**
   * write the data to the stream ($this->tmpFileHandle)
   *
   * @param $chunk a number of segmentation lines to
   */
  public abstract function exportChunk($chunk);

  /**
   * write the end/wrapuo data to the stream ($this->tmpFileHandle)
   */
  public abstract function exportFooter();

  /**
   * provides the file name for download
   * should be overwritten by the implementation
   */
  protected function getFileName() {
    if (!empty($this->filename)) {
      return $this->filename;
    } else {
      return $this->config['name'] . '.' . strtolower($this->config['type']);
    }
  }


  /**
   * export the given campaig and segments
   */
  public function generateFile($campaign_id, $segment_list = NULL) {
    // TODO: dispatch, different export types, etc.
    // this is just a POC
    $this->createTmpFile();
    $this->exportHeader();
    $query_sql = CRM_Segmentation_Configuration::getSegmentQuery($campaign_id, $segment_list);
    $main_query = CRM_Core_DAO::executeQuery($query_sql);

    $more_data = TRUE;
    while ($more_data) {
      // compile a chunk
      $segment_chunk = array();
      $more_data = FALSE; // will be set back to TRUE below
      while ($main_query->fetch()) {
        $segment_chunk[] = array(
          'contact_id'    => $main_query->contact_id,
          'datetime'      => $main_query->datetime,
          'campaign_id'   => $main_query->campaign_id,
          'segment_id'    => $main_query->segment_id,
          'segment_name'  => $main_query->segment_name,
          'test_group'    => $main_query->test_group,
          'membership_id' => $main_query->membership_id);
        if (count($segment_chunk) >= $this->chunk_size) {
          $more_data = TRUE;
          break;
        }
      }
      $this->exportChunk($segment_chunk);
    }

    $this->exportFooter();
    $this->closeTmpFile();
  }

  /**
   * offer the result for export
   * generateFile has be called before this
   *
   * This does not return
   */
  public function exportFile() {
    if (empty($this->tmpFileName)) {
      throw new Exception("No file exported yet");
    }
    // TODO: don't use buffer, just read into phpout
    $mime_type = mime_content_type($this->tmpFileName);
    $buffer    = file_get_contents($this->tmpFileName);
    $this->deleteTmpFile();
    CRM_Utils_System::download($this->getFileName(), $mime_type, $buffer);
  }

  /**
   * create a new tmp file
   */
  protected function createTmpFile() {
    $this->tmpFileName = tempnam(sys_get_temp_dir(), 'prefix');
    $this->tmpFileHandle = fopen($this->tmpFileName, 'w');
  }

  /**
   * remove tmp file
   */
  protected function closeTmpFile() {
    fclose($this->tmpFileHandle);
    $this->tmpFileHandle = NULL;
  }

  /**
   * remove the tmp file
   */
  protected function deleteTmpFile() {
    unlink($this->tmpFileName);
    $this->tmpFileName = NULL;
  }


  /****************************
   **    STATIC ACCESS       **
   ****************************/

  /**
   * get an exporter instance for the given configuration
   */
  public static function getExporter($exporter_id) {
    $exporters = self::getAllExporters();
    if (empty($exporters[$exporter_id])) {
      throw new Exception("Unknown Exporter ID '{$exporter_id}'");
    }

    $config = $exporters[$exporter_id];

    // find the right class
    $class = "CRM_Segmentation_Exporter{$config['type']}";
    return new $class($config);
  }

  /**
   * Get the list of configured exporters
   *
   * @return array( exporter_id => exporter name)
   */
  public static function getExporterList() {
    $exporters = self::getAllExporters();
    $exporter_list = array();
    foreach ($exporters as $exporter_id => $exporter) {
      $exporter_list[$exporter_id] = $exporter['name'];
    }
    return $exporter_list;
  }

  /**
   * Get the list of configured exporters
   *
   * @return array( exporter_id => array<exporter spec>)
   */
  public static function getAllExporters() {
    $exporters = CRM_Core_BAO_Setting::getItem('Segmentation', 'segmentation_exporters');
    if (!is_array($exporters)) {
      // load default file
      $default_exporter_data = file_get_contents(__DIR__ . "/../../resources/default_exporters.json");
      $exporters = json_decode($default_exporter_data, TRUE);
    }

    $exporter_list = array();
    foreach ($exporters as $exporter) {
      $exporter_list[$exporter['id']] = $exporter;
    }
    return $exporter_list;
  }


  /*************************************************
   **       INTERNAL GEARWORKS: RULES             **
   *************************************************/

  /**
   * This is the "heart and soul" of the exporter. It will
   * interpret the rules defined in the configuration
   * and generate an array of data for this segementation line
   * for the exporter to write out.
   */
  protected function executeRules($line) {
    $data = array();
    foreach ($this->config['rules'] as $rule) {
      switch ($rule['action']) {
        // RULE: COPY
        case 'copy':
          $data[$rule['to']] = $this->getValue($rule['from'], $line, $data);
          break;

        // RULE: SET FILE NAME
        case 'setfilename':
          $this->filename = $this->getValue($rule['from'], $line, $data);
          break;

        default:
          throw new Exception("Unknown rule action '{$rule['action']}' in exporter configuration", 1);
      }
    }
    return $data;
  }

  /**
   * get the value of a given soruce specification, which can be
   *  1) a simple variable name in $data
   *  2) access to the connected entities, e.g. contact.first_name.
   *     The following entities are supported:
   *     contact       - the contact linked the segment
   *     campaign      - the contact linked the segment
   *     segment       - the segment itself
   *     phone_phone   - the contact's phone of type 'Phone' (1)
   *     phone_mobile  - the contact's phone of type 'Mobile' (1)
   *     [TODO] membership - the membership linked the segment
   *  CAUTION: if a variable like 'contact.first_name' exists in $data
   *            it takes preference
   *
   * @param $source string  the source spec (what you just read)
   * @param $line   array   the segmentation line as in the DB
   * @param $data   array   the accumulated/processed data for the current line
   */
  protected function getValue($source, $line, $data) {
    if (isset($data[$source])) {
      return $data[$source];
    }

    // check if it's an entity source
    if (preg_match('#^(?P<entity>\w+)[.](?P<attribute>\w+)$#', $source, $entity_source)) {
      $entity = $this->getEntity($entity_source['entity'], $line);
      if (isset($entity[$entity_source['attribute']])) {
        return $entity[$entity_source['attribute']];
      } else {
        return '';
      }
    }

    // it doesn't exist yet and is not entity source -> return empty string
    return '';
  }

  /**
   * get entity related to this line, see documentation for ::executeRules
   */
  protected function getEntity($entity_name, $line) {
    switch (strtolower($entity_name)) {
      case 'contact':
        if ($this->_contact == NULL || $this->_contact['id'] != $line['contact_id']) {
          $this->_contact = civicrm_api3('Contact', 'getsingle', array('id' => $line['contact_id']));
        }
        return $this->_contact;

      case 'segment':
        return $line;

      case 'campaign':
        if ($this->_campaign == NULL || $this->_campaign['id'] != $line['campaign_id']) {
          $this->_campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $line['campaign_id']));
        }
        return $this->_campaign;

      case 'phone_phone':
        if ($this->_phone_phone == NULL || $this->_phone_phone['contact_id'] != $line['contact_id']) {
          $this->_phone_phone = $this->findDetailEntity($line, 'Phone', array('phone_type_id' => 1));
        }
        return $this->_phone_phone;

      case 'phone_mobile':
        if ($this->_phone_mobile == NULL || $this->_phone_mobile['contact_id'] != $line['contact_id']) {
          $this->_phone_mobile = $this->findDetailEntity($line, 'Phone', array('phone_type_id' => 2));
        }
        return $this->_phone_mobile;

      default:
        throw new Exception("Unkown entity '{$entity_name}' requested", 1);
    }
  }

  /**
   * used to find and return detail entities (Phone, Email, etc.)
   */
  protected function findDetailEntity($line, $type, $search_params, $preferred = 'is_primary') {
    $search_params['option.limit'] = 0;
    $search_params['contact_id'] = $line['contact_id'];
    $query = civicrm_api3($type, 'get', $search_params);

    // find an entity
    $entity = array('contact_id' => $line['contact_id']); // fallback
    foreach ($query['values'] as $entity_candidate) {
      if (!empty($preferred) && !empty($entity_candidate[$preferred])) {
        // that's the one!
        return $entity_candidate;
      } else {
        $entity = $entity_candidate;
      }
    }
    return $entity;
  }
}
