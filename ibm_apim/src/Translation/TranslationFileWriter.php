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

namespace Drupal\ibm_apim\Translation;


use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoStreamWriter;

class TranslationFileWriter {

  private $items;
  private $file;
  private $strip_msgstr = FALSE;

  /**
   * TranslationFileWriter constructor.
   *
   * @param array $items - PO Items to write.
   * @param $file - filename.
   * @param bool $strip_msgstr - strip msgstr entries, i.e. translations, used to generate an english only version of a file.
   */
  public function __construct(array $items, $file, $strip_msgstr = FALSE) {
    $this->items = $items;
    $this->file = $file;
    $this->strip_msgstr = $strip_msgstr;
    $this->writeFile();
  }

  private function writeFile() {
    $writer = new PoStreamWriter();
    $writer->setURI($this->file);
    // TODO: pass through the header.
    $writer->setHeader(new PoHeader('fr'));

    try {
      $writer->open();
      foreach ($this->items as $item) {
        if($this->strip_msgstr) {
          if($item->isPlural()) {
            $emptyarray = array();
            foreach ($item->getTranslation() as $entry) {
              array_push($emptyarray, NULL);
            }
            $item->setTranslation($emptyarray);
          }
          else {
            $item->setTranslation(NULL);
          }
        }
        $writer->writeItem($item);
      }
      $writer->close();
    }
    catch (\Exception $exception) {
      throw $exception;
    }
  }

}