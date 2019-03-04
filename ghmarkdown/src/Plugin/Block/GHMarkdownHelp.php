<?php

namespace Drupal\ghmarkdown\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides the GHMarkdownHelp block.
 *
 * @Block(
 *   id = "markdown_help",
 *   admin_label = @Translation("Markdown filter tips")
 * )
 */
class GHMarkdownHelp extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    $build['#title'] = t('Markdown filter tips');
    $build['#markup'] = $this->markdownHelpContent();

    return $build;
  }

  protected function markdownHelpContent(): string {
    return '<pre>' . t('
## Header 2 ##
### Header 3 ###
#### Header 4 ####
##### Header 5 #####
(Hashes on right are optional)

Link [Drupal](http://drupal.org)

Inline markup like _italics_,
 **bold**, and `code()`.

> Blockquote. Like email replies
>> And, they can be nested

* Bullet lists are easy too
- Another one
+ Another one

1. A numbered list
2. Which is numbered
3. With periods and a space

And now some code:
    // Code is indented text
    is_easy() to_remember();') . '</pre>';
  }

}
