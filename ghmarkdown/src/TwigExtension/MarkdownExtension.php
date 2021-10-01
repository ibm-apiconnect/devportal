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

namespace Drupal\ghmarkdown\TwigExtension;

use Drupal\ghmarkdown\cebe\markdown\GithubMarkdown;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class MarkdownExtension.
 *
 * @package Drupal\ghmarkdown
 */
class MarkdownExtension extends AbstractExtension {

  /**
   *
   * This function must return the name of the extension. It must be unique.
   */
  public function getName(): string {
    return 'ghmarkdown.twig_extension';
  }

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters(): array {
    return [
      new TwigFilter('markdown', array($this, 'filterParseMarkdown'), array('is_safe' => array('html'))),
    ];
  }

  /**
   * Filter to parse markdown
   */
  public static function filterParseMarkdown($txt): string {
    $parser = new GithubMarkdown();
    return $parser->parse($txt);
  }

}