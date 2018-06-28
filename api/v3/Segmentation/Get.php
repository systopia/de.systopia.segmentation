<?php

use CRM_Segmentation_ExtensionUtil as E;

/**
 * Segmentation.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_segmentation_get_spec(&$spec) {
  $spec = [
    'id' => [
      'title' => 'Segmentation ID',
      'api.required' => TRUE,
      'type' => CRM_Utils_Type::T_INT,
    ],
  ];
}

/**
 * Segmentation.Get API
 *
 * @param array $params
 *
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_segmentation_get($params) {
  if (empty($params['id'])) {
    return civicrm_api3_create_error('Parameter "id" is required.');
  }
  $query = CRM_Core_DAO::executeQuery("SELECT id, name FROM civicrm_segmentation_index WHERE civicrm_segmentation_index.id=%0", [
    [
      $params['id'],
      'Integer',
    ],
  ]);
  $query->fetch();
  return civicrm_api3_create_success(
    [
      [
        'id' => $query->id,
        'name' => $query->name,
      ],
    ],
    $params,
    'Segmentation',
    'Get'
  );

}
