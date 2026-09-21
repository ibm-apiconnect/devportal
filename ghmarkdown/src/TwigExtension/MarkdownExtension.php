<?php


namespace Drupal\ghmarkdown\TwigExtension;

use Drupal\ghmarkdown\cebe\markdown\GithubMarkdown;
use Drupal\Core\Render\Markup;
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
      new TwigFilter('markdown', array($this, 'filterParseMarkdown')),
    ];
  }

  /**
   * Filter to parse markdown
   */
  public static function filterParseMarkdown($txt): Markup {
    $parser = new GithubMarkdown();
    return Markup::create($parser->parse($txt));
  }

}