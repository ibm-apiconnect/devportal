<?php


use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Url;
use Drupal\page_manager\Entity\Page;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_create_homepage_blocks() {
  // welcome banner
  $welcome_block_content = BlockContent::create([
    'info' => 'Welcome Banner [en]',
    'type' => 'basic',
    'body' => [
      'value' => get_localized_welcomebanner_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  add_welcome_block_translations($welcome_block_content);

  $welcome_values = [
    'id' => 'welcomebanner',
    'plugin' => 'block_content:' . $welcome_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => [
      'id' => 'block_content:' . $welcome_block_content->uuid(),
      'label' => 'Welcome banner',
      'provider' => 'block_content',
      'label_display' => 'visible',
    ],
    'theme' => 'connect_theme',
    'visibility' => [],
    'weight' => 0,
  ];
  $welcome_block_instance = Block::create($welcome_values);
  $welcome_block_instance->save();

  // getting started block
  $gsg_block_content = BlockContent::create([
    'info' => 'Getting Started [en]',
    'type' => 'basic',
    'body' => [
      'value' => get_localized_gsb_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  add_getting_started_block_content_translations($gsg_block_content);

  $gsg_values = [
    'id' => 'getting_started',
    'plugin' => 'block_content:' . $gsg_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => [
      'id' => 'block_content:' . $gsg_block_content->uuid(),
      'label' => 'Getting Started',
      'provider' => 'block_content',
      'label_display' => FALSE,
    ],
    'theme' => 'connect_theme',
    'visibility' => [],
    'weight' => 0,
  ];
  $gsg_block = Block::create($gsg_values);
  $gsg_block->save();

  // get help block
  $gethelp_block_content = BlockContent::create([
    'info' => 'Get Help [en]',
    'type' => 'basic',
    'body' => [
      'value' => get_localized_gethelp_content('en'),
      'format' => 'full_html',
    ],
  ]);
  add_get_help_block_content_translations($gethelp_block_content);
  $gethelp_values = [
    'id' => 'get_help',
    'plugin' => 'block_content:' . $gethelp_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => [
      'id' => 'block_content:' . $gethelp_block_content->uuid(),
      'label' => 'Get Help',
      'provider' => 'block_content',
      'label_display' => FALSE,
    ],
    'theme' => 'connect_theme',
    'visibility' => [],
    'weight' => 520,
  ];
  $gethelp_block = Block::create($gethelp_values);
  $gethelp_block->save();

  // Add the block instances to the welcome page
  // load the welcome page that should have been created from our YAML
  $page = Page::load('welcome');
  // should only be one page variant, so we'll just grab the first one
  if ($page !== NULL) {
    $page_variants = $page->getVariants();
    reset($page_variants);
    $page_variant = array_values($page_variants)[0];

    $variant_plugin = $page_variant->getVariantPlugin();
    // Add Welcome block
    $add_welcome_block = [
      'id' => 'block_content:' . $welcome_block_content->uuid(),
      'label' => 'Welcome Banner',
      'provider' => 'block_content',
      'label_display' => FALSE,
      'view_mode' => 'full',
      'status' => TRUE,
      'region' => 'col_1',
      'weight' => 0,
      'context_mapping' => [],
    ];
    $variant_plugin->addBlock($add_welcome_block);

    // Add featured APIs block
    $add_featured_block = [
      'id' => 'featuredcontent',
      'plugin' => 'featuredcontent',
      'label' => 'Featured Content',
      'provider' => 'featuredcontent',
      'region' => 'col_1',
      'nodeType' => 'product',
      'selectionType' => 'CREATED',
      'numberOfTiles' => 6,
      'theme' => 'connect_theme',
      'weight' => 1,
      'context_mapping' => [],
      'label_display' => FALSE,
    ];
    $variant_plugin->addBlock($add_featured_block);

    // Add case study block
    $add_casestudy_block = [
      'id' => 'casestudy_block',
      'plugin' => 'casestudy_block',
      'label' => 'Case Studies',
      'provider' => 'case_study',
      'region' => 'col_1',
      'numberOfTiles' => 4,
      'selectionType' => 'CREATED',
      'customNodes' => [],
      'theme' => 'connect_theme',
      'weight' => 2,
      'context_mapping' => [],
      'label_display' => FALSE,
    ];
    $variant_plugin->addBlock($add_casestudy_block);

    // Add getting started block
    $add_gsg_block = [
      'id' => 'block_content:' . $gsg_block_content->uuid(),
      'label' => 'Getting Started',
      'provider' => 'block_content',
      'view_mode' => 'full',
      'status' => TRUE,
      'region' => 'col_1',
      'weight' => 3,
      'context_mapping' => [],
      'label_display' => FALSE,
    ];
    $variant_plugin->addBlock($add_gsg_block);

    // add socialblock
    $add_social_block = [
      'id' => 'social_block',
      'plugin' => 'social_block',
      'label' => 'Social Block',
      'provider' => 'socialblock',
      'region' => 'col_1',
      'twitterSearchBy' => 0,
      'twitterSearchParameter' => "ibmcloud",
      'twitterTweetTypes' => 0,
      'forumsList' => ["1"],
      'numberOfTiles' => 9,
      'theme' => 'connect_theme',
      'weight' => 4,
      'context_mapping' => [],
      'label_display' => FALSE,
    ];
    $variant_plugin->addBlock($add_social_block);
    // getting help
    $add_gettinghelp_block = [
      'id' => 'block_content:' . $gethelp_block_content->uuid(),
      'label' => 'Get help',
      'provider' => 'block_content',
      'label_display' => FALSE,
      'view_mode' => 'full',
      'status' => TRUE,
      'region' => 'col_1',
      'weight' => 5,
      'context_mapping' => [],
    ];
    $variant_plugin->addBlock($add_gettinghelp_block);
    $page_variant->save();
  }
}

/**
 * Function to update the block content if its missing or needs translations adding.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_update_homepage_blocks() {
  $welcomebannerblock = Block::load('welcomebanner');
  if ($welcomebannerblock !== NULL) {
    $plugin = $welcomebannerblock->get('plugin');
    if ($plugin !== NULL) {
      $parts = explode(':', $plugin);
      $block_content_id = $parts[1];
      $query = \Drupal::entityQuery('block_content');
      $query->condition('uuid.value', $block_content_id);
      $nids = $query->accessCheck()->execute();
      if (isset($nids) && !empty($nids)) {
        $blockid = array_shift($nids);
        $block_content = BlockContent::load($blockid);
        if ($block_content !== NULL) {
          if (!$block_content->hasTranslation('en')) {
            $block_content->set('body', [
              'value' => get_localized_welcomebanner_content('en'),
              'format' => 'full_html',
            ]);
            $block_content->save();
          }
          add_welcome_block_translations($block_content, FALSE);
        }
      }
    }
  }

  $gsgblock = Block::load('getting_started');
  if ($gsgblock !== NULL) {
    $plugin = $gsgblock->get('plugin');
    if ($plugin !== NULL) {
      $parts = explode(':', $plugin);
      $block_content_id = $parts[1];
      $query = \Drupal::entityQuery('block_content');
      $query->condition('uuid.value', $block_content_id);
      $nids = $query->accessCheck()->execute();
      if (isset($nids) && !empty($nids)) {
        $blockid = array_shift($nids);
        $block_content = BlockContent::load($blockid);
        if ($block_content !== NULL) {
          if (!$block_content->hasTranslation('en')) {
            $block_content->set('body', [
              'value' => get_localized_gsb_content('en'),
              'format' => 'full_html',
            ]);
            $block_content->save();
          }
          add_getting_started_block_content_translations($block_content, FALSE);
        }
      }
    }
  }

  $gethelpblock = Block::load('get_help');
  if ($gethelpblock !== NULL) {
    $plugin = $gethelpblock->get('plugin');
    if ($plugin !== NULL) {
      $parts = explode(':', $plugin);
      $block_content_id = $parts[1];
      $query = \Drupal::entityQuery('block_content');
      $query->condition('uuid.value', $block_content_id);
      $nids = $query->accessCheck()->execute();
      if (isset($nids) && !empty($nids)) {
        $blockid = array_shift($nids);
        $block_content = BlockContent::load($blockid);
        if ($block_content !== NULL) {
          if (!$block_content->hasTranslation('en')) {
            $block_content->set('body', [
              'value' => get_localized_gethelp_content('en'),
              'format' => 'full_html',
            ]);
            $block_content->save();
          }
          add_get_help_block_content_translations($block_content, FALSE);
        }
      }
    }
  }
}

/**
 * @param \Drupal\block_content\Entity\BlockContent $gethelp_block_content
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function add_get_help_block_content_translations(BlockContent $gethelp_block_content, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($gethelp_block_content->hasTranslation($lang) && !$overwrite)) {
      continue;
    }

    if ($gethelp_block_content->hasTranslation($lang)) {
      $gethelp_block_content->removeTranslation($lang);
    }
    $translation = [
      'info' => t('Get Help', [], ['langcode' => $lang]) . ' [' . $lang . ']',
      'type' => 'basic',
      'body' => [
        'value' => get_localized_gethelp_content($lang),
        'format' => 'full_html',
      ],
      'langcode' => $lang,
    ];
    $gethelp_block_content->addTranslation($lang, $translation);
  }
  $gethelp_block_content->save();
}

/**
 * @param \Drupal\block_content\Entity\BlockContent $gsg_block_content
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function add_getting_started_block_content_translations(BlockContent $gsg_block_content, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($gsg_block_content->hasTranslation($lang) && !$overwrite)) {
      continue;
    }

    if ($gsg_block_content->hasTranslation($lang)) {
      $gsg_block_content->removeTranslation($lang);
    }

    $translation = [
      'info' => t('Getting Started', [], ['langcode' => $lang]) . ' [' . $lang . ']',
      'type' => 'basic',
      'body' => [
        'value' => get_localized_gsb_content($lang),
        'format' => 'full_html',
      ],
      'langcode' => $lang,
    ];
    $gsg_block_content->addTranslation($lang, $translation);
  }

  $gsg_block_content->save();
}

/**
 * @param \Drupal\block_content\Entity\BlockContent $welcome_block_content
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function add_welcome_block_translations(BlockContent $welcome_block_content, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($welcome_block_content->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($welcome_block_content->hasTranslation($lang)) {
      $welcome_block_content->removeTranslation($lang);
    }

    $translation = [
      'info' => t('Welcome Banner', [], ['langcode' => $lang]) . ' [' . $lang . ']',
      'type' => 'basic',
      'body' => [
        'value' => get_localized_welcomebanner_content($lang),
        'format' => 'full_html',
      ],
      'langcode' => $lang,
    ];
    $welcome_block_content->addTranslation($lang, $translation);
  }

  $welcome_block_content->save();
}

/**
 * @param $langcode
 *
 * @return string
 */
function get_localized_welcomebanner_content($langcode): string {
  $html = '<div class="welcomebanner"><div class="welcomebannertext"><h2>';
  $html .= t('Brace yourselves.', [], ['langcode' => $langcode]) . '<br />';
  $html .= t('APIs are coming.', [], ['langcode' => $langcode]) . '</h2><p>';
  $html .= t('Explore, get access to and be creative with our APIs.', [], ['langcode' => $langcode]) . '<br />';
  $html .= t('We can\'t wait to see what you come up with!', [], ['langcode' => $langcode]) . '</p><div class="bannerButtonRow">';
  $html .= '<a class="button cta-btns--white__btn" href="product">';
  $html .= t('Explore API Documentation', [], ['langcode' => $langcode]) . '</a></div></div></div>';
  return $html;
}

/**
 * @param $langcode
 *
 * @return string
 */
function get_localized_gsb_content($langcode): string {
  $html = '<div class="startguide">';
  $html .= '<div class="gsstep title">';
  $html .= t('<h4>Let\'s get started!</h4>', [], ['langcode' => $langcode]);
  $html .= '<p>' . t('Here is the step-by-step guide to get access to our APIs and incorporate our functionality into your projects.', ['@link' => 'product'], ['langcode' => $langcode]) . '</p></div>';
  $html .= '<div class="gsstep explore"><div class="placeholder"></div>';
  $html .= t('<h4>Explore APIs</h4>', [], ['langcode' => $langcode]);
  $html .= '<p>' . t('Take a look at our <a href="@link">API products</a> and quickly find APIs to construct a fully featured application.', ['@link' => 'product'], ['langcode' => $langcode]) . '</p></div>';
  $html .= '<div class="gsstep signup"><div class="placeholder"></div>';
  $html .= t('<h4>Sign up</h4>', [], ['langcode' => $langcode]);
  $html .= '<p>' . t('<a href="@link">Create a new account</a> and get started with our APIs. It\'s free to join.', ['@link' => 'user/register'], ['langcode' => $langcode]) . '</p></div>';
  $html .= '<div class="gsstep subscribe"><div class="placeholder"></div>';
  $html .= t('<h4>Create</h4>', [], ['langcode' => $langcode]);
  $html .= '<p>' . t('Select a plan and create your application to make use of our APIs.', [], ['langcode' => $langcode]) . '</p></div>';
  $html .= '<div class="gsstep check-usage"><div class="placeholder"></div>';
  $html .= t('<h4>Check usage</h4>', [], ['langcode' => $langcode]);
  $html .= '<p>' . t('Check usage for different APIs that your application has made from your application.', [], ['langcode' => $langcode]) . '</p></div>';
  $html .= '</div>';
  return $html;
}

/**
 * @param $langcode
 *
 * @return string
 */
function get_localized_gethelp_content($langcode): string {
  $forward_svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
   viewBox="0 0 16 16" width="16" height="16" fill="currentColor" xml:space="preserve" aria-label="Forward arrow" role="img">
      <g>
          <path  d="M9.3 3.7L13.1 7.5 1 7.5 1 8.5 13.1 8.5 9.3 12.3 10 13 15 8 10 3z"/>
      </g>
    </svg>';

  $html = '<div class="what_will_you_build"><div class="build_content"><h3>' . t('What will you build?', [], ['langcode' => $langcode]) . '</h3><p>' . t('We understand that having the right tools is essential to bring your ideas to life. Use the Developer Portal to get access to our APIs and start creating now.', [], ['langcode' => $langcode]) . '</p></div><div class="build_button"><a class="button cta-btn" href="product">' . t('Get started', [], ['langcode' => $langcode]) . '</a></div></div>';
  
  $html .= '<div class="get_help">';
  $html .= '<div class="helper_col get_started_col"><h4>' . t('Get Started', [], ['langcode' => $langcode]) . '</h4><div>' . t('Get started with our APIs by creating an account and exploring the documentation to find what\'s right for you.', [], ['langcode' => $langcode]) . '</div><div><a href="user/register">' . t('Create an Account', [], ['langcode' => $langcode]) . $forward_svg . '</a></div></div>';
  $html .= '<div class="helper_col forum_col"><h4>' . t('Forum', [], ['langcode' => $langcode]) . '</h4><div>' . t('Ask a question in the forums or search to forum history to see if it\'s been asked before.', [], ['langcode' => $langcode]) . '</div><div><a href="forum">' . t('Join the discussion', [], ['langcode' => $langcode]) . $forward_svg . '</a></div></div>';
  $html .= '<div class="helper_col contact_us-_ol"><h4>' . t('Contact Us', [], ['langcode' => $langcode]) . '</h4><div>' . t('Can\'t find the answer to your question? Need more help? Have some feedback? Let us know!', [], ['langcode' => $langcode]) . '</div><div><a href="contact">' . t('Email us', [], ['langcode' => $langcode]) . $forward_svg . '</a></div></div></div>';
  return $html;
}

/**
 * Function to update all the links in the getting started guide from /products to /product
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function update_gsg_block() {
  $query = \Drupal::entityQuery('block_content');
  $query->condition('info.value', 'Getting Started [en]');
  $nids = $query->accessCheck()->execute();

  if (isset($nids) && !empty($nids)) {
    $blockId = array_shift($nids);
    $block = BlockContent::load($blockId);
    if ($block !== NULL) {
      // update english
      $bodyValue = $block->body->value;
      // href="products"
      $updated = str_replace('href="products"', 'href="product"', $bodyValue);
      $block->set('body', [
        'value' => $updated,
        'format' => 'full_html',
      ]);
      $block->save();

      $languages = \Drupal::languageManager()->getLanguages();
      foreach ($languages as $language) {
        if ($language->getId() === 'en') {
          continue;
        }
        if (!$block->hasTranslation($language->getId())) {
          continue;
        }
        $translation = $block->getTranslation($language->getId());
        $bodyValue = $translation->body->value;
        $updated = str_replace('href="products"', 'href="product"', $bodyValue);
        $translation->set('body', [
          'value' => $updated,
          'format' => 'full_html',
        ]);
        $translation->save();
      }
    }
  }
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_create_forum_block() {
  try {
    $block = \Drupal::entityTypeManager()
      ->getStorage('block')
      ->load('forumsidebar');
    // If the block exist, just return.
  } catch (\Exception $e) {
    // Do Nothing.
  }

  if (!empty($block)) {
    return;
  }

  // welcome banner
  $forum_block_content = BlockContent::create([
    'info' => 'Forum Sidebar [en]',
    'type' => 'basic',
    'body' => [
      'value' => get_localized_forumblock_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  add_forum_block_translations($forum_block_content);

  $forum_values = [
    'id' => 'forumsidebar',
    'plugin' => 'block_content:' . $forum_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => [
      'id' => 'block_content:' . $forum_block_content->uuid(),
      'label' => 'Forum Sidebar',
      'provider' => 'block_content',
      'label_display' => '0',
    ],
    'theme' => 'connect_theme',
    'visibility' => [],
    'weight' => 0,
  ];
  $forum_block_instance = Block::create($forum_values);
  $forum_block_instance->save();
}

/**
 * Function to update the block content if its missing or needs translations adding.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_update_forumsidebar_block() {
  $forumsidebarblock = Block::load('forumsidebar');
  if ($forumsidebarblock !== NULL) {
    $plugin = $forumsidebarblock->get('plugin');
    if ($plugin !== NULL) {
      $parts = explode(':', $plugin);
      $block_content_id = $parts[1];
      $query = \Drupal::entityQuery('block_content');
      $query->condition('uuid.value', $block_content_id);
      $nids = $query->accessCheck()->execute();
      if (isset($nids) && !empty($nids)) {
        $blockid = array_shift($nids);
        $block_content = BlockContent::load($blockid);
        if ($block_content !== NULL) {
          if (!$block_content->hasTranslation('en')) {
            $block_content->set('body', [
              'value' => get_localized_forumblock_content('en'),
              'format' => 'full_html',
            ]);
          }
          $block_content->save();
          add_forum_block_translations($block_content, FALSE);
        }
      }
    }
  }
}

/**
 * @param $langcode
 *
 * @return string
 */
function get_localized_forumblock_content($langcode): string {
  $html = '<div class="forumSidebar">';
  $html .= '<div class="supporttext"><h2>';
  $html .= t('Support', [], ['langcode' => $langcode]) . '</h2><p>';
  $html .= t("Looking for help? Check out the frequently asked questions on our <a href='@supportlink'>support page</a>.", ['@supportlink' => 'support'], ['langcode' => $langcode]) . '</p></div>';
  $html .= '<div class="contacttext"><h2>';
  $html .= t('Contact us', [], ['langcode' => $langcode]) . '</h2><p>';
  $html .= t("Send us a <a href='@contactlink'>message</a>. We'll get back to you as soon as we can.", ['@contactlink' => 'contact'], ['langcode' => $langcode]) . '</p></div>';
  $html .= '<div class="socialtext"><h2>';
  $html .= t('Get Social', [], ['langcode' => $langcode]) . '</h2></div>';
  $html .= '</div>';
  return $html;
}

/**
 * @param \Drupal\block_content\Entity\BlockContent $forum_block_content
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function add_forum_block_translations(BlockContent $forum_block_content, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($forum_block_content->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($forum_block_content->hasTranslation($lang)) {
      $forum_block_content->removeTranslation($lang);
    }

    $translation = [
      'info' => t('Forum Sidebar', [], ['langcode' => $lang]) . ' [' . $lang . ']',
      'type' => 'basic',
      'body' => [
        'value' => get_localized_forumblock_content($lang),
        'format' => 'full_html',
      ],
      'langcode' => $lang,
    ];
    $forum_block_content->addTranslation($lang, $translation);
  }

  $forum_block_content->save();
}

/**
 * Function to update all the links in the forum sidebar block
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_update_forum_block() {
  $block = NULL;
  $query = \Drupal::entityQuery('block_content');
  $query->condition('info.value', 'Forum Sidebar [en]');
  $nids = $query->accessCheck()->execute();

  if (isset($nids) && !empty($nids)) {
    $blockId = array_shift($nids);
    $block = BlockContent::load($blockId);
  }
  if ($block !== NULL) {
    // update english
    $bodyValue = $block->body->value;

    $newSupportString = '<a href=\'' . Url::fromRoute('ibm_apim.support')->toString() . '\'>';
    $newContactString = '<a href=\'' . Url::fromRoute('contact.site_page')->toString() . '\'>';
    $updated = preg_replace('/<a href=[\'"][a-zA-Z_\-0-9\/]*support[\'"]>/', $newSupportString, $bodyValue);
    $updated = preg_replace('/<a href=[\'"][a-zA-Z_\-0-9\/]*contact[\'"]>/', $newContactString, $updated);
    $block->set('body', [
      'value' => $updated,
      'format' => 'full_html',
    ]);
    $block->save();

    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $language) {
      if ($language->getId() === 'en') {
        continue;
      }
      if (!$block->hasTranslation($language->getId())) {
        continue;
      }
      $translation = $block->getTranslation($language->getId());
      $bodyValue = $translation->body->value;
      $updated = preg_replace('/<a href=[\'"][a-zA-Z_\-0-9\/]*support[\'"]>/', $newSupportString, $bodyValue);
      $updated = preg_replace('/<a href=[\'"][a-zA-Z_\-0-9\/]*contact[\'"]>/', $newContactString, $updated);
      $translation->set('body', [
        'value' => $updated,
        'format' => 'full_html',
      ]);
      $translation->save();
    }
  }
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_main_menu_links() {
  $menu_name = 'main';

  $home_link = MenuLinkContent::create([
    'title' => t('Home', [], ['langcode' => 'en']),
    'description' => t('Portal homepage', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/'],
    'menu_name' => $menu_name,
    'expanded' => TRUE,
    'weight' => -1,
  ]);
  $home_link->save();
  add_home_link_translations($home_link);

  $apis_link = MenuLinkContent::create([
    'title' => t('API Products', [], ['langcode' => 'en']),
    'description' => t('Browse available APIs', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/product', [], ['langcode' => 'en']],
    'menu_name' => $menu_name,
    'weight' => 5,
    'langcode' => 'en',
  ]);
  $apis_link->save();
  add_apis_link_translations($apis_link);

  $apps_link = MenuLinkContent::create([
    'title' => t('Apps', [], ['langcode' => 'en']),
    'description' => t('Create and manage your apps', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/application', [], ['langcode' => 'en']],
    'menu_name' => $menu_name,
    'weight' => 10,
    'langcode' => 'en',
  ]);
  $apps_link->save();
  add_apps_link_translations($apps_link);

  $forum_link = MenuLinkContent::create([
    'title' => t('Forums'),
    'description' => t('Interact with our community'),
    'link' => ['uri' => 'internal:/forum'],
    'menu_name' => $menu_name,
    'expanded' => TRUE,
    'weight' => 25,
  ]);
  $forum_link->save();
  add_forum_link_translations($forum_link);

  // Create News menu link if ibm_apic_news module is enabled
  if (\Drupal::moduleHandler()->moduleExists('ibm_apic_news')) {
    $news_link = MenuLinkContent::create([
      'title' => t('News'),
      'description' => t('Portal homepage'),
      'link' => ['uri' => 'internal:/news'],
      'menu_name' => $menu_name,
      'expanded' => TRUE,
      'weight' => 27,
    ]);
    $news_link->save();
    add_news_link_translations($news_link);
  }

  // update weight of the blogs link
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
  $blog_menu_id = 'views_view:views.blog.blog_all';
  $blogs_link = $menu_link_manager->getDefinition($blog_menu_id);
  $blogs_link['weight'] = 21;
  $menu_link_manager->updateDefinition($blog_menu_id, $blogs_link);
  $cache = \Drupal::cache('menu');
  $cache->deleteAll();
}

/**
 * @param $link
 */
function add_news_link_translations($link, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($link->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('News', [], ['langcode' => $lang]),
      'description' => t('Portal homepage', [], ['langcode' => $lang]),
      'link' => ['uri' => 'internal:/news'],
      'menu_name' => 'main',
      'expanded' => TRUE,
      'weight' => 27,
    ];
    $link->addTranslation($lang, $translation);
  }

  $link->save();
}

/**
 * @param $link
 */
function add_forum_link_translations($link, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($link->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Forums', [], ['langcode' => $lang]),
      'description' => t('Interact with our community', [], ['langcode' => $lang]),
      'link' => ['uri' => 'internal:/forum'],
      'menu_name' => 'main',
      'expanded' => TRUE,
      'weight' => 25,
    ];
    $link->addTranslation($lang, $translation);
  }

  $link->save();
}

/**
 * @param $link
 */
function add_apps_link_translations($link, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($link->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Apps', [], ['langcode' => $lang]),
      'description' => t('Create and manage your apps', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $link->addTranslation($lang, $translation);
  }
  $link->save();
}

/**
 * @param $link
 */
function add_apis_link_translations($link, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($link->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('API Products', [], ['langcode' => $lang]),
      'description' => t('Browse available APIs', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $link->addTranslation($lang, $translation);
  }
  $link->save();
}

/**
 * @param $link
 */
function add_home_link_translations($link, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($link->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Home', [], ['langcode' => $lang]),
      'description' => t('Portal homepage', [], ['langcode' => $lang]),
      'link' => ['uri' => 'internal:/'],
      'menu_name' => 'main',
      'expanded' => TRUE,
      'weight' => -1,
    ];
    $link->addTranslation($lang, $translation);
  }

  $link->save();
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_login_menu_links() {
  $menu_name = 'login';

  $login_link = MenuLinkContent::create([
    'title' => t('Sign in'),
    'description' => t('Sign in to your account'),
    'link' => ['uri' => 'internal:/user/login'],
    'menu_name' => $menu_name,
    'expanded' => TRUE,
    'weight' => 20,
  ]);
  $login_link->save();
  add_sign_in_link_translations($login_link);


  $register_link = MenuLinkContent::create([
    'title' => t('Create account'),
    'description' => t('Create a new account'),
    'link' => ['uri' => 'internal:/user/register'],
    'menu_name' => $menu_name,
    'expanded' => TRUE,
    'weight' => 10,
  ]);
  $register_link->save();
  add_create_account_link_translations($register_link);
}

/**
 * @param $link
 */
function add_create_account_link_translations($link, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($link->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Create account', [], ['langcode' => $lang]),
      'description' => t('Create a new account', [], ['langcode' => $lang]),
      'link' => ['uri' => 'internal:/user/register'],
      'menu_name' => 'login',
      'expanded' => TRUE,
      'weight' => 10,
    ];
    $link->addTranslation($lang, $translation);
  }

  $link->save();
}

/**
 * @param $link
 */
function add_sign_in_link_translations($link, $overwrite = true): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en' || ($link->hasTranslation($lang) && !$overwrite)) {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Sign in', [], ['langcode' => $lang]),
      'description' => t('Sign in to your account', [], ['langcode' => $lang]),
      'link' => ['uri' => 'internal:/user/login'],
      'menu_name' => 'login',
      'expanded' => TRUE,
      'weight' => 20,
    ];
    $link->addTranslation($lang, $translation);
  }
  $link->save();
}

/**
 * Update the node incase translations or new langcodes are added
 */
function apim_profile_update_menu_links() {
  try {
    apim_profile_update_main_menu_links();
    apim_profile_update_login_menu_links();
  } catch (PluginNotFoundException | PluginException | EntityStorageException $e) {
  }
}

/**
 * Updates translations for the main menu or adds new languages if added
 */
function apim_profile_update_main_menu_links() {
  $home_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['link' => 'internal:/']);
  $home_link = reset($home_link);
  if (!empty($home_link)) {
    add_home_link_translations($home_link, FALSE);
  }

  $apis_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['link' => 'internal:/product']);
  $apis_link = reset($apis_link);
  if (!empty($apis_link)) {
    add_apis_link_translations($apis_link, FALSE);
  }

  $apps_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['link' => 'internal:/application']);
  $apps_link = reset($apps_link);
  if (!empty($apps_link)) {
    add_apps_link_translations($apps_link, FALSE);
  }

  $forum_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['link' => 'internal:/forum']);
  $forum_link = reset($forum_link);
  if (!empty($forum_link)) {
    add_forum_link_translations($forum_link, FALSE);
  }
}

/**
 * Updates translations for the main menu or adds new languages if added
 */
function apim_profile_update_login_menu_links() {
  $login_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['link' => 'internal:/user/login']);
  $login_link = reset($login_link);
  if (!empty($login_link)) {
    add_sign_in_link_translations($login_link, FALSE);
  }

  $register_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['link' => 'internal:/user/register']);
  $register_link = reset($register_link);
  if (!empty($register_link)) {
    add_create_account_link_translations($register_link, FALSE);
  }
}

/**
 * Update homepage block weights
 *
 * Adjusts block weights to match the new homepage design:
 * - Case Study block: weight 3 -> 2
 * - Getting Started block: weight 2 -> 3
 * - IBM News block: weight 3 -> 3 
 * - Social Block: weight 3 -> 4
 * - Get Help block: weight 5 -> 5
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_update_block_configuration() {
  $moduleHandler = \Drupal::service('module_handler');
  if (!$moduleHandler->moduleExists('page_manager')) {
    return;
  }

  $page = Page::load('welcome');
  if ($page !== NULL) {
    $page_variants = $page->getVariants();
    if (!empty($page_variants)) {
      $page_variant = reset($page_variants);
      $variant_plugin = $page_variant->getVariantPlugin();
      $variant_settings = $page_variant->get('variant_settings');
      
      // Find Getting Started and Get Help block UUIDs by label
      $getting_started_uuid = NULL;
      $get_help_uuid = NULL;
      $content = $page_variant->calculateDependencies()['content'] ?? [];
      $block_content_storage = \Drupal::entityTypeManager()->getStorage('block_content');
      
      foreach ($content as $block_content_id) {
        // id = block_content:basic:UUID
        $parts = explode(':', $block_content_id);
        if (count($parts) >= 3) {
          $raw_block_content_id = $parts[2];
          $block_content_array = $block_content_storage->loadByProperties(['uuid' => $raw_block_content_id]);
          $block_content = reset($block_content_array);
          
          if ($block_content !== NULL && $block_content !== FALSE) {
            $label = $block_content->get('info')->value;
            if ($label === 'Getting Started [en]') {
              $getting_started_uuid = $raw_block_content_id;
            }
            elseif ($label === 'Get Help [en]') {
              $get_help_uuid = $raw_block_content_id;
            }
          }
        }
      }
      
      $needs_save = FALSE;
      
      if (isset($variant_settings['blocks'])) {
        foreach ($variant_settings['blocks'] as $block_id => $block_settings) {
          // Update Case Study block weight to 2
          if (isset($block_settings['id']) && $block_settings['id'] === 'casestudy_block') {
            if (isset($block_settings['weight']) && $block_settings['weight'] != 2) {
              $variant_plugin->updateBlock($block_id, ['weight' => 2]);
              $needs_save = TRUE;
            }
          }
          // Update Getting Started block weight to 3
          if ($getting_started_uuid !== NULL &&
              isset($block_settings['id']) &&
              $block_settings['id'] === 'block_content:' . $getting_started_uuid) {
            if (isset($block_settings['weight']) && $block_settings['weight'] != 3) {
              $variant_plugin->updateBlock($block_id, ['weight' => 3]);
              $needs_save = TRUE;
            }
          }
          // Update IBM News block weight to 3
          if (isset($block_settings['id']) && $block_settings['id'] === 'home_page_news_block') {
            if (isset($block_settings['weight']) && $block_settings['weight'] != 3) {
              $variant_plugin->updateBlock($block_id, ['weight' => 3]);
              $needs_save = TRUE;
            }
          }
          // Update Social block weight to 4
          if (isset($block_settings['id']) && $block_settings['id'] === 'social_block') {
            if (isset($block_settings['weight']) && $block_settings['weight'] != 4) {
              $variant_plugin->updateBlock($block_id, ['weight' => 4]);
              $needs_save = TRUE;
            }
          }
          // Update Get Help block weight to 5
          if ($get_help_uuid !== NULL &&
              isset($block_settings['id']) &&
              $block_settings['id'] === 'block_content:' . $get_help_uuid) {
            if (isset($block_settings['weight']) && $block_settings['weight'] != 5) {
              $variant_plugin->updateBlock($block_id, ['weight' => 5]);
              $needs_save = TRUE;
            }
          }
        }
      }

      if ($needs_save) {
        $page_variant->save();
      }
    }
  }
}

/**
 * Force update homepage block contents (Getting Started, Get Help, Welcome Banner)
 * This function updates the content regardless of whether translations exist.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_force_update_homepage_blocks() {
  // Update Welcome Banner block
  $welcomebannerblock = Block::load('welcomebanner');
  if ($welcomebannerblock !== NULL) {
    $plugin = $welcomebannerblock->get('plugin');
    if ($plugin !== NULL) {
      $parts = explode(':', $plugin);
      $block_content_id = $parts[1];
      $query = \Drupal::entityQuery('block_content');
      $query->condition('uuid.value', $block_content_id);
      $nids = $query->accessCheck()->execute();
      if (isset($nids) && !empty($nids)) {
        $blockid = array_shift($nids);
        $block_content = BlockContent::load($blockid);
        if ($block_content !== NULL) {
          // Force update English content
          $block_content->set('body', [
            'value' => get_localized_welcomebanner_content('en'),
            'format' => 'full_html',
          ]);
          $block_content->save();
          // Force update all translations (overwrite = TRUE)
          add_welcome_block_translations($block_content, TRUE);
        }
      }
    }
  }

  // Update Getting Started block
  $gsgblock = Block::load('getting_started');
  if ($gsgblock !== NULL) {
    $plugin = $gsgblock->get('plugin');
    if ($plugin !== NULL) {
      $parts = explode(':', $plugin);
      $block_content_id = $parts[1];
      $query = \Drupal::entityQuery('block_content');
      $query->condition('uuid.value', $block_content_id);
      $nids = $query->accessCheck()->execute();
      if (isset($nids) && !empty($nids)) {
        $blockid = array_shift($nids);
        $block_content = BlockContent::load($blockid);
        if ($block_content !== NULL) {
          // Force update English content
          $block_content->set('body', [
            'value' => get_localized_gsb_content('en'),
            'format' => 'full_html',
          ]);
          $block_content->save();
          // Force update all translations (overwrite = TRUE)
          add_getting_started_block_content_translations($block_content, TRUE);
        }
      }
    }
  }

  // Update Get Help block
  $gethelpblock = Block::load('get_help');
  if ($gethelpblock !== NULL) {
    $plugin = $gethelpblock->get('plugin');
    if ($plugin !== NULL) {
      $parts = explode(':', $plugin);
      $block_content_id = $parts[1];
      $query = \Drupal::entityQuery('block_content');
      $query->condition('uuid.value', $block_content_id);
      $nids = $query->accessCheck()->execute();
      if (isset($nids) && !empty($nids)) {
        $blockid = array_shift($nids);
        $block_content = BlockContent::load($blockid);
        if ($block_content !== NULL) {
          // Force update English content
          $block_content->set('body', [
            'value' => get_localized_gethelp_content('en'),
            'format' => 'full_html',
          ]);
          $block_content->save();
          // Force update all translations (overwrite = TRUE)
          add_get_help_block_content_translations($block_content, TRUE);
        }
      }
    }
  }
}
