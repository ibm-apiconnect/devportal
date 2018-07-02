<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace Drupal\Tests\ghmarkdown\Unit;

use Drupal\ghmarkdown\cebe\markdown\Markdown;

/**
 * Test support ordered lists at arbitrary number(`start` html attribute)
 * @author Maxim Hodyrew <maximkou@gmail.com>
 * @group ghmarkdown
 */
class MarkdownOLStartNumTest extends BaseMarkdownTest {
  public function createMarkdown() {
    $markdown = new Markdown();
    $markdown->keepListStartNumber = TRUE;
    return $markdown;
  }

  public function getDataPaths() {
    return [
      'markdown-data' => __DIR__ . '/markdown-ol-start-num-data',
    ];
  }
}
