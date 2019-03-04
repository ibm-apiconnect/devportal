<?php

namespace Drupal\Tests\ghmarkdown\Unit;

use Drupal\ghmarkdown\cebe\markdown\MarkdownExtra;

/**
 * @author Carsten Brandt <mail@cebe.cc>
 * @group ghmarkdown
 */
class MarkdownExtraTest extends BaseMarkdownTest {

  public function createMarkdown() {
    return new MarkdownExtra();
  }

  public function getDataPaths() {
    return [
      'markdown-data' => __DIR__ . '/markdown-data',
      'extra-data' => __DIR__ . '/extra-data',
    ];
  }
}