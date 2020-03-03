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
 * Class TranslationPreparation
 *
 * Calculates which translations (.po) are available for a set of strings (.pot).
 * Builds a collection of ProjectInfo objects which contains all that is needed to
 * produce what is needed by the translation centres.
 *
 * @package Drupal\ibm_apim\Translation
 */
class TranslationPreparation {

  private $projectInfos;

  private $ibm_modules = [
    'apim_profile',
    'apic_api',
    'apic_app',
    'auth_apic',
    'connect_theme',
    'consumerorg',
    'eventstream',
    'featuredcontent',
    'ghmarkdown',
    'ibm_apim',
    'mail_subscribers',
    'product',
    'socialblock',
    'themegenerator',
  ];

  /**
   * TranslationPreparation constructor.
   *
   * @param $pot_files_location
   *   directory containing pot files, i.e. complete list of strings.
   * @param $drupal_po_files_location .
   *   directory containing drupal po files, i.e. existing translations from localize.drupal.org.
   * @param $output_location
   *   base dir for files to be output to.
   * @param $platform_dir
   *   platform directory on the portal node.
   * @param $merge_dir
   *   directory to merge existing translations into.
   */
  public function __construct($pot_files_location, $drupal_po_files_location, $output_location, $platform_dir, $merge_dir) {

    echo "Parameters passed to TranslationPreparation:\n";
    echo "\tpot_files_location=$pot_files_location\n";
    echo "\tdrupal_po_files_location=$drupal_po_files_location\n";
    echo "\toutput_location=$output_location\n";
    echo "\tplatform_dir=$platform_dir\n";
    echo "\tmerge_dir=$merge_dir\n";
    echo "\n\n";

    $this->scanPotDir($pot_files_location);
    $this->setOutputDirs($output_location);
    $this->mergeLatestTranslations($platform_dir, $drupal_po_files_location, $merge_dir);
    $this->addPosToProjectInfo($merge_dir);

  }

  /**
   * Explore the prepared ProjectInfo collection which has been built based on the .pot and .po files made available to this class.
   *
   * @return array
   *  Associative array [project => ProjectInfo object]
   *
   */
  public function getProjectInfos(): array {
    return $this->projectInfos;
  }

  /**
   * @param $potFilesLocation
   */
  private function scanPotDir($potFilesLocation): void {
    // TODO: pass through options for projects from drush.
    //$pots = array($potFilesLocation . '/openid_connect-8.x-1.0-beta3.pot');
    //$pots = array($potFilesLocation . '/workbench-8.x-1.0.pot');
    $pots = glob($potFilesLocation . '/*.pot');

    foreach ($pots as $pot) {

      // useful for debug as it speeds things up considerably ;)
      //      if ($pot === '/tmp/translation_files/required_pots/drupal-8.4.0.pot') {
      //        echo "SKIPPING DRUPAL MODULE...\n";
      //        continue;
      //      }

      $info = new ProjectInfo();
      $info->setPotFile($pot);

      $proj = basename($pot, '.pot');
      $name_version = $this->splitProjectNameVersion($proj);
      $info->setName($name_version['name']);
      if (isset($name_version['version'])) {
        $info->setVersion($name_version['version']);
      }

      $this->projectInfos[$proj] = $info;

    }
  }

  /**
   * @param $outputLocation
   *
   * @throws \Exception
   */
  private function setOutputDirs($outputLocation): void {
    if ($this->projectInfos === NULL || sizeof($this->projectInfos) === 0) {
      throw new \Exception('Trying to set output directories for no projects.');
    }

    foreach ($this->projectInfos as $key => $value) {
      $name_version = $this->splitProjectNameVersion($key);
      if (in_array($name_version['name'], $this->ibm_modules, FALSE)) {
        $module_type = 'ibm';
      }
      else {
        $module_type = 'drupal';
      }
      $value->setOutputDir($outputLocation . '/' . $module_type . '/' . $key);
      //echo "setting output dir to be " .  $this->outputLocation . '/' . $key . "\n";
    }

    $this->checkAndCreateDirectory($outputLocation);
    $this->checkAndCreateDirectory($outputLocation . '/ibm');
    $this->checkAndCreateDirectory($outputLocation . '/drupal');

  }

  /**
   *
   * In order to get a complete set of existing translations we need to use the latest available from
   * <platform_dir>/sites/all/translations and check whether there are any updates from drupal which would
   * supersede the ibm translations.
   *
   * @param $platform_dir
   * @param $poFilesLocation
   * @param $merge_dir
   *
   * @throws \Exception
   */
  private function mergeLatestTranslations($platform_dir, $poFilesLocation, $merge_dir): void {

    $current_translations_dir = $platform_dir . '/sites/all/translations';
    if (!is_dir($current_translations_dir)) {
      throw new \Exception("Current translations directory does not exist: $current_translations_dir");
    }

    $this->checkAndCreateDirectory($merge_dir);

    // Pick up each of the sets of .po files required. The ibm_translations are possibly based on older versions of the
    // projects so we ignore the version and match just on the project name. The drupal translations will be correct
    // because these are gathered based on the versions of projects running on the appliance.
    $ibm_translations = $this->scanPoDir($current_translations_dir, TRUE);
    $drupal_translations = $this->scanPoDir($poFilesLocation);

    foreach ($ibm_translations as $project => $langfiles) { // key = project name, value = [lang=>file]

      // for IBM developed projects the translation centre will always return the latest version of the files, so
      // don't provide a merged output for this, which will mean later on we just use the latest copy.
      $proj_name_version = $this->splitProjectNameVersion($project);
      if (in_array($proj_name_version['name'], $this->ibm_modules, FALSE)) {
        echo 'Skipping merge of ibm project: ' . $project . "\n";
      }
      else {

        echo 'Merging project: ' . $project . "\n";
        // if not in drupal translations, just add this.
        if (!array_key_exists($project, $drupal_translations) || empty($drupal_translations[$project])) {
          echo "  No drupal translations, copying straight across to merge directory.\n";
          $this->copyPoFiles($langfiles, $merge_dir);
        }
        else {
          // need to diff these files.
          echo "  Drupal translations exists, checking individual languages.\n";
          $drupal_translation = $drupal_translations[$project];
          foreach ($langfiles as $lang => $file) {
            echo "    Checking $lang\n";
            if (!array_key_exists($lang, $drupal_translation)) {
              echo "      No drupal translation for $lang, copying straight across to merge directory.\n";
              $this->copySinglePoFile($file, $merge_dir);
            }
            else {
              echo "      Drupal translation exists for $lang, merge needed.\n";
              $merged_items = $this->mergePoFiles($file, $drupal_translation[$lang]);
              $this->writeMergedFile($project, $lang, $merged_items, $merge_dir);
            }
          }

        }
      }

    } // projectInfos

  }

  /**
   * Scan a directory of .po files, if it contains matches for projects we are looking for then gather up the locations of the files.
   *
   * @param $poFilesLocation
   *   Location of drupal translations.
   * @param $ignoreVersion
   *   Match files without the project version, i.e. just the project name.
   *
   * @return array
   *   key = project, value = array [key = language, value = file location]
   * @throws \Exception
   *   if we haven't built a list of projectInfos to work from.
   */
  private function scanPoDir($poFilesLocation, $ignoreVersion = FALSE): array {
    if ($this->projectInfos === NULL || sizeof($this->projectInfos) === 0) {
      throw new \Exception('Trying to set po directories for no projects.');
    }

    $translations = [];

    foreach ($this->projectInfos as $key => $value) {
      $po_list = [];

      $match = $key;

      // if we are ignoring the version then we just pick up the file based on the project name.
      if ($ignoreVersion) {
        $split = explode('-', $key, 2);
        if (sizeof($split) === 1) {
          // no version already... match is already correct.
        }
        elseif (sizeof($split) === 2) {
          $match = $split[0] . '-';
        }
        else {
          throw new \Exception('Unexpected project name when searching for po files: ' . $key);
        }
      }
      $pos = glob($poFilesLocation . '/' . $match . '*.po');

      foreach ($pos as $file) {
        $lang = $this->getLanguageFromPoFileName($file);
        $po_list[$lang] = $file;
      }
      //if (!empty($po_list)) {
      $translations[$key] = $po_list;
      //}

    }

    //echo "$poFilesLocation = " . serialize($translations) . "\n\n";

    return $translations;

  }

  /**
   * Set the .po files on a project info object based on the contents of a directory.
   *
   * @param $merge_dir
   *   Directory containing .po files.
   *
   * @throws \Exception
   */
  private function addPosToProjectInfo($merge_dir): void {
    $merged_pos = $this->scanPoDir($merge_dir);
    foreach ($merged_pos as $project => $pos) {
      $this->getProjectInfos()[$project]->setPoFiles($pos);
    }
  }

  /**
   * @param $filename
   *
   * @return bool|string
   * @throws \Exception
   */
  private function getLanguageFromPoFileName($filename) {
    $langfile = basename($filename, '.po');
    $lastDotPos = strrpos($langfile, '.');
    if (!$lastDotPos) {
      throw new \Exception('Unable to find language in po file');
    }
    return substr($langfile, $lastDotPos + 1);
  }

  /**
   * @param $location
   *
   * @throws \Exception
   */
  private function checkAndCreateDirectory($location): void {
    if (!file_exists($location) && !is_dir($location)) {
      $success = mkdir($location);
      if (!$success) {
        throw new \Exception('error creating directory: ' . $location);
      }
    }
  }

  /**
   * @param $langFiles
   * @param $location
   */
  private function copyPoFiles($langFiles, $location): void {
    foreach ($langFiles as $lang => $file) {
      copy($file, $location . '/' . basename($file));
    }
  }

  /**
   * @param $file
   * @param $location
   */
  private function copySinglePoFile($file, $location): void {
    copy($file, $location . '/' . basename($file));
  }

  /**
   * Merge PoItems from ibm translation file and drupal translation file for a
   * given project and language.
   * If present drupal translation takes precedence.
   *
   * @param $ibm_translation
   *   path to ibm translation file.
   * @param $drupal_translation
   *   path to ibm translation file.
   *
   * @return array
   *   Merged PoItems
   */
  private function mergePoFiles($ibm_translation, $drupal_translation): array {
    echo "      In mergeTranslation with\n      - $ibm_translation\n      - $drupal_translation\n";

    $ibm_translation_reader = new TranslationFileReader($ibm_translation);
    $drupal_translation_reader = new TranslationFileReader($drupal_translation);

    $ibm_items = $ibm_translation_reader->getItems();
    $drupal_items = $drupal_translation_reader->getItems();
    $merged_items = [];

    foreach ($ibm_items as $ibm_i) {
      $drupal_translation_used = FALSE;
      foreach ($drupal_items as $index => $drupal_i) {
        if ($ibm_i->getSource() === $drupal_i->getSource()) {

          //echo "preferring drupal translation\n";
          $drupal_translation_used = TRUE;
          $merged_items[] = $drupal_i;
          // remove from the drupal_items collection.
          unset($drupal_items[$index]);
          //echo "size of drupal_items is " . sizeof($drupal_items) . "\n";
          break;
        }
      }
      if (!$drupal_translation_used) {
        //echo "using IBM translation\n";
        $merged_items[] = $ibm_i;
      }
    }
    if (sizeof($drupal_items) > 0) {
      echo "      Merging remnants of drupal translations as they aren't available otherwise.\n";
      $merged_items = array_merge($merged_items, $drupal_items);
    }
    return $merged_items;
  }

  /**
   * Write merged file out to location.
   *
   * @param $project
   * @param $lang
   * @param $items
   * @param $location
   */
  private function writeMergedFile($project, $lang, $items, $location): void {
    $file_path = $location . '/' . $project . '.' . $lang . '.po';
    echo "      Writing merged file to $file_path\n";
    new TranslationFileWriter($items, $file_path);
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
