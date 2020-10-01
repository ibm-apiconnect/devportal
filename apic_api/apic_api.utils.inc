<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use \Drupal\Core\Site\Settings;
/**
 * @param $tagName
 *
 * @return string
 */
function apic_api_tag_to_class($tagName): string {
  ibm_apim_entry_trace(__FUNCTION__, $tagName);
  $asInt = 0;
  $strLength = mb_strlen($tagName);
  for ($i = 0; $i < $strLength; $i++) {
    $asInt += ord($tagName[$i]);
  }
  $asInt %= 19;
  ibm_apim_exit_trace(__FUNCTION__, $asInt);
  return 'apiTag' . $asInt;
}

/**
 * @param $apiName
 *
 * @return string
 */
function apic_api_random_image($apiName): string {
  ibm_apim_entry_trace(__FUNCTION__, $apiName);
  $asInt = 0;
  $strLength = mb_strlen($apiName);
  for ($i = 0; $i < $strLength; $i++) {
    $asInt += ord($apiName[$i]);
  }
  $digit = $asInt % 19;
  if ($digit === 0) {
    $digit = 1;
  }
  $num = str_pad($digit, 2, 0, STR_PAD_LEFT);
  ibm_apim_exit_trace(__FUNCTION__, $num);
  return 'api_' . $num . '.png';
}

/**
 * take a byte array, write it out to a zip file, and then read in
 * concatenating all file content into a string
 *
 * @param $data
 *
 * @return string
 */
function apic_api_get_string_from_zip($data): string {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  $draft = '';
  $fileSystemService = \Drupal::service('file_system'); 
  $file = $fileSystemService->realpath($fileSystemService->saveData($data, Settings::get('file_public_path')));
  $tempDir = apic_api_tempdir($fileSystemService->getTempDirectory());
  $zip = new ZipArchive();
  if ($zip->open($file) === TRUE) {
    $zip->extractTo($tempDir);
    $zip->close();

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $fileInfo) {
      $fileName = $fileInfo->getFilename();
      $pathName = $fileInfo->getPathname();
      $fourCharExt = mb_strtolower(mb_substr($fileName, -4));
      $threeCharExt = mb_strtolower(mb_substr($fileName, -3));
      if ($fourCharExt === 'wsdl' || $threeCharExt === 'xml' || $threeCharExt === 'xsd') {
        $draft .= $fileName . '\n';
        $handle = fopen($pathName, 'rb');
        $contents = fread($handle, filesize($pathName));
        fclose($handle);
        $handle = NULL;
        // remove BOM
        $contents = str_replace("\xEF\xBB\xBF", '', $contents);
        $draft .= $contents . '\n';
      }
    }
  }
  // clear up zip and temp extract dir
  $fileSystemService->delete($file);
  apic_api_deletedir($tempDir);
  ibm_apim_exit_trace(__FUNCTION__, NULL);
  return $draft;
}

/**
 * utility function to create and return the name of a temporary directory
 *
 * @param bool $dir
 * @param string $prefix
 *
 * @return null|string
 */
function apic_api_tempdir($dir = FALSE, $prefix = 'wsdlzip'): ?string {
  ibm_apim_entry_trace(__FUNCTION__, [$dir, $prefix]);
  $returnValue = NULL;
  $tempFile = tempnam($dir, $prefix);
  if (file_exists($tempFile)) {
    unlink($tempFile);
  }
  mkdir($tempFile);
  if (is_dir($tempFile)) {
    $returnValue = $tempFile;
  }
  ibm_apim_exit_trace(__FUNCTION__, $returnValue);
  return $returnValue;
}

/**
 * utility function to recursively delete a directory
 *
 * @param string $dir
 */
function apic_api_deletedir($dir): void {
  ibm_apim_entry_trace(__FUNCTION__, $dir);
  if (is_dir($dir)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $fileInfo) {
      $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
      $todo($fileInfo->getRealPath());
    }
    rmdir($dir);
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * This function exists to avoid issues caused by the json {} and [] looking the same in PHP!
 * Only option is to remove them from the structure.
 *
 * @param array $yaml
 *
 * @return array
 */
function apic_api_remove_empty_elements($yaml): array {
  if ($yaml !== NULL) {
    // if empty securityDefinitions and others then needs to be stdClass not array
    foreach ([
               'securityDefinitions',
               'responses',
               'parameters',
               'definitions',
               'paths',
               'contact',
               'license',
             ] as $key) {
      if (isset($yaml[$key])) {
        $isArray = is_array($yaml[$key]);
        if ($isArray === TRUE && empty($yaml[$key])) {
          unset($yaml[$key]);
        }
        elseif ($isArray === TRUE) {
          $yaml[$key] = tidyYaml($yaml[$key]);
        }
      }
    }
    if (isset($yaml['paths'])) {
      foreach ($yaml['paths'] as $pathName => $path) {
        if (is_array($yaml['paths'][$pathName]) && empty($yaml['paths'][$pathName])) {
          unset($yaml['paths'][$pathName]);
        }
        else {
          foreach ($path as $verb => $op) {
            if (isset($op['responses'])) {
              foreach ($op['responses'] as $code => $response) {
                $yaml['paths'][$pathName][$verb]['responses'][$code] = tidyYaml($yaml['paths'][$pathName][$verb]['responses'][$code]);
              }
            }
          }
        }
      }
    }
    // remove empty security requirements too
    if (isset($yaml['security'])) {
      foreach ($yaml['security'] as $key => $security) {
        if (is_array($yaml['security'][$key]) && empty($yaml['security'][$key])) {
          unset($yaml['security'][$key]);
        }
      }
    }
    // remove empty contact
    if (isset($yaml['info']['contact']) && is_array($yaml['info']['contact']) && empty($yaml['info']['contact'])) {
      unset($yaml['info']['contact']);
    }
  }
  return $yaml;
}

/**
 * Utility method to recurse through the yaml doc and tidy up trailing empty objects / empty arrays
 *
 * @param array $yaml
 *
 * @return array
 */
function tidyYaml(&$yaml): array {
  unset($prop);
  $isArray = is_array($yaml);
  if ($isArray) {
    foreach ($yaml as $prop => $value) {
      $isArrayProp = is_array($yaml[$prop]);
      if ($prop === 'schema') {
        if ($isArrayProp === TRUE && empty($yaml[$prop])) {
          unset($yaml[$prop]);
        }
        elseif ($isArrayProp === TRUE) {
          $yaml[$prop] = tidyYaml($yaml[$prop]);
        }
      }
      elseif ($prop === 'properties') {

        if ($isArrayProp === TRUE && empty($yaml[$prop])) {
          unset($yaml[$prop]);
          // handle empty objects now we've removed possibly their only attribute
          if ($isArray === TRUE && empty($yaml)) {
            $yaml['type'] = 'object';
          }
        }
        elseif ($isArrayProp === TRUE) {
          $yaml[$prop] = tidyYaml($yaml[$prop]);
        }
      }
      elseif ($isArrayProp === TRUE) {
        $yaml[$prop] = tidyYaml($yaml[$prop]);
      }
    }
  }
  return $yaml;
}
