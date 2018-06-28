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
  protected $_loadCache    = NULL; // cache for loadEntity
  protected $_fieldCache   = NULL; // cache for resolveCustomField

  /**
   * constructor is protected -> use CRM_Segmentation_Exporter::getExporter() (below)
   */
  protected function __construct($config) {
    $this->config = $config;
    $this->_contacts = array();
    $this->_details = array();
    $this->_loadCache = array();
    $this->_fieldCache = array();
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
   * @param $data one line to be exported
   */
  public abstract function exportLine($data);

  /**
   * write the end/wrapuo data to the stream ($this->tmpFileHandle)
   */
  public abstract function exportFooter();

  /**
   * provides the file name for download
   * should be overwritten by the implementation
   */
  public function getFileName() {
    if (!empty($this->filename)) {
      return $this->filename;
    } else {
      return $this->config['name'] . '.' . strtolower($this->config['type']);
    }
  }

  /**
   * export the given campaig and segments
   */
  public function generateFile($campaign_id, $params = array(), $offset = 0, $count = 0, $is_last = TRUE, $exclude_deleted_contacts = TRUE) {
    $exportedRows = 0;
    // create a tmp file (if not yet exists)
    $this->createTmpFile();

    if ($offset == 0) {
      $this->exportHeader();
    }

    // add group_by/not_null parameter from exporter config
    if (!empty($this->config['group_by'])) {
      $params['group_by'] = $this->config['group_by'];
    }
    if (!empty($this->config['not_null'])) {
      $params['not_null'] = $this->config['not_null'];
    }

    $query_sql = CRM_Segmentation_Configuration::getSegmentQuery($campaign_id, $params, $exclude_deleted_contacts, $offset, $count);
    $main_query = CRM_Core_DAO::executeQuery($query_sql);

    $more_data = TRUE;
    while ($more_data) {
      // compile a chunk
      $segment_chunk = array();
      $more_data = FALSE; // will be set back to TRUE below
      while ($main_query->fetch()) {
        $exportedRows++;
        $segment_chunk[] = array(
          'contact_id'    => $main_query->contact_id,
          'datetime'      => $main_query->datetime,
          'campaign_id'   => $main_query->campaign_id,
          'segment_id'    => $main_query->segment_id,
          'segment_name'  => $main_query->segment_name,
          'test_group'    => $main_query->test_group,
          'membership_id' => $main_query->membership_id,
          'bundle'        => $main_query->bundle,
          'text_block'    => $main_query->text_block);
        if (count($segment_chunk) >= $this->chunk_size) {
          $more_data = TRUE;
          break;
        }
      }
      $this->preCache($segment_chunk);
      $this->exportChunk($segment_chunk);
      $this->flushCache($segment_chunk);
    }

    if ($is_last) {
      $this->exportFooter();
    }

    // close the tmpfile
    $this->closeTmpFile();

    return $exportedRows;
  }


  /**
   * offer the resulting file
   *   generateFile has be called before this
   *
   * @return file path
   */
  public function getExportedFile() {
    if (empty($this->tmpFileName)) {
      throw new Exception("No file exported yet");
    }
    return $this->tmpFileName;
  }

  /**
   * Get this exporter's name
   */
  public function getName() {
    return $this->config['name'];
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
   * write the data to the stream ($this->tmpFileHandle)
   *
   * @param $chunk a number of segmentation lines to
   */
  public function exportChunk($chunk) {
    foreach ($chunk as $segmentation_line) {
      $data = array();
      while ($this->loopNext($data, $segmentation_line)) {
        // execute rules to get all data
         $this->executeRules($segmentation_line, $data);

        // check if this line should be skipped
        if ($this->shouldSkipRow($data)) {
          continue;
        }

        $this->exportLine($data);
      }
    }
  }

  /**
   * Check if the row (represented by the data)
   * should be skipped, which is currently represented
   * by the __skip__ field
   */
  protected function shouldSkipRow($data) {
    return !empty($data['__skip__']);
  }

  /**
   * create a new tmp file
   */
  protected function createTmpFile() {
    if ($this->tmpFileHandle == NULL) {
      $this->tmpFileName = tempnam(sys_get_temp_dir(), 'prefix');
      $this->tmpFileHandle = fopen($this->tmpFileName, 'w');
    }
  }

  /**
   * resume with an existing file path
   */
  public function resumeTmpFile($filepath) {
    $this->tmpFileName = $filepath;
    $this->tmpFileHandle = fopen($this->tmpFileName, 'a');
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
  protected function executeRules($line, &$data) {
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

        // RULE: MAP
        case 'map':
          $value = $this->getValue($rule['from'], $line, $data);
          if (isset($rule['map'][$value])) {
            $data[$rule['to']] = $rule['map'][$value];
          }
          break;

        // RULE: SPRINTF
        case 'sprintf':
          $format = CRM_Utils_Array::value('format', $rule, '');
          $data[$rule['to']] = sprintf($format, $this->getValue($rule['from'], $line, $data));
          break;

        // RULE: REGEX REPLACE
        case 'preg_replace':
          $pattern = CRM_Utils_Array::value('search', $rule, '##');
          $replace = CRM_Utils_Array::value('replace', $rule, '');
          $data[$rule['to']] = preg_replace($pattern, $replace, $this->getValue($rule['from'], $line, $data));
          break;

        // RULE: REGEX PARSE (stores parse data in to named groups)
        case 'preg_parse':
          $pattern = CRM_Utils_Array::value('pattern', $rule, '##');
          $value = $this->getValue($rule['from'], $line, $data);
          if (preg_match($pattern, $value, $matches)) {
            foreach ($matches as $name => $string) {
              if (!is_numeric($name)) {
                $data[$name] = $string;
              }
            }
          }
          break;

        // RULE: SKIP
        case 'skip':
          $pattern = CRM_Utils_Array::value('matches', $rule, '');
          if (preg_match($pattern, $this->getValue($rule['from'], $line, $data))) {
            $data['__skip__'] = TRUE;
          }
          break;

        // RULE: DATE
        case 'date':
          $format = CRM_Utils_Array::value('format', $rule, '');
          $value = $this->getValue($rule['from'], $line, $data);
          if (empty($value)) {
            $data[$rule['to']] = '';
          } else {
           $data[$rule['to']] = date($format, strtotime($value));
          }
          break;

        // RULE: APPEND
        case 'append':
          $appended_string = CRM_Utils_Array::value('separator', $rule, '');
          $appended_string .= $this->getValue($rule['from'], $line, $data);
          $data[$rule['to']] .= $appended_string;
          break;

        // RULE: LOAD (entity)
        case 'load':
          if (!empty($rule['type'])) {
            $data[$rule['to']] = $this->loadEntity(
              CRM_Utils_Array::value('type', $rule, ''),
              CRM_Utils_Array::value('params', $rule, array()),
              CRM_Utils_Array::value('required', $rule, array()),
              $line,
              $data,
              CRM_Utils_Array::value('cached', $rule, FALSE));
          }
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
          $data[$rule['to']] = sprintf('%02d', $result);
          break;

        // RULE: SET FILE NAME
        case 'setfilename':
          $this->filename = $this->getValue($rule['from'], $line, $data);
          break;

        default:
          throw new Exception("Unknown rule action '{$rule['action']}' in exporter configuration", 1);
      }
    }
  }

  /**
   * get the value of a given soruce specification, which can be
   *  1) a simple variable name in $data
   *  2) access to the connected entities, e.g. contact.first_name.
   *     The following entities are supported:
   *     contact       - the contact linked the segment
   *     campaign      - the contact linked the segment
   *     segment       - the segment itself
   *     phone_primary - the contact's main phone
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
    if (preg_match('#^(?P<entity>\w+)[.](?P<attribute>[\w.]+)$#', $source, $entity_source)) {
      $entity = $this->getEntity($entity_source['entity'], $line, $data);
      $attribute_name = $this->resolveCustomField($entity_source['attribute']);
      if (isset($entity[$attribute_name])) {
        return $entity[$attribute_name];
      } else {
        return '';
      }
    }

    // it doesn't exist yet and is not entity source -> return empty string
    return '';
  }

  /**
   * if the given field_name is a custom field, but
   * NOT the custom_\d+ format (e.g. custom_42),
   * it will try to look up the custom field ID,
   * accepting the following formats:
   *  1) custom_<CustomField.name>
   *  2) custom_<CustomGroup.name>.<CustomField.name>
   *
   * @return the currected (resolved) custom field name
   */
  protected function resolveCustomField($field_name) {
    $prefix = substr($field_name, 0, 7);
    if ($prefix == 'custom_') {
      $suffix = substr($field_name, 7);
      if (!is_numeric($suffix)) {
        $path = split("\\.", $suffix);
        $field = NULL;
        if (count($path) == 1) {
          $field = $this->getField($field_name, array('name' => $path[0]));
        } else {
          $field = $this->getField($field_name, array('name' => $path[1], 'custom_group_id' => $path[0]));
        }
        if ($field['id']) {
          // custom field found
          $field_name = 'custom_' . $field['id'];
        }
      }
    }
    return $field_name;
  }

  /**
   * will load a custom field
   */
  protected function getField($key, $parameters) {
    if (!isset($this->_fieldCache[$key])) {
      // not found -> load
      $parameters['return'] = 'name,id';
      try {
        $field = civicrm_api3('CustomField', 'getsingle', $parameters);
      } catch (Exception $e) {
        // field not found
        $field = array('id' => 0, 'name' => 'not found');
      }
      $this->_fieldCache[$key] = $field;
    }
    return $this->_fieldCache[$key];
  }

  /**
   * get entity related to this line, see documentation for ::executeRules
   */
  protected function getEntity($entity_name, $line, $data) {
    switch (strtolower($entity_name)) {
      case 'contact':
        if (!isset($this->_contacts[$line['contact_id']])) {
          // error_log("CACHE MISS: Contact.{$line['contact_id']}");
          $this->_contacts[$line['contact_id']] = civicrm_api3('Contact', 'getsingle', array('id' => $line['contact_id']));
        }
        return $this->_contacts[$line['contact_id']];

      case 'segment':
        return $line;

      case 'membership':
        if (!empty($line['membership_id'])) {
          if (!isset($this->_memberships[$line['membership_id']])) {
            // error_log("CACHE MISS: Membership.{$line['membership_id']}");
            $this->_memberships[$line['membership_id']] = civicrm_api3('Membership', 'getsingle', array('id' => $line['membership_id']));
          }
          return $this->_memberships[$line['membership_id']];
        } else {
          // no membership added
          return NULL;
        }

      case 'campaign':
        if ($this->_campaign == NULL || $this->_campaign['id'] != $line['campaign_id']) {
          // error_log("CACHE MISS: Campaign.{$line['campaign_id']}");
          $this->_campaign = civicrm_api3('Campaign', 'getsingle', array('id' => $line['campaign_id']));
        }
        return $this->_campaign;

      case 'phone_primary':
        return $this->getDetailEntity($line, 'Phone', array('is_primary' => 1));

      case 'phone_phone':
        return $this->getDetailEntity($line, 'Phone', array('phone_type_id' => 1));

      case 'phone_mobile':
        return $this->getDetailEntity($line, 'Phone', array('phone_type_id' => 2));

      default:
        // maybe it's in the data
        if (isset($data[$entity_name]) && is_array($data[$entity_name])) {
          return $data[$entity_name];
        } else {
          throw new Exception("Unkown entity '{$entity_name}' requested", 1);
        }
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

  /**
   * load an arbitrary entity
   */
  protected function loadEntity($entity_type, $parameter_specs, $required_parameters, $line, &$data, $cached) {
    // get parameters
    $params = $this->getQueryParams($parameter_specs, $data, $line);
    $params['option.limit'] = 2;
    $cache_key = NULL;

    // check if required params are present
    foreach ($required_parameters as $required_parameter) {
      if (!isset($params[$required_parameter]) || $params[$required_parameter]=='') {
        // required parameter not set
        return array();
      }
    }

    if ($cached) {
      $cache_key = sha1($entity_type . json_encode($params));
      if (isset($this->_loadCache[$cache_key])) {
        return $this->_loadCache[$cache_key];
      }
    }

    $result = civicrm_api3($entity_type, 'get', $params);
    if ($result['count'] == 1) {
      // found
      $entity = reset($result['values']);
    } else {
      // not found / not unique
      $entity = array();
    }

    if ($cached) {
      $this->_loadCache[$cache_key] = $entity;
    }
    return $entity;
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
   **                LOOPING                      **
   *************************************************/

  protected $loop_status = NULL;
  protected $loop_stack  = array();

  /**
   * this allows you to loop over certain entities
   *
   * the first time around, this will initialise
   *
   */
  protected function loopNext(&$data, $line) {
    if (empty($this->config['loop'])) {
      // THERE IS NO LOOP -> just do it once:
      if ($this->loop_status === NULL) {
        // FIRST TIME
        $this->loop_status = TRUE;
        return TRUE;
      } else {
        // SECOND TIME
        $this->loop_status = NULL;
        return FALSE;
      }

    } else {
      // THERE IS A LOOP!
      try {
        if ($this->loop_status === NULL) {
          // init
          $this->loop_status = array();
          $this->loop_stack  = array();
          $this->pushStack($data, $line);
          return TRUE;
        } else {
          return $this->getNextStack($data, $line);
        }
      } catch (Exception $e) {
        // something went wrong...
        $this->loop_stack = array();
        $this->loop_status = NULL;
        error_log($e->getMessage());
        return FALSE;
      }
    }
  }

  /**
   * get the next stack status
   */
  protected function getNextStack(&$data, $line) {
    // if we're out of stack, that's it
    if (empty($this->loop_stack)) {
      $this->loop_status = NULL;
      return FALSE;
    }

    // let's look at the current status
    $current_loop  = end($this->loop_stack);
    $current_index = end($this->loop_status);
    $current_depth = count($this->loop_stack);
    $current_spec  = $this->config['loop'][$current_depth-1];

    // try to get next item
    $current_index += 1;
    if ($current_index >= count($current_loop)) {
      // out of items -> go back on level
      array_pop($this->loop_stack);
      array_pop($this->loop_status);

      // then try again one level below
      return $this->getNextStack($data, $line);

    } else {
      // item still available
      $data[$current_spec['name']] = $current_loop[$current_index];

      // mark the new status
      array_pop($this->loop_status);
      $this->loop_status[] = $current_index;

      // populate rest of the stack (if not leaf)
      $this->pushStack($data, $line);

      // that's it
      return TRUE;
    }
  }

  /**
   * push the next item on the stack
   */
  protected function pushStack(&$data, $line) {
    $current_level = count($this->loop_stack);
    if ($current_level >= count($this->config['loop'])) {
      return;
    }

    $loop_spec = $this->config['loop'][$current_level];

    // load data
    $query_params = $this->getQueryParams($loop_spec['params'], $data, $line);
    $query_params['sequential'] = 1;
    $query_params['option.limit'] = 0;
    $query_result = civicrm_api3($loop_spec['type'], 'get', $query_params);

    // push to stack
    $this->loop_status[] = 0;
    $this->loop_stack[]  = $query_result['values'];

    // mark in data
    $data[$loop_spec['name']] = $query_result['count'] ? $query_result['values'][0] : array();

    // push all the way to the end
    if (count($this->loop_stack) < count($this->config['loop'])) {
      $this->pushStack($data, $line);
    }
  }

  /**
   * get the API parameters from the query spec
   */
  protected function getQueryParams($spec, $data, $line) {
    $parameters = array();
    foreach ($spec as $key => $value) {
      if (substr($value, 0, 4) == 'var:') {
        $value = $this->getValue(substr($value, 4), $line, $data);
      }
      $parameters[$key] = $value;
    }
    return $parameters;
  }


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
      if (isset($rule['from']) && preg_match('#^(?P<entity>\w+)[.](?P<attribute>[\w.]+)$#', $rule['from'], $entity_source)) {
        $attribute_name = $this->resolveCustomField($entity_source['attribute']);
        switch (strtolower($entity_source['entity'])) {
          case 'contact':
            $contact_fields[$attribute_name] = 1;
            break;

          case 'membership':
            $membership_fields[$attribute_name] = 1;
            break;

          case 'phone_primary':
            $phone_types['P'] = 1; # normal phone
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
    if (!empty($contact_fields) && !empty($contact_ids)) {
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
    if (!empty($membership_fields) && !empty($membership_ids)) {
      $membership_query = civicrm_api3('Membership', 'get', array(
        'id'           => array('IN' => $membership_ids),
        'option.limit' => 0,
        // FIXME: this doesn't really work:
        // 'return'       => implode(',', array_keys($membership_fields))
        ));
      foreach ($membership_query['values'] as $membership) {
        $this->_memberships[$membership['id']] = $membership;
      }
    }

    // load phone data
    if (!empty($phone_types) && !empty($contact_ids)) {
      // create array
      foreach ($contact_ids as $contact_id) {
        $this->_details[$contact_id]['phone'] = array();
      }
      $phone_query = civicrm_api3('Phone', 'get', array(
        'contact_id'    => array('IN' => $contact_ids),
        'option.limit'  => 0,
        ));
      // if specific phone types (not primary) are requested, restrict to those
      if (!in_array('P', $phone_types)) {
        $phone_query['phone_type_id'] = array('IN' => array_keys($phone_types));
      }
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
