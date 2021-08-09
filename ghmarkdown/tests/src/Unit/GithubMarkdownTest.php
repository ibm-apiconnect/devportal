<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace Drupal\Tests\ghmarkdown\Unit;

use Drupal\ghmarkdown\cebe\markdown\GithubMarkdown;

/**
 * Test case for the github flavored markdown.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @group ghmarkdown
 */
class GithubMarkdownTest extends BaseMarkdownTest {

  public function createMarkdown(): GithubMarkdown {
    return new GithubMarkdown();
  }

  public function getDataPaths(): array {
    return [
      'markdown-data' => __DIR__ . '/markdown-data',
      'github-data' => __DIR__ . '/github-data',
    ];
  }

  public function testNewlines(): void {
    $markdown = $this->createMarkdown();
    self::assertEquals("This is text<br />\nnewline\nnewline.", $markdown->parseParagraph("This is text  \nnewline\nnewline."));
    $markdown->enableNewlines = TRUE;
    self::assertEquals("This is text<br />\nnewline<br />\nnewline.", $markdown->parseParagraph("This is text  \nnewline\nnewline."));

    self::assertEquals("<p>This is text</p>\n<p>newline<br />\nnewline.</p>\n", $markdown->parse("This is text\n\nnewline\nnewline."));
  }

  /**
   * @throws \Exception
   */
  public function dataFiles(): array {
    $files = parent::dataFiles();
    foreach ($files as $i => $f) {
      // skip files that are different in github MD
      if ($f[0] === 'markdown-data' && (
          $f[1] === 'list-marker-in-paragraph' ||
          $f[1] === 'dense-block-markers'
        )) {
        unset($files[$i]);
      }
    }
    return $files;
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

    self::assertStringEndsWith(">{$utfNaturalUrl}</a>", $parser->parseParagraph($utfNaturalUrl), "Natural UTF-8 URL needs no conversion.");
    self::assertStringEndsWith(">{$utfNaturalUrl}</a>", $parser->parseParagraph($utfEncodedUrl), "Encoded UTF-8 URL will be converted to readable format.");
    self::assertStringEndsWith(">{$eucEncodedUrl}</a>", $parser->parseParagraph($eucEncodedUrl), "Non UTF-8 URL should never be converted.");
    // See: \cebe\markdown\inline\UrlLinkTrait::renderAutoUrl
  }

}
