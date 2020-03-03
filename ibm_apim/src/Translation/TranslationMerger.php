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

namespace Drupal\ibm_apim\Translation;

/**
 * Class MergeDrop
 *
 * Translated strings are delivered back in files which contain just the strings which have been newly translated.
 * These need to be merged back with the translation memories which are the strings which were previously translated.
 * This can then be compared to the .pot file (the full set of strings) to verify everything has been translated.
 *
 * @package Drupal\ibm_apim\Translation
 *
 */
class TranslationMerger {

  private $projects;

  private $languages;

  private $new_translation_files_location;

  private $export_files_location;

  private $output_location;

  /**
   * Constructor
   *
   * @param $new_translation_files_location
   *   directory containing new translation files.
   * @param $export_files_location
   *   directory containing files which were provided to the translation centres, i.e. .pot files + .memories.<lang>.po files.
   * @param $output_location
   *   base dir for files to be output to.
   * @param array $languages
   */
  public function __construct($new_translation_files_location, $export_files_location, $output_location, $languages = []) {

    $this->new_translation_files_location = $new_translation_files_location;
    $this->export_files_location = $export_files_location;

    $this->projects = [];
    foreach (glob($export_files_location . '/*', GLOB_ONLYDIR) as $projectpath) {
      $this->projects[] = $projectpath;
    }

    // Single file project processing:
    //    $this->projects[] = '/tmp/translation_files/dev2tc/account_field_split-8.x-1.0-alpha2';

    $this->output_location = $output_location;

    //        // single language:
    //        $this->languages = array('cs');
    $this->languages = $languages ? $languages : $this->getAllLanguages();

  }

  /**
   * Iterate over the projects.
   * We use the .pot file as the source of truth, and iterate over the other files based on that.
   */
  public function merge(): void {

    $this->checkDir($this->output_location);
    $this->logDirs();

    foreach ($this->projects as $project_path) {

      $project = basename($project_path);
      echo 'vvvvv Processing: ' . $project . ' vvvvv\n';
      $pot_file = $project_path . '/' . $project . '.pot';
      if (!file_exists($pot_file)) {
        echo "ERROR: No .pot file found for $project. Looked for $pot_file \n";
      }
      else {
        $project_output_dir = $this->output_location . '/' . $project;
        $this->checkDir($project_output_dir);

        foreach ($this->languages as $language) {
          echo '  vvvvv ' . $language . ' vvvvv\n';
          $memory_items = $this->getMemoryItems($project, $language);
          $new_items = $this->getNewTranslationItems($project, $language);
          $complete_items = $this->buildCompleteTranslationFile($pot_file, $memory_items, $new_items);
          $this->writeOutput($complete_items, $language, $project, $project_output_dir);
          echo '  ^^^^^ ' . $language . ' ^^^^^\n';
        }

      }
      echo '^^^^^ Completed: ' . $project . ' ^^^^^\n\n';
    } // projects

  }

  /**
   *
   * Get all active languages that drupal knows about.
   * If it is present strip out english.
   *
   * @return array
   *  Language keys.
   *
   */
  private function getAllLanguages(): array {
    $langs = array_keys(\Drupal::languageManager()->getLanguages());
    $index = array_search('en', $langs, FALSE);
    if ($index !== FALSE) {
      unset($langs[$index]);
    }
    return $langs;
  }

  private function logDirs(): void {
    echo '=============================\n';
    echo 'New translation files:      ' . $this->new_translation_files_location . "\n";
    echo 'Exported translation files: ' . $this->export_files_location . "\n";
    echo 'Output directory:           ' . $this->output_location . "\n";
    echo '=============================\n\n';
  }

  /**
   *
   * Check a dir exists, if not attempt to create it.
   *
   * @param $path
   *
   * @throws \Exception
   */
  protected function checkDir($path): void {
    if (!file_exists($path) && !is_dir($path)) {
      $success = mkdir($path);
      if (!$success) {
        throw new \Exception('error creating output directory: ' . $path);
      }
    }
  }

  /**
   * @param string $filename
   *  .po/ .pot file for reading in.
   *
   * @return array
   *  PoItem array representing the strings in the translation files.
   */
  protected function getTranslationFileItems(string $filename): ?array {

    if (!file_exists($filename)) {
      echo 'Not found: ' . $filename . "\n";
      return NULL;
    }
    else {
      echo 'Found: ' . $filename . "\n";
      $reader = new TranslationFileReader($filename);
      return $reader->getItems();
    }

  }

  /**
   * Get memories files. These were the translations already available to the translation centre before this drop.
   *
   * @param string $projectName
   *   Project name.
   * @param string $language
   *   Language.
   *
   * @return array
   *   PoItem array
   */
  private function getMemoryItems(string $projectName, string $language): ?array {
    // e.g. acl-8.x-1.0-alpha1-memories.de.po
    $filename = $this->export_files_location . '/' . $projectName . '/' . $projectName . '-memories.' . $language . '.po';
    return $this->getTranslationFileItems($filename);

  }

  /**
   * Get new translation files. These are the new translation files returned.
   *
   * @param string $projectName
   *   Project name.
   * @param string $language
   *   Language.
   *
   * @return array
   *   PoItem array
   */
  private function getNewTranslationItems(string $projectName, string $language): ?array {

    // versions might have changed so we need to search wildcards for the version.
    $projectNameVersion = $this->splitProjectNameVersion($projectName);

    if (isset($projectNameVersion['version'])) {
      $project_search_wildcard = $projectNameVersion['name'] . '-*';
    }
    else {
      $project_search_wildcard = $projectNameVersion['name'];
    }

    $list = glob($this->new_translation_files_location . '/' . $project_search_wildcard . '/' . $project_search_wildcard . '.' . $language . '.po');

    if (\sizeof($list) > 1) {
      echo 'Multiple new translation files found for ' . $projectNameVersion['name'] . ':' . \PHP_EOL;
      \var_dump($list);
      echo 'Using the first one from the list' . \PHP_EOL;
    }

    if (\sizeof($list) === 0) {
      echo 'No new translation files found for ' . $projectNameVersion['name'] . \PHP_EOL;
      return NULL;
    }
    else {
      $filename = \array_shift($list);
      return $this->getTranslationFileItems($filename);
    }
  }

  /**
   * @param string $pot_file
   * @param $memory_items
   * @param $newtranslation_items
   *
   * @return array
   */
  private function buildCompleteTranslationFile(string $pot_file, $memory_items, $newtranslation_items) : ?array{
    $reader = new TranslationFileReader($pot_file);
    $items = $reader->getItems();
    $complete_items = [];
    foreach ($items as $item) {
      $got_memory = FALSE;
      $got_new = FALSE;
      if ($memory_items !== NULL) {
        foreach ($memory_items as $mem) {
          if ($item->getSource() === $mem->getSource()) {
            $complete_items[] = $mem;
            $got_memory = TRUE;
          }
        }
        // if we have found a match no need to carry on.
        if ($got_memory) {
          continue;
        }
      }

      if ($newtranslation_items !== NULL) {
        foreach ($newtranslation_items as $new) {
          if ($item->getSource() === $new->getSource()) {
            $complete_items[] = $new;
            $got_new = TRUE;
          }
        }
        // if we have found a match no need to carry on.
        if ($got_new) {
          continue;
        }
      }

      // this means we haven't found any translations. Notify of this as it suggests something has been missed in our file processing/ translation process.
      // this is not added to the list of strings otherwise we might load an empty string as the translation.
      $notfound = is_array($item->getSource()) ? serialize($item->getSource()) : $item->getSource();
      echo 'NO TRANSLATION FOUND FOR: ' . $notfound . "\n";

    } // items

    return $complete_items;

  }

  /**
   * @param array $complete_items
   *   PoItems - complete set of translations.
   * @param string $language
   *   Language.
   * @param string $project
   *   Project name + version string.
   * @param string $dir
   *   Output directory.
   */
  private function writeOutput(array $complete_items, string $language, string $project, string $dir) : void{
    $filename = $dir . '/' . $project . '.' . $language . '.po';
    new TranslationFileWriter($complete_items, $filename);
    echo 'Written: ' . basename($filename) . "\n";
  }

  /**
   * Split a project into name-version. Version is optional.
   *
   * @param $project
   *
   * @return array
   */
  private function splitProjectNameVersion($project): array {
    $split = explode('-', $project, 2);

    $returnProject = [];

    $returnProject['name'] = $split[0];
    if (sizeof($split) > 1) {
      $returnProject['version'] = $split[1];
    }
    return $returnProject;
  }


}
