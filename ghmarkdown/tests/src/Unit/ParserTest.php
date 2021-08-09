<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace Drupal\Tests\ghmarkdown\Unit;

use Drupal\ghmarkdown\cebe\markdown\Parser;
use Drupal\Tests\UnitTestCase;

/**
 * Test case for the parser base class.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @group ghmarkdown
 */
class ParserTest extends UnitTestCase {

  public function testMarkerOrder(): void {
    $parser = new TestParser();
    $parser->markers = [
      '[' => 'parseMarkerA',
      '[[' => 'parseMarkerB',
    ];

    self::assertEquals("<p>Result is A</p>\n", $parser->parse('Result is [abc]'));
    self::assertEquals("<p>Result is B</p>\n", $parser->parse('Result is [[abc]]'));
    self::assertEquals('Result is A', $parser->parseParagraph('Result is [abc]'));
    self::assertEquals('Result is B', $parser->parseParagraph('Result is [[abc]]'));

    $parser = new TestParser();
    $parser->markers = [
      '[[' => 'parseMarkerB',
      '[' => 'parseMarkerA',
    ];

    self::assertEquals("<p>Result is A</p>\n", $parser->parse('Result is [abc]'));
    self::assertEquals("<p>Result is B</p>\n", $parser->parse('Result is [[abc]]'));
    self::assertEquals('Result is A', $parser->parseParagraph('Result is [abc]'));
    self::assertEquals('Result is B', $parser->parseParagraph('Result is [[abc]]'));
  }

  public function testMaxNestingLevel(): void {
    $parser = new TestParser();
    $parser->markers = [
      '[' => 'parseMarkerC',
    ];

    $parser->maximumNestingLevel = 3;
    self::assertEquals("(C-a(C-b(C-c)))", $parser->parseParagraph('[a[b[c]]]'));
    $parser->maximumNestingLevel = 2;
    self::assertEquals("(C-a(C-b[c]))", $parser->parseParagraph('[a[b[c]]]'));
    $parser->maximumNestingLevel = 1;
    self::assertEquals("(C-a[b[c]])", $parser->parseParagraph('[a[b[c]]]'));
  }

  public function testKeepZeroAlive(): void {
    $parser = new TestParser();

    self::assertEquals("0", $parser->parseParagraph("0"));
    self::assertEquals("<p>0</p>\n", $parser->parse("0"));
  }

}

/**
 * Class TestParser
 *
 * @package Drupal\Tests\ghmarkdown\Unit
 */
class TestParser extends Parser {

  /**
   * @var array
   */
  public array $markers = [];

  /**
   * @return array
   */
  protected function inlineMarkers(): array {
    return $this->markers;
  }

  /**
   * @param $text
   *
   * @return array
   */
  protected function parseMarkerA($text): array {
    return [['text', 'A'], strrpos($text, ']') + 1];
  }

  /**
   * @param $text
   *
   * @return array
   */
  protected function parseMarkerB($text): array {
    return [['text', 'B'], strrpos($text, ']') + 1];
  }

  /**
   * @param $text
   *
   * @return array
   */
  protected function parseMarkerC($text): array {
    $terminatingMarkerPos = strrpos($text, ']');
    $inside = $this->parseInline(substr($text, 1, $terminatingMarkerPos - 1));
    return [['text', '(C-' . $this->renderAbsy($inside) . ')'], $terminatingMarkerPos + 1];
  }

}
