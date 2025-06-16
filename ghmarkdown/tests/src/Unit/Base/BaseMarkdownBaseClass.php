<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace Drupal\Tests\ghmarkdown\Unit\Base;

use Drupal\ghmarkdown\cebe\markdown\Parser;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for all Test cases.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
abstract class BaseMarkdownBaseClass extends UnitTestCase {

  protected static string $outputFileExtension = '.html';

  abstract public static function getDataPaths();

  /**
   * @return Parser
   */
  abstract public static function createMarkdown(): Parser;

  /**
   * @dataProvider dataFiles
   */
  public function testParse($path, $file): void {
    [$markdown, $html] = $this->getTestData($path, $file);
    // Different OS line endings should not affect test
    $html = str_replace(["\r\n", "\n\r", "\r"], "\n", $html);

    $m = $this->createMarkdown();
    self::assertEquals($html, $m->parse($markdown));
  }

  public function testUtf8(): void {
    self::assertSame("<p>абвгдеёжзийклмнопрстуфхцчшщъыьэюя</p>\n", $this->createMarkdown()
      ->parse('абвгдеёжзийклмнопрстуфхцчшщъыьэюя'));
    self::assertSame("<p>there is a charater, 配</p>\n", $this->createMarkdown()->parse('there is a charater, 配'));
    self::assertSame("<p>Arabic Latter \"م (M)\"</p>\n", $this->createMarkdown()->parse('Arabic Latter "م (M)"'));
    self::assertSame("<p>電腦</p>\n", $this->createMarkdown()->parse('電腦'));

    self::assertSame('абвгдеёжзийклмнопрстуфхцчшщъыьэюя', $this->createMarkdown()
      ->parseParagraph('абвгдеёжзийклмнопрстуфхцчшщъыьэюя'));
    self::assertSame('there is a charater, 配', $this->createMarkdown()->parseParagraph('there is a charater, 配'));
    self::assertSame('Arabic Latter "م (M)"', $this->createMarkdown()->parseParagraph('Arabic Latter "م (M)"'));
    self::assertSame('電腦', $this->createMarkdown()->parseParagraph('電腦'));
  }

  public function testInvalidUtf8(): void {
    $m = $this->createMarkdown();
    self::assertEquals("<p><code>�</code></p>\n", $m->parse("`\x80`"));
    self::assertEquals('<code>�</code>', $m->parseParagraph("`\x80`"));
  }

  public static function pregData(): array {
    // http://en.wikipedia.org/wiki/Newline#Representations
    return [
      ["a\r\nb", "a\nb"],
      ["a\n\rb", "a\nb"], // Acorn BBC and RISC OS spooled text output :)
      ["a\nb", "a\nb"],
      ["a\rb", "a\nb"],

      ["a\n\nb", "a\n\nb", "a</p>\n<p>b"],
      ["a\r\rb", "a\n\nb", "a</p>\n<p>b"],
      ["a\n\r\n\rb", "a\n\nb", "a</p>\n<p>b"], // Acorn BBC and RISC OS spooled text output :)
      ["a\r\n\r\nb", "a\n\nb", "a</p>\n<p>b"],
    ];
  }

  /**
   * @dataProvider pregData
   */
  public function testPregReplaceR($input, $exptected, $pexpect = NULL): void {
    self::assertSame($exptected, $this->createMarkdown()->parseParagraph($input));
    self::assertSame($pexpect === NULL ? "<p>$exptected</p>\n" : "<p>$pexpect</p>\n", $this->createMarkdown()
      ->parse($input));
  }

  public function getTestData($path, $file): array {
    return [
      file_get_contents(static::getDataPaths()[$path] . '/' . $file . '.md'),
      file_get_contents(static::getDataPaths()[$path] . '/' . $file . self::$outputFileExtension),
    ];
  }

  /**
   * @throws \Exception
   */
  public static function dataFiles(): array {
    $files = [];
    foreach (static::getDataPaths() as $name => $src) {
      $handle = opendir($src);
      if ($handle === FALSE) {
        throw new \Exception('Unable to open directory: ' . $src);
      }
      while (($file = readdir($handle)) !== FALSE) {
        if ($file === '.' || $file === '..') {
          continue;
        }

        if (substr($file, -3, 3) === '.md' && file_exists($src . '/' . substr($file, 0, -3) . self::$outputFileExtension)) {
          $files[] = [$name, substr($file, 0, -3)];
        }
      }
      closedir($handle);
    }
    return $files;
  }

}
