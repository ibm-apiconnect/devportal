<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2026
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\Updater\ApicModule;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for ApicModule::checkFunctionNames().
 *
 * @group ibm_apim
 * @coversDefaultClass \Drupal\ibm_apim\Updater\ApicModule
 */
class ApicModuleTest extends UnitTestCase {

  /**
   * Temporary file used across tests.
   *
   * @var string
   */
  protected string $tmpFile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->tmpFile = tempnam(sys_get_temp_dir(), 'apicmodule_test_');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (file_exists($this->tmpFile)) {
      unlink($this->tmpFile);
    }
    parent::tearDown();
  }

  /**
   * Helper: write content to the temp file and call checkFunctionNames().
   */
  private function check(string $content): bool {
    file_put_contents($this->tmpFile, $content);
    return ApicModule::checkFunctionNames($this->tmpFile);
  }

  /**
   * Legitimate custom module functions must be accepted.
   *
   * @covers ::checkFunctionNames
   */
  public function testLegitimateModuleIsAccepted(): void {
    $php = "<?php\nfunction my_custom_module_init() {}\nfunction my_custom_module_help() {}\n";
    $this->assertTrue($this->check($php), 'Functions with a non-IBM prefix must be accepted.');
  }

  /**
   * Plain IBM hook declarations at line start must be rejected.
   *
   * @covers ::checkFunctionNames
   */
  public function testPlainIbmHookIsRejected(): void {
    $cases = [
      "<?php\nfunction ibm_apim_user_login(\$a) {}\n",
      "<?php\nfunction auth_apic_form_alter(&\$f, \$s, \$id) {}\n",
      "<?php\nfunction consumerorg_preprocess_page(&\$v) {}\n",
      "<?php\nfunction apic_api_node_view(\$b) {}\n",
    ];
    foreach ($cases as $php) {
      $this->assertFalse($this->check($php), "Plain IBM hook declaration must be rejected:\n$php");
    }
  }

  /**
   * Bypass variants that defeat the old ^\s* anchor must still be rejected.
   *
   * These are the exact payloads from APICON-25820: a leading non-whitespace
   * token before 'function' would have passed the original regex but must be
   * caught by the \b word-boundary fix.
   *
   * @covers ::checkFunctionNames
   */
  public function testBypassVariantsAreRejected(): void {
    $cases = [
      "<?php\n;function ibm_apim_user_login(\$a) {}\n"           => '; prefix',
      "<?php\n/**/function auth_apic_form_alter(&\$f,\$s,\$i) {}\n" => '/**/ prefix',
      "<?php\nif(1){function consumerorg_preprocess_page(&\$v){}}\n" => 'if(1){ wrap',
    ];
    foreach ($cases as $php => $description) {
      $this->assertFalse($this->check($php), "Bypass variant '$description' must be rejected.");
    }
  }

  /**
   * A non-existent file path must return FALSE.
   *
   * @covers ::checkFunctionNames
   */
  public function testNonExistentFileReturnsFalse(): void {
    $this->assertFalse(
      ApicModule::checkFunctionNames('/tmp/this_file_does_not_exist_apicmodule.php'),
      'A missing file must return FALSE.'
    );
  }

}
