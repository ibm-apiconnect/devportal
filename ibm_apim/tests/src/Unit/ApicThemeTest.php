<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Updater\UpdaterException;
use Drupal\ibm_apim\Updater\ApicTheme;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for ApicTheme machine name validation.
 *
 * @group ibm_apim
 * @coversDefaultClass \Drupal\ibm_apim\Updater\ApicTheme
 */
class ApicThemeTest extends UnitTestCase {

  /**
   * Temporary directory for test files.
   *
   * @var string
   */
  protected $tempDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Create temporary directory
    $this->tempDir = sys_get_temp_dir() . '/apic_theme_test_' . uniqid();
    mkdir($this->tempDir, 0777, TRUE);
    
    // Mock the info parser service - it will parse real files
    $infoParser = $this->createMock(InfoParserInterface::class);
    $infoParser->method('parse')
      ->willReturnCallback(function ($file) {
        // Check if file exists and is readable
        if (empty($file) || !file_exists($file) || !is_readable($file)) {
          return [];
        }
        // Parse the actual YAML file
        $content = file_get_contents($file);
        $data = [];
        foreach (explode("\n", $content) as $line) {
          if (preg_match('/^(\w+):\s*(.+)$/', trim($line), $matches)) {
            $data[$matches[1]] = trim($matches[2], "'\"");
          }
        }
        return $data;
      });
    
    // Mock the file system service
    $fileSystem = $this->createMock(FileSystemInterface::class);
    $fileSystem->method('scanDirectory')
      ->willReturnCallback(function ($directory, $mask) {
        if (!is_dir($directory)) {
          return [];
        }
        $files = [];
        $items = scandir($directory);
        foreach ($items as $item) {
          if ($item === '.' || $item === '..') {
            continue;
          }
          $path = $directory . '/' . $item;
          if (is_file($path) && preg_match($mask, $item)) {
            $file = new \stdClass();
            $file->uri = $path;
            $file->filename = $item;
            $file->name = basename($item, '.info.yml');
            $files[] = $file;
          }
        }
        return $files;
      });
    $fileSystem->method('basename')
      ->willReturnCallback(function ($path) {
        return basename($path);
      });
    
    // Mock the string translation service
    $stringTranslation = $this->createMock(TranslationInterface::class);
    $stringTranslation->method('translateString')
      ->willReturnCallback(function ($translatable) {
        // Get the untranslated string and arguments from TranslatableMarkup
        $string = $translatable->getUntranslatedString();
        $args = $translatable->getArguments();
        // Return the formatted string with placeholders replaced
        return strtr($string, $args);
      });
    
    // Set up the container with mocked services
    $container = new ContainerBuilder();
    $container->set('info_parser', $infoParser);
    $container->set('file_system', $fileSystem);
    $container->set('string_translation', $stringTranslation);
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up temporary directory
    if (is_dir($this->tempDir)) {
      $this->removeDirectory($this->tempDir);
    }
    parent::tearDown();
  }

  /**
   * Helper to remove directory recursively.
   */
  protected function removeDirectory($dir) {
    if (!is_dir($dir)) {
      return;
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $path = $dir . '/' . $file;
      is_dir($path) ? $this->removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
  }

  /**
   * Helper to create a theme directory with .info.yml file.
   */
  protected function createTheme($machineName) {
    $themeDir = $this->tempDir . '/' . $machineName;
    mkdir($themeDir, 0777, TRUE);
    
    $infoFile = $themeDir . '/' . $machineName . '.info.yml';
    $content = "name: 'Test Theme'\ntype: theme\ncore_version_requirement: ^10 || ^11\n";
    file_put_contents($infoFile, $content);
    
    return $themeDir;
  }

  /**
   * Test valid machine names pass validation.
   *
   * @covers ::getProjectTitle
   */
  public function testValidMachineNames(): void {
    $validNames = [
      'mytheme',
      'my_theme_123',
      'my_custom_theme',
      'a',
      'a123',
      'abcdefghij1234567890', // 20 characters
    ];

    foreach ($validNames as $name) {
      $themeDir = $this->createTheme($name);
      $result = ApicTheme::getProjectTitle($themeDir);
      $this->assertEquals('Test Theme', $result, "Valid name '$name' should pass");
    }
  }

  /**
   * Test machine names starting with numbers fail.
   *
   * @covers ::getProjectTitle
   */
  public function testMachineNamesStartingWithNumbersFail(): void {
    $invalidNames = ['123theme', '0theme', '9test'];

    foreach ($invalidNames as $name) {
      $themeDir = $this->createTheme($name);
      
      try {
        ApicTheme::getProjectTitle($themeDir);
        $this->fail("Machine name '$name' should have thrown UpdaterException");
      } catch (UpdaterException $e) {
        $message = $e->getMessage();
        $this->assertStringContainsString('Invalid machine name', $message);
        $this->assertStringContainsString('must start with a lowercase letter', $message);
      }
    }
  }

  /**
   * Test machine names starting with underscore fail.
   *
   * @covers ::getProjectTitle
   */
  public function testMachineNamesStartingWithUnderscoreFail(): void {
    $themeDir = $this->createTheme('_mytheme');
    
    $this->expectException(UpdaterException::class);
    $this->expectExceptionMessageMatches('/Invalid machine name.*must start with a lowercase letter/');
    
    ApicTheme::getProjectTitle($themeDir);
  }

  /**
   * Test machine names with invalid characters fail.
   *
   * @covers ::getProjectTitle
   */
  public function testMachineNamesWithInvalidCharactersFail(): void {
    $invalidNames = [
      'MyTheme' => 'uppercase',
      'my-theme' => 'hyphen',
      'my.theme' => 'dot',
      'my@theme' => 'special char',
    ];

    foreach ($invalidNames as $name => $reason) {
      $themeDir = $this->createTheme($name);
      
      try {
        ApicTheme::getProjectTitle($themeDir);
        $this->fail("Machine name '$name' ($reason) should have thrown UpdaterException");
      } catch (UpdaterException $e) {
        $message = $e->getMessage();
        $this->assertStringContainsString('Invalid machine name', $message);
        $this->assertStringContainsString('lowercase letters, numbers, and underscores', $message);
      }
    }
  }

  /**
   * Test reserved machine names fail.
   *
   * @covers ::getProjectTitle
   */
  public function testReservedMachineNamesFail(): void {
    $reservedNames = [
      'src', 'lib', 'vendor', 'assets', 'css', 'files', 'images',
      'js', 'misc', 'templates', 'includes', 'fixtures', 'drupal',
    ];

    foreach ($reservedNames as $name) {
      $themeDir = $this->createTheme($name);
      
      try {
        ApicTheme::getProjectTitle($themeDir);
        $this->fail("Reserved name '$name' should have thrown UpdaterException");
      } catch (UpdaterException $e) {
        $message = $e->getMessage();
        $this->assertStringContainsString('Invalid machine name', $message);
        $this->assertStringContainsString('reserved', $message);
      }
    }
  }

  /**
   * Test machine names exceeding length limit fail.
   *
   * @covers ::getProjectTitle
   */
  public function testMachineNameTooLongFails(): void {
    $longName = 'this_is_a_very_long_machine_name_that_exceeds_limit'; // > 20 chars
    $themeDir = $this->createTheme($longName);
    
    $this->expectException(UpdaterException::class);
    $this->expectExceptionMessageMatches('/Invalid machine name.*must not exceed 20 characters/');
    
    ApicTheme::getProjectTitle($themeDir);
  }

}