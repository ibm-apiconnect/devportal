<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * Save the WSDL data as a file on the fs for serving up to clients
 * @param $apiID
 * @param $data
 * @param $filename
 * @return bool|null|\stdClass
 */
function apic_api_save_wsdl($apiID, $data, $filename = NULL) {
  ibm_apim_entry_trace(__FUNCTION__, $apiID);
  try {
    $apiimgdir = 'private://apiwsdl';
    file_prepare_directory($apiimgdir, FILE_CREATE_DIRECTORY);

    if ($data == NULL) {
      $file_temp = NULL;
    }
    else {
      if (isset($filename)) {
        $filename = str_replace(array('\'', '"'), '', $filename);
        $filename = urlencode($filename);
      }
      if (!isset($filename)) {
        $filename = 'API.wsdl';
      }
      $apidir = $apiimgdir . '/' . $apiID;
      file_prepare_directory($apidir, FILE_CREATE_DIRECTORY);

      // Saves a file to the specified destination and creates a database entry.
      $file_temp = file_save_data($data, $apiimgdir . '/' . $apiID . '/' . $filename, FILE_EXISTS_REPLACE);
    }
    if (isset($file_temp)) {
      \Drupal::logger('apic_api')->notice('Saved WSDL to filesystem for apiID: %apiID, filename: %filename', array('%apiID' => $apiID, '%filename' => $filename));
    } else {
      \Drupal::logger('apic_api')->notice('Failed to save WSDL to filesystem for apiID: %apiID', array('%apiID' => $apiID));
    }
    ibm_apim_exit_trace(__FUNCTION__, $apiimgdir . '/' . $apiID . '/' . $filename);
    return $file_temp;
  } catch (Exception $e) {
    \Drupal::logger('apic_api')->notice('Save of WSDL to filesystem failed with: %data', array('%data' => $e->getMessage()));
    ibm_apim_exit_trace(__FUNCTION__, NULL);
    return NULL;
  }
}