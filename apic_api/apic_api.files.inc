<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\Core\File\FileSystemInterface;

/**
 * Save the WSDL data as a file on the fs for serving up to clients
 *
 * @param $apiId
 * @param $data
 * @param $fileName
 *
 * @return bool|null|\Drupal\file\FileInterface
 */
function apic_api_save_wsdl($apiId, $data, $fileName = NULL) {
  ibm_apim_entry_trace(__FUNCTION__, $apiId);
  try {
    $apiImgDir = 'private://apiwsdl';
    \Drupal::service('file_system')->prepareDirectory($apiImgDir, FileSystemInterface::CREATE_DIRECTORY);

    if ($data === NULL) {
      $fileTemp = NULL;
    }
    else {
      if ($fileName !== NULL) {
        $fileName = str_replace(['\'', '"'], '', $fileName);
        $fileName = urlencode($fileName);
      }
      if ($fileName === NULL) {
        $fileName = 'API.wsdl';
      }
      $apiDir = $apiImgDir . '/' . $apiId;
      \Drupal::service('file_system')->prepareDirectory($apiDir, FileSystemInterface::CREATE_DIRECTORY);

      // Saves a file to the specified destination and creates a database entry.
      $fileTemp = \Drupal::service('file.repository')->writeData($data, $apiImgDir . '/' . $apiId . '/' . $fileName, FileSystemInterface::EXISTS_REPLACE);
    }
    if ($fileTemp !== NULL) {
      \Drupal::logger('apic_api')->notice('Saved WSDL to filesystem for apiID: %apiID, filename: %filename', [
        '%apiID' => $apiId,
        '%filename' => $fileName,
      ]);
    }
    else {
      \Drupal::logger('apic_api')->notice('Failed to save WSDL to filesystem for apiID: %apiID', ['%apiID' => $apiId]);
    }
    ibm_apim_exit_trace(__FUNCTION__, $apiImgDir . '/' . $apiId . '/' . $fileName);
    return $fileTemp;
  } catch (Throwable $e) {
    \Drupal::logger('apic_api')->notice('Save of WSDL to filesystem failed with: %data', ['%data' => $e->getMessage()]);
    ibm_apim_exit_trace(__FUNCTION__, NULL);
    return NULL;
  }
}