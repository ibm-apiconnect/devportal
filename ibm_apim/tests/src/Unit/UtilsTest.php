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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\Service\Utils;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\Utils
 *
 * @group ibm_apim
 */
class UtilsTest extends UnitTestCase {

  private $prophet;


  protected function setup() {
    $this->prophet = new Prophet();
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  public function testStartsWithSuccess(): void {
    $utils = new Utils();
    $result = $utils->startsWith('foobar', 'foo');
    $this->assertEquals(true, $result);
  }

  public function testStartsWithFailure(): void {
    $utils = new Utils();
    $result = $utils->startsWith('foobar', 'bar');
    $this->assertEquals(false, $result);
  }

  public function testEndsWithSuccess(): void {
    $utils = new Utils();
    $result = $utils->endsWith('foobar', 'bar');
    $this->assertEquals(true, $result);
  }

  public function testEndsWithFailure(): void {
    $utils = new Utils();
    $result = $utils->endsWith('foobar', 'foo');
    $this->assertEquals(false, $result);
  }

  public function testTruncateString(): void {
    $utils = new Utils();
    $result = $utils->truncate_string('foobarfoobar',6);
    $this->assertEquals('fooba…', $result);
  }

  public function testTruncateStringAppend(): void {
    $utils = new Utils();
    $result = $utils->truncate_string('foobarfoobar',6,'x');
    $this->assertEquals('foobax', $result);
  }

  public function testTruncateStringNoLength(): void {
    $utils = new Utils();
    $result = $utils->truncate_string('foobarfoobar');
    $this->assertEquals('foobarfoobar', $result);
  }

  public function testConvertLangNameZhHans(): void {
    $utils = new Utils();
    $result = $utils->convert_lang_name('zh_hans');
    $this->assertEquals('zh-cn', $result);
  }

  public function testConvertLangNameZhHans2(): void {
    $utils = new Utils();
    $result = $utils->convert_lang_name('zh-hans');
    $this->assertEquals('zh-cn', $result);
  }

  public function testConvertLangNameZhHant(): void {
    $utils = new Utils();
    $result = $utils->convert_lang_name('zh_hant');
    $this->assertEquals('zh-tw', $result);
  }

  public function testConvertLangNameZhHant2(): void {
    $utils = new Utils();
    $result = $utils->convert_lang_name('zh-hant');
    $this->assertEquals('zh-tw', $result);
  }

  public function testConvertLangNameFr(): void {
    $utils = new Utils();
    $result = $utils->convert_lang_name('fr_fr');
    $this->assertEquals('fr-fr', $result);
  }
}
