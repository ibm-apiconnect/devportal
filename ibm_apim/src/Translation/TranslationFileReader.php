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

use Drupal\Component\Gettext\PoStreamReader;

/**
 * Class PotFileReader
 *
 * Read msgIds from a .pot file.
 *
 * @package Drupal\ibm_apim\Translation
 */
class TranslationFileReader {

  protected $reader;

  /**
   * @var array associative array
   *  - key = msgid
   *  - value = PoItem
   */
  private $items;

  public function __construct($file, $langCode = NULL) {
    $this->reader = $this->loadFile($file, $langCode);
    #echo "Reading: $file\n";
    $this->parse();
  }

  /**
   * Read .pot/ .po files.
   *
   * @throws \Exception
   */
  private function parse(): void {
    $items = [];

    $item = $this->reader->readItem();
    while ($item !== NULL) {
      $items[] = $item;
      $item = $this->reader->readItem();
    }

    $this->items = $items;
  }

  private function loadFile($file, $langCode = NULL): PoStreamReader {
    $reader = new PoStreamReader();
    $reader->setURI($file);
    if ($langCode !== NULL) {
      $reader->setLangcode($langCode);
    }

    try {
      $reader->open();
    } catch (\Exception $exception) {
      throw $exception;
    }

    return $reader;
  }

  /**
   * Get PoItems from a .po or .pot file.
   *
   * @return array of \Drupal\Component\Gettext\PoItem
   */
  public function getItems(): array {
    return $this->items;
  }


}