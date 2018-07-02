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
 * @return string - class name for tag
 *
 * @param $tagName
 * @return string
 */
function apic_api_tag_to_class($tagName) {
  ibm_apim_entry_trace(__FUNCTION__, $tagName);
  $asInt = 0;
  for ($i = 0; $i < mb_strlen($tagName); $i++) {
    $asInt += ord($tagName[$i]);
  }
  $asInt = $asInt % 19;
  ibm_apim_exit_trace(__FUNCTION__, $asInt);
  return "apiTag" . $asInt;
}

/**
 * @return string - api icon for a given name
 *
 * @param $apiName
 * @return string
 */
function apic_api_random_image($apiName) {
  ibm_apim_entry_trace(__FUNCTION__, $apiName);
  $asInt = 0;
  for ($i = 0; $i < mb_strlen($apiName); $i++) {
    $asInt += ord($apiName[$i]);
  }
  $digit = $asInt % 19;
  if ($digit == 0) {
    $digit = 1;
  }
  $num = str_pad($digit, 2, 0, STR_PAD_LEFT);
  ibm_apim_exit_trace(__FUNCTION__, $num);
  return "api_" . $num . ".png";
}

/**
 * take a bytearray, write it out to a zip file, and then read in
 * concatenating all file content into a string
 *
 * @param $data
 * @return string
 */
function apic_api_get_string_from_zip($data) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  $draft = "";
  $file = \Drupal::service('file_system')->realpath(file_unmanaged_save_data($data,\Drupal\Core\Site\Settings::get('file_public_path')));
  $tempdir = apic_api_tempdir(file_directory_temp());
  $zip = new ZipArchive();
  if ($zip->open($file) === TRUE) {
    $zip->extractTo($tempdir);
    $zip->close();

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempdir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $fileinfo) {
      $filename = $fileinfo->getFilename();
      $pathname = $fileinfo->getPathname();
      $fourcharext = mb_strtolower(mb_substr($filename, -4));
      $threecharext = mb_strtolower(mb_substr($filename, -3));
      if ($fourcharext == "wsdl" || $threecharext == "xml" || $threecharext == "xsd") {
        $draft .= $filename . '\n';
        $handle = fopen($pathname, "r");
        $contents = fread($handle, filesize($pathname));
        fclose($handle);
        $handle = NULL;
        // remove BOM
        $contents = str_replace("\xEF\xBB\xBF", '', $contents);
        $draft .= $contents . '\n';
      }
    }
  }
  // clear up zip and temp extract dir
  file_unmanaged_delete($file);
  apic_api_deletedir($tempdir);
  ibm_apim_exit_trace(__FUNCTION__, NULL);
  return $draft;
}

/**
 * utility function to create and return the name of a temporary directory
 *
 * @param bool $dir
 * @param string $prefix
 * @return string
 */
function apic_api_tempdir($dir = FALSE, $prefix = 'wsdlzip') {
  ibm_apim_entry_trace(__FUNCTION__, array($dir, $prefix));
  $returnValue = NULL;
  $tempfile = tempnam($dir, $prefix);
  if (file_exists($tempfile)) {
    unlink($tempfile);
  }
  mkdir($tempfile);
  if (is_dir($tempfile)) {
    $returnValue = $tempfile;
  }
  ibm_apim_exit_trace(__FUNCTION__, $returnValue);
  return $returnValue;
}

/**
 * utility function to recursively delete a directory
 *
 * @param $dir
 */
function apic_api_deletedir($dir) {
  ibm_apim_entry_trace(__FUNCTION__, $dir);
  if (is_dir($dir)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $fileinfo) {
      $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
      $todo($fileinfo->getRealPath());
    }
    rmdir($dir);
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * This function exists to avoid issues caused by the json {} and [] looking the same in PHP!
 * Only option is to remove them from the structure.
 *
 * @param $yaml
 * @return mixed
 */
function apic_api_remove_empty_elements($yaml) {
  if (isset($yaml)) {
    // if empty securityDefinitions and others then needs to be stdClass not array
    foreach (array(
               'securityDefinitions',
               'responses',
               'parameters',
               'definitions',
               'paths',
               'contact',
               'license'
             ) as $key) {
      if (isset($yaml[$key])) {
        if (is_array($yaml[$key]) && empty($yaml[$key])) {
          unset($yaml[$key]);
        } elseif (is_array($yaml[$key])) {
          $yaml[$key] = tidyYaml($yaml[$key]);
        }
      }
    }
    if (isset($yaml['paths'])) {
      foreach ($yaml['paths'] as $pathname => $path) {
        foreach ($path as $verb => $op) {
          if (isset($op['responses'])) {
            foreach ($op['responses'] as $code => $response) {
              $yaml['paths'][$pathname][$verb]['responses'][$code] = tidyYaml($yaml['paths'][$pathname][$verb]['responses'][$code]);
            }
          }
        }
      }
    }
  }
  return $yaml;
}

/**
 * Utility method to recurse through the yaml doc and tidy up trailing empty objects / empty arrays
 *
 * @param $yaml
 * @return array
 */
function tidyYaml(&$yaml) {
  unset($prop);
  if (is_array($yaml)) {
    foreach ($yaml as $prop => $value) {
      if ($prop == 'schema') {
        if (is_array($yaml[$prop]) && empty($yaml[$prop])) {
          unset($yaml[$prop]);
        } elseif (is_array($yaml[$prop])) {
          $yaml[$prop] = tidyYaml($yaml[$prop]);
        }
      }
      elseif ($prop == 'properties') {
        if (is_array($yaml[$prop]) && empty($yaml[$prop])) {
          unset($yaml[$prop]);
          // handle empty objects now we've removed possibly their only attribute
          if (is_array($yaml) && empty($yaml)) {
            $yaml['type'] = 'object';
          }
        } elseif (is_array($yaml[$prop])) {
          $yaml[$prop] = tidyYaml($yaml[$prop]);
        }
      } elseif (is_array($yaml[$prop])) {
        $yaml[$prop] = tidyYaml($yaml[$prop]);
      }
    }
  }
  return $yaml;
}
