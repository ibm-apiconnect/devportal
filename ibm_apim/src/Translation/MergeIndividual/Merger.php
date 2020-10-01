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

namespace Drupal\ibm_apim\Translation\MergeIndividual;

use Drupal\ibm_apim\Translation\TranslationFileWriter;
use Drupal\ibm_apim\Translation\TranslationMerger;

/**
 * Class Merger
 *
 * @package Drupal\ibm_apim\Translation\MergeIndividual
 *
 * Merge 2 sets of .po files, this will produce a set of translations combining the contents of 2 files.
 */
class Merger extends TranslationMerger {

  private $outputDir;

  private $masterItems;

  private $secondaryItems;

  /**
   * Merger constructor.
   *
   * Merge 2 translation files (.po).
   *
   * @param $file1 - This file takes precedent when there is a matching msgstr in both files.
   * @param $file2 - A msgstr in this file will be replaced if it exists in $file1
   * @param $outputdir - A new file will be written out to this location.
   *
   * @throws \Exception
   */
  function __construct($file1, $file2, $outputdir = '/tmp/mergedir') {

    $this->outputDir = $outputdir;
    $this->masterItems = $this->getTranslationFileItems($file1);
    $this->secondaryItems = $this->getTranslationFileItems($file2);

    $this->checkDir($outputdir);

    $complete_items = $this->mergeFiles();

    $this->writeOutput($complete_items, $outputdir . '/' . basename($file1));

  }


  /**
   * Take everything from master items and add things not included there from secondary items.
   *
   * @return array|null
   */
  private function mergeFiles(): ?array {

    $complete_items = $this->masterItems;

    foreach ($this->secondaryItems as $secondaryItem) {

      //echo "Checking: " . $secondaryItem->getSource() . PHP_EOL;
      foreach ($this->masterItems as $masterItem) {
        //echo " Against: "  . $masterItem->getSource() . PHP_EOL;
        if ($secondaryItem->getSource() === $masterItem->getSource()) {
          //echo "  *** GOT IT *** - should move to new Checking node" . PHP_EOL;
          continue 2;
        }
      }

      // if we don't get a match we need to add this into the complete set of items.
      $complete_items[] = $secondaryItem;

    }

    return $complete_items;

  }

  /**
   * @param array $items
   * @param string $filename
   *
   * @throws \Exception
   */
  private function writeOutput($items, $filename): void {
    echo 'Writing to ' . $filename . PHP_EOL;
    new TranslationFileWriter($items, $filename);
    echo 'Written: ' . $filename . "\n";
  }


}