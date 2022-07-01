<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * This class handles the injection of data in to Behat
 * feature files. Rather than hard coding data in to tests,
 * we can use @data() placeholders to dynamically inject
 * data in to our tests.
 */

namespace Drupal\apictest\TestData;

class TestData {

  /**
   * directory containing the .json test data files
   *
   * @var string
   */
  private string $scenarioDirectory = __DIR__ . '/../../../../testdata';

  /**
   * any array properties need marking as such for our processing later
   *
   * @var array|string[]
   */
  private array $arrayProperties = [
    'andre',
    'consumerorg',
    'application',
    'api',
    'product',
    'user_registries',
  ];

  public $rawScenario;

  /**
   * Constructor. Takes a string that names the test data scenario to load.
   * e.g. if you want to use testdata/production.json, pass the string "production".
   * Defaults to mocked if not specified.
   *
   * @param String $testDataScenario
   *  the name of the scenario file to load e.g. mocked, production
   *
   * @throws \Exception
   */
  public function __construct($testDataScenario = 'mocked') {

    $this->loadTestDataFromScenarioFile($this->scenarioDirectory . '/' . $testDataScenario . '.json');

  }

  /**
   * Loads the scenario file in to memory for later processing.
   *
   * @param String $scenarioFile
   *  the full path to the scenario file to be loaded.
   *
   * @throws \Exception
   */
  private function loadTestDataFromScenarioFile(string $scenarioFile): void {

    if (!file_exists($scenarioFile)) {
      throw new \Exception("Couldn't find a scenario file at $scenarioFile");
    }

    $this->rawScenario = json_decode(file_get_contents($scenarioFile), FALSE, 512, JSON_THROW_ON_ERROR);

  }

  /**
   * This function does the actual replacing of the @data(object.property)
   * injecting the data loaded from the selected scenario file.
   *
   * @param string|null $parameter
   *  the @data(object.property) string that needs data injecting in to it.
   *
   * @return string
   */
  public function insertTestData(?string $parameter): string {

    // The @data(property) part of the string may be embedded in a larger string
    // e.g. /span/@title['@data(andre.mail)']. We need to extract the @data
    // bit and reinsert the replace part in to the full string.
    $wholeString = $parameter;

    // TODO : this only supports one @data(...) element. Hopefully we don't need anything more complex!!
    $startPos = strpos($parameter, '@data');
    $endPos = strpos($parameter, ')') + 1;
    $length = $endPos - $startPos;
    $chunkToReplace = substr($parameter, $startPos, $length);

    $searchString = str_replace(['@data(', ')'], '', $chunkToReplace);

    $replacement = $this->processPlaceholder($searchString);

    // now insert back in to the main string

    return str_replace($chunkToReplace, $replacement, $wholeString);
  }

  /**
   * The main meat of the data injection functionality is in this function.
   *
   * @param string|null $placeholder
   *  the @data(object.property) string that needs data injecting in to it
   *
   * @param array $partsCollectedSoFar
   *  do not set this parameter. This function is recursive and makes use of this
   *  array to keep track of current progress.
   *
   * @return mixed
   */
  private function processPlaceholder(?string $placeholder, $partsCollectedSoFar = []) {

    #print "process placeholder for " . $placeholder . \PHP_EOL;

    // The @data string is basically of the form :
    //  object.property
    //  object.object.property
    //  object.object.object.property
    // and so on so this is a nice problem to solve recursively.

    // Pop the next property off the list. Store the remainder of the string to replace.
    [$nextProperty, $newPlaceholder] = $this->popNextProperty($placeholder);

    // if we popped a null, we've reached the end of the recursion so unwind again
    if ($nextProperty === NULL) {

      // this is some fun code. we're effectively accessing a property on the $this->rawScenario object
      // by working our way down the tree of "parts" we have collected along the way.
      // so we start with the scenario itself...
      $result = $this->rawScenario;
      // ... work through each of the parts we have collected...
      foreach ($partsCollectedSoFar as $part) {

        // ... arrays need extra processing as you can't access "$array[0]" in a single step...
        if ($this->partHasArrayIndex($part)) {

          $partName = $this->getPartName($part);
          $partIndex = $this->getSelectedNumber($part);
          // ... get whole array...
          $result = $result->$partName;
          // ... then get the element we want...
          $result = $result[$partIndex];

        }
        else {
          // simple properties are much easier!
          $result = $result->$part;
        }
      }
      // by here we should have the value that was requested
      return $result;
    }

    // Otherwise we still have more properties to process

    // If this property is an array property, work out which array element was chosen.
    // If none was explicitly selected, force the selection of the first element i.e. [0].
    $fixedNextProperty = $nextProperty;
    foreach ($this->arrayProperties as $property) {
      if (strpos($nextProperty, $property) === 0) {
        $selectedNumber = $this->getSelectedNumber($nextProperty);

        if (strpos($nextProperty, "[$selectedNumber]") === FALSE) {
          $fixedNextProperty = $nextProperty . "[$selectedNumber]";
        }

        break;
      }
    }

    // Append the property to the working list and go around again
    $partsCollectedSoFar[] = $fixedNextProperty;
    return $this->processPlaceholder($newPlaceholder, $partsCollectedSoFar);

  }


  /**
   * Find the next property from a string that needs processing i.e.
   * the next bit of the string up to the first '.' character.
   *
   * @param $argumentString
   *
   * @return array|null
   */
  private function popNextProperty($argumentString): ?array {

    if ($argumentString === NULL || $argumentString === '') {
      return [NULL, NULL];
    }

    $dotPosition = strpos($argumentString, '.');

    if ($dotPosition === FALSE) {
      $returnValue = [$argumentString, NULL];
    }
    else {
      $property = substr($argumentString, 0, $dotPosition);
      $remainingString = substr($argumentString, $dotPosition + 1);
      $returnValue = [$property, $remainingString];
    }
    return $returnValue;
  }

  /**
   * Look at a property and work out which element of an array was
   * selected in the property e.g. if the string contains [5], return 5.
   * If the string contains no [N] substring, return 0 which forces the
   * first element of the array to be returned.
   *
   * @param $placeholder
   *
   * @return int
   */
  private function getSelectedNumber($placeholder): int {

    // Default number is 0. If no number has been selected, we return the first item in the list.
    $selectedNumber = 0;

    // If there is a number selected, the placeholder will include the string [{n}] where n is the selected item.
    if (preg_match("/\[(\d+)\]/", $placeholder, $matches)) {
      $selectedNumber = $matches[1][0];
    }

    return $selectedNumber;

  }

  /**
   * Check if a property has an array index qualfiier (i.e. [N]).
   * Return TRUE if it does and FALSE otherwise.
   *
   * @param $part
   *
   * @return false|int
   */
  private function partHasArrayIndex($part) {
    return preg_match("/\[(\d+)\]/", $part, $matches);
  }

  /**
   * Get the property name of an array qualfied string.
   * e.g. for input 'name[6]' return 'name'.
   *
   * @param $part
   *
   * @return bool|string
   */
  private function getPartName($part) {
    return substr($part, 0, strpos($part, '['));
  }

}