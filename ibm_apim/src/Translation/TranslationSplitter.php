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

/*
 * Takes .pot file as the master list of strings and a corresponding
 * .po file, to produce the following files:
 *
 *   - translation - master list (.pot file as is).
 *   - memories - for the translation centre to use as translation memories.
 *   - required - for the translation centre to translate.
 */

class TranslationSplitter {

  private $strings;

  private $translations;

  private $memories = [];

  private $translation_required = [];

  public function __construct($potFile, $poFile, $languageCode) {
    $potReader = new TranslationFileReader($potFile);
    $this->strings = $potReader->getItems();

    $poReader = new TranslationFileReader($poFile, $languageCode);
    $this->translations = $poReader->getItems();

    $this->split();

  }

  private function split(): void {
    if ($this->strings === NULL || sizeof($this->strings) === 0) {
      throw new \Exception('No strings available.');
    }

    if ($this->translations === NULL || sizeof($this->translations) === 0) {
      throw new \Exception('No translations available.');
    }

    foreach ($this->strings as $string) {
      // each string either ends up in a memories or translation needed collection.
      $found_translation = FALSE;
      foreach ($this->translations as $existing_translation) {

        if ($string->getSource() === $existing_translation->getSource()) {
          $found_translation = TRUE;
          $this->memories[] = $existing_translation;
          break;
        }
      }
      // doesn't exist in existing translations, add to translation required collection.
      if (!$found_translation) {
        $this->translation_required[] = $string;
      }

    }
  }

  public function getMemories(): array {
    return $this->memories;
  }

  public function getRequiredTranslations(): array {
    return $this->translation_required;
  }

}

