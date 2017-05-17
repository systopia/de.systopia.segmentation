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
  protected $_campaign     = NULL;
  protected $_contacts     = NULL; // array contact_id => $contact
  protected $_memberships  = NULL; // array membership_id => $membership
  protected $_details      = NULL; // array contact_id => array(email/phone/...)

  /**
   * constructor is protected -> use CRM_Segmentation_Exporter::getExporter() (below)
   */
  protected function __construct($config) {
    $this->config = $config;
    $this->_contacts = array();
    $this->_details = array();
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
      $this->preCache($segment_chunk);
      $this->exportChunk($segment_chunk);
      $this->flushCache($segment_chunk);
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

        // RULE: SET
        case 'set':
          $data[$rule['to']] = CRM_Utils_Array::value('value', $rule, '');
          break;

        // RULE: SPRINTF
        case 'sprintf':
          $format = CRM_Utils_Array::value('format', $rule, '');
          $data[$rule['to']] = sprintf($format, $this->getValue($rule['from'], $line, $data));
          break;

        // RULE: DATE
        case 'date':
          $format = CRM_Utils_Array::value('format', $rule, '');
          $data[$rule['to']] = date($format, strtotime($this->getValue($rule['from'], $line, $data)));
          break;


        // RULE: APPEND
        case 'append':
          $appended_string = CRM_Utils_Array::value('separator', $rule, '');
          $appended_string .= $this->getValue($rule['from'], $line, $data);
          $data[$rule['to']] .= $appended_string;
          break;

        // RULE: MOD97
       case 'mod97':
          $string_to_check = $this->getValue($rule['from'], $line, $data);
          // strip all non-digits
          $string_to_check = preg_replace('#[^0-9]#', '', $string_to_check);
          // append empty checksum '00'
          $string_to_check .= '00';
          // calculate checksum
          $result = 98 - ($string_to_check % 97);
          // format result with two digits
          $data[$rule['to']] .= sprintf('%2d', $result);
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
        if (!isset($this->_contacts[$line['contact_id']])) {
          error_log("CACHE MISS: Contact.{$line['contact_id']}");
          $this->_contacts[$line['contact_id']] = civicrm_api3('Contact', 'getsingle', array('id' => $line['contact_id']));
        }
        return $this->_contacts[$line['contact_id']];

      case 'segment':
        return $line;

      case 'campaign':
        if ($this->_campaign == NULL || $this->_campaign['id'] != $line['campaign_id']) {
          error_log("CACHE MISS: Campaign.{$line['campaign_id']}");
          $this->_campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $line['campaign_id']));
        }
        return $this->_campaign;

      case 'phone_phone':
        return $this->getDetailEntity($line, 'Phone', array('phone_type_id' => 1));
        // if (!$phone) {
        //   $phone = $this->loadDetailEntity($line, 'Phone', array('phone_type_id' => 1));
        // }
        // return $phone;

      case 'phone_mobile':
        return $this->getDetailEntity($line, 'Phone', array('phone_type_id' => 2));
        // if (!$phone) {
        //   $phone = $this->loadDetailEntity($line, 'Phone', array('phone_type_id' => 2));
        // }
        // return $phone;

      default:
        throw new Exception("Unkown entity '{$entity_name}' requested", 1);
    }
  }

  /**
   * get a detail entity (Phone, Email, etc.) from the cache
   */
  protected function getDetailEntity($line, $type, $search_params, $preferred = 'is_primary') {
    $contact_id = $line['contact_id'];
    $type_index = strtolower($type);
    $entity = array(); // fallback

    if (isset($this->_details[$contact_id][$type_index])) {
      foreach ($this->_details[$contact_id][$type_index] as $entity_candidate) {
        // test if entity matches search params
        foreach ($search_params as $key => $value) {
          if ($entity_candidate[$key] != $value) {
            // this is not the entity you're looking for
            continue 2;
          }
        }

        // check if this is a preferred entity
        if (!empty($preferred) && !empty($entity_candidate[$preferred])) {
          // that's the one!
          return $entity_candidate;
        } else {
          $entity = $entity_candidate;
        }
      }
      return $entity;

    } else {
      // cache entry not set -> details not loaded yet
      $this->_details[$contact_id][$type_index] = array();
      $query = civicrm_api3($type, 'get', array(
        'contact_id'   => $contact_id,
        'option.limit' => 0));

      foreach ($query['values'] as $entity) {
        $this->_details[$contact_id][$type_index][] = $entity;
      }

      return $this->getDetailEntity($line, $type, $search_params, $preferred);
    }
  }

  // /**
  //  * load and return detail entities (Phone, Email, etc.)
  //  */
  // protected function loadDetailEntity($line, $type, $search_params, $preferred = 'is_primary') {
  //   error_log("CACHE MISS: {$type}.{$line['contact_id']}");
  //   $type_index = strtolower($type);
  //   $search_params['option.limit'] = 0;
  //   $search_params['contact_id'] = $line['contact_id'];
  //   $query = civicrm_api3($type, 'get', $search_params);

  //   // find an entity
  //   foreach ($query['values'] as $entity) {
  //     $this->_details[$line['contact_id']][$type_index][] = $entity;
  //   }

  //   return $this->getDetailEntity($line, $type, $search_params, $preferred);
  // }



  /*************************************************
   **                CACHING                      **
   *************************************************/

  /**
   * fill the internal cache with all data for that chunk,
   * based on the rules
   */
  protected function preCache($chunk) {
    // gather IDs
    $contact_ids    = array();
    $membership_ids = array();
    foreach ($chunk as $segment) {
      $contact_ids[] = $segment['contact_id'];
      if (!empty($segment['membership_id'])) {
        $membership_ids[] = $segment['membership_id'];
      }
    }

    // gather fields
    $contact_fields = array();
    $membership_fields = array();
    // TODO: $email_types = array();
    $phone_types = array();
    // TODO: $address_types = array();
    foreach ($this->config['rules'] as $rule) {
      if (isset($rule['from']) && preg_match('#^(?P<entity>\w+)[.](?P<attribute>\w+)$#', $rule['from'], $entity_source)) {
        switch (strtolower($entity_source['entity'])) {
          case 'contact':
            $contact_fields[$entity_source['attribute']] = 1;
            break;

          case 'membership':
            $membership_fields[$entity_source['attribute']] = 1;
            break;

          case 'phone_phone':
            $phone_types['1'] = 1; # normal phone
            break;

          case 'phone_mobile':
            $phone_types['2'] = 1; # mobile phone
            break;

          default:
            break;
        }
      }
    }

    // load contact data
    if (!empty($contact_fields)) {
      $contact_query = civicrm_api3('Contact', 'get', array(
        'id'           => array('IN' => $contact_ids),
        'option.limit' => 0,
        'return'       => implode(',', array_keys($contact_fields))
        ));
      foreach ($contact_query['values'] as $contact) {
        $this->_contacts[$contact['id']] = $contact;
      }
    }

    // load membership data
    if (!empty($membership_fields)) {
      $membership_query = civicrm_api3('Membership', 'get', array(
        'id'           => array('IN' => $membership_ids),
        'option.limit' => 0,
        'return'       => implode(',', array_keys($membership_fields))
        ));
      foreach ($membership_query['values'] as $membership) {
        $this->_memberships[$membership['id']] = $membership;
      }
    }

    // load phone data
    if (!empty($phone_types)) {
      // create array
      foreach ($contact_ids as $contact_id) {
        $this->_details[$contact_id]['phone'] = array();
      }
      $phone_query = civicrm_api3('Phone', 'get', array(
        'contact_id'    => array('IN' => $contact_ids),
        'option.limit'  => 0,
        'phone_type_id' => array('IN' => array_keys($phone_types)),
        ));
      foreach ($phone_query['values'] as $phone) {
        $this->_details[$phone['contact_id']]['phone'][] = $phone;
      }
    }

    // TODO: email/address/etc data
  }

  /**
   * flush the cache
   */
  protected function flushCache($chunk) {
    $this->_contacts = array();
    $this->_details = array();
  }
}
