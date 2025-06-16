<?php

namespace Drupal\Tests\ghmarkdown\Unit;

use Drupal\ghmarkdown\cebe\markdown\MarkdownExtra;
use Drupal\Tests\ghmarkdown\Unit\Base\BaseMarkdownBaseClass;

/**
 * @author Carsten Brandt <mail@cebe.cc>
 * @group ghmarkdown
 */
class MarkdownExtraTest extends BaseMarkdownBaseClass {

  public static function createMarkdown(): MarkdownExtra {
    return new MarkdownExtra();
  }

  public static function getDataPaths(): array {
    return [
      'markdown-data' => __DIR__ . '/markdown-data',
      'extra-data' => __DIR__ . '/extra-data',
    ];
  }

}