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

namespace Drupal\ibm_apim\Translation;

/**
 * Class ProjectParser
 *
 * Works on a parsed list of projects, where a list of pot/ po files for each project
 * is available in ProjectInfo objects.
 *
 * This class iterates across all projects:
 *  Create an output directory for that project.
 *  Copy in the original .pot file which is the source list.
 *  If we have any translations (.po files) then it creates 2 output files.
 *  - memories file - one per language - intended to be loaded as translation memory.
 *  - requiredtranslation file - one per language - a guide to the translation centre.
 *
 * @package Drupal\ibm_apim\Translation
 *
 */
class ProjectParser {

  private $projects;

  private $fulltranslations = [];

  /**
   * ProjectParser constructor.
   *
   * @param array $projects
   *   Array of ProjectInfo objects
   *
   * @throws \Exception
   */
  public function __construct(array $projects) {
    if ($projects === NULL || sizeof($projects) === 0) {
      throw new \Exception('No projects to work with.');
    }
    $this->projects = $projects;
    $this->process();
    $this->write();
  }

  /**
   * Produce memories and requiretranslation objects for each project.
   */
  private function process(): void {

    foreach ($this->projects as $project => $info) {
      $translation_files = [];
      echo "Project: $project \n";
      foreach ($info->getPoFiles() as $language => $poFile) {
        $translation_files[$language] = new TranslationSplitter($info->getPotFile(), $poFile, $language);
      }
      $this->fulltranslations[$project] = $translation_files;
    }

  }

  /**
   * Create project specific output directory and writes all the files
   * available to it.
   *
   * @throws \Exception No translations available.
   */
  private function write(): void {
    if (sizeof($this->fulltranslations) === 0) {
      throw new \Exception('No translations available when trying to write out files.');
    }

    echo 'Writing output ';

    foreach ($this->fulltranslations as $project => $translations) {
      $output_dir = $this->createOutputDir($this->projects[$project]->getOutputDir());

      // we need the original .pot file in the output dir, regardless of what else we have produced.

      $potFile = $this->projects[$project]->getPotFile();
      copy($potFile, $output_dir . '/' . basename($potFile));

      //$split_translations = $this->fulltranslations[$project][$lang];
      foreach ($translations as $lang => $split_translations) {
        if (sizeof($split_translations->getMemories()) > 0) {
          new TranslationFileWriter($split_translations->getMemories(), $output_dir . '/' . $project . '-memories.' . $lang . '.po');
        }
        if (sizeof($split_translations->getRequiredTranslations()) > 0) {
          new TranslationFileWriter($split_translations->getRequiredTranslations(), $output_dir . '/' . $project . '-translationrequired.' . $lang . '.po');
        }
      }
      echo '.';
    }

    echo '\nWriting output complete.\n';

  }

  /**
   * @param $location
   *
   * @return mixed
   * @throws \Exception
   */
  private function createOutputDir($location) {
    #echo "output dir is: " . $location . "\n";
    if (!is_dir($location)) {
      $success = mkdir($location);
      if (!$success) {
        throw new \Exception('Unable to create output directory: ' . $location);
      }
      #      else {
      #        echo "Output directory $location created.\n";
      #      }
    }

    return $location;
  }

}