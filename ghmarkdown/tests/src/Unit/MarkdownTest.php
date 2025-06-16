<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace Drupal\Tests\ghmarkdown\Unit;

use Drupal\ghmarkdown\cebe\markdown\Markdown;
use Drupal\Tests\ghmarkdown\Unit\Base\BaseMarkdownBaseClass;

/**
 * Test case for traditional markdown.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @group ghmarkdown
 */
class MarkdownTest extends BaseMarkdownBaseClass {

  public static function createMarkdown(): Markdown {
    return new Markdown();
  }

  public static function getDataPaths(): array {
    return [
      'markdown-data' => __DIR__ . '/markdown-data',
    ];
  }

  public function testEdgeCases(): void {
    self::assertEquals("<p>&amp;</p>\n", $this->createMarkdown()->parse('&'));
    self::assertEquals("<p>&lt;</p>\n", $this->createMarkdown()->parse('<'));
  }

  public function testKeepZeroAlive(): void {
    $parser = $this->createMarkdown();

    self::assertEquals("0", $parser->parseParagraph("0"));
    self::assertEquals("<p>0</p>\n", $parser->parse("0"));
  }

  public function testAutoLinkLabelingWithEncodedUrl(): void {
    $parser = $this->createMarkdown();

    $utfText = "\xe3\x81\x82\xe3\x81\x84\xe3\x81\x86\xe3\x81\x88\xe3\x81\x8a";
    $utfNaturalUrl = "http://example.com/" . $utfText;
    $utfEncodedUrl = "http://example.com/" . urlencode($utfText);
    $eucEncodedUrl = "http://example.com/" . urlencode(mb_convert_encoding($utfText, 'EUC-JP', 'UTF-8'));

    self::assertStringEndsWith(">{$utfNaturalUrl}</a>", $parser->parseParagraph("<{$utfNaturalUrl}>"), "Natural UTF-8 URL needs no conversion.");
    self::assertStringEndsWith(">{$utfNaturalUrl}</a>", $parser->parseParagraph("<{$utfEncodedUrl}>"), "Encoded UTF-8 URL will be converted to readable format.");
    self::assertStringEndsWith(">{$eucEncodedUrl}</a>", $parser->parseParagraph("<{$eucEncodedUrl}>"), "Non UTF-8 URL should never be converted.");
    // See: \cebe\markdown\inline\LinkTrait::renderUrl
  }

}
