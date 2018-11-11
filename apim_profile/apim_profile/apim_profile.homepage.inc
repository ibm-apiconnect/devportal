<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\page_manager\Entity\Page;

function apim_profile_create_homepage_blocks() {

  $languages = \Drupal::languageManager()->getLanguages();

  // welcome banner
  $welcome_block_content = BlockContent::create(array(
    'info' => 'Welcome Banner [en]',
    'type' => 'basic',
    'body' => array(
      'value' => get_localized_welcomebanner_content('en'),
      'format' => 'full_html',
    ),
    'langcode' => 'en'
  ));

  // add translations for the welcomebanner
  foreach ($languages as $language) {
    if ($language->getId() === 'en') {
      continue;
    }
    $translation = array(
      'info' => t('Welcome Banner', [], ['langcode' => $language->getId()]) . ' [' . $language->getId() . ']',
      'type' => 'basic',
      'body' => array(
        'value' => get_localized_welcomebanner_content($language->getId()),
        'format' => 'full_html',
      ),
      'langcode' => $language->getId()
    );
    $welcome_block_content->addTranslation($language->getId(), $translation);
  }

  $welcome_block_content->save();

  $welcome_values = array(
    'id' => 'welcomebanner',
    'plugin' => 'block_content:' . $welcome_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => array(
      'id' => 'block_content:' . $welcome_block_content->uuid(),
      'label' => 'Welcome banner',
      'provider' => 'block_content',
      'label_display' => 'visible'
    ),
    'theme' => 'connect_theme',
    'visibility' => array(),
    'weight' => 0
  );
  $welcome_block_instance = Block::create($welcome_values);
  $welcome_block_instance->save();

  // getting started block
  $gsg_block_content = BlockContent::create(array(
    'info' => 'Getting Started [en]',
    'type' => 'basic',
    'body' => array(
      'value' => get_localized_gsb_content('en'),
      'format' => 'full_html',
    ),
    'langcode' => 'en'
  ));

  // add translations for the getting started block
  foreach ($languages as $language) {
    if ($language->getId() === 'en') {
      continue;
    }
    $translation = array(
      'info' => t('Getting Started', [], ['langcode' => $language->getId()]) . ' [' . $language->getId() . ']',
      'type' => 'basic',
      'body' => array(
        'value' => get_localized_gsb_content($language->getId()),
        'format' => 'full_html',
      ),
      'langcode' => $language->getId()
    );
    $gsg_block_content->addTranslation($language->getId(), $translation);
  }

  $gsg_block_content->save();
  $gsg_values = array(
    'id' => 'getting_started',
    'plugin' => 'block_content:' . $gsg_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => array(
      'id' => 'block_content:' . $gsg_block_content->uuid(),
      'label' => 'Getting Started',
      'provider' => 'block_content',
      'label_display' => FALSE
    ),
    'theme' => 'connect_theme',
    'visibility' => array(),
    'weight' => 0,
  );
  $gsg_block = Block::create($gsg_values);
  $gsg_block->save();

  // go ahead block
  $goahead_block_content = BlockContent::create(array(
    'info' => 'Go Ahead [en]',
    'type' => 'basic',
    'body' => array(
      'value' => get_localized_goahead_content('en'),
      'format' => 'full_html',
    ),
  ));
  foreach ($languages as $language) {
    if ($language->getId() === 'en') {
      continue;
    }
    $translation = array(
      'info' => t('Go Ahead', [], ['langcode' => $language->getId()]) . ' [' . $language->getId() . ']',
      'type' => 'basic',
      'body' => array(
        'value' => get_localized_goahead_content($language->getId()),
        'format' => 'full_html',
      ),
      'langcode' => $language->getId()
    );
    $goahead_block_content->addTranslation($language->getId(), $translation);
  }

  $goahead_block_content->save();
  $goahead_values = array(
    'id' => 'go_ahead',
    'plugin' => 'block_content:' . $goahead_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => array(
      'id' => 'block_content:' . $goahead_block_content->uuid(),
      'label' => 'Go Ahead',
      'provider' => 'block_content',
      'label_display' => FALSE
    ),
    'theme' => 'connect_theme',
    'visibility' => array(),
    'weight' => 510,
  );
  $goahead_block = Block::create($goahead_values);

  // get help block
  $gethelp_block_content = BlockContent::create(array(
    'info' => 'Get Help [en]',
    'type' => 'basic',
    'body' => array(
      'value' => get_localized_gethelp_content('en'),
      'format' => 'full_html',
    ),
  ));
  foreach ($languages as $language) {
    if ($language->getId() === 'en') {
      continue;
    }
    $translation = array(
      'info' => t('Get Help', [], ['langcode' => $language->getId()]) . ' [' . $language->getId() . ']',
      'type' => 'basic',
      'body' => array(
        'value' => get_localized_gethelp_content($language->getId()),
        'format' => 'full_html',
      ),
      'langcode' => $language->getId()
    );
    $gethelp_block_content->addTranslation($language->getId(), $translation);
  }
  $gethelp_block_content->save();
  $gethelp_values = array(
    'id' => 'get_help',
    'plugin' => 'block_content:' . $gethelp_block_content->uuid(),
    'provider' => NULL,
    'region' => 'disabled',
    'settings' => array(
      'id' => 'block_content:' . $gethelp_block_content->uuid(),
      'label' => 'Get Help',
      'provider' => 'block_content',
      'label_display' => FALSE
    ),
    'theme' => 'connect_theme',
    'visibility' => array(),
    'weight' => 520,
  );
  $gethelp_block = Block::create($gethelp_values);

  // Add the block instances to the welcome page
  // load the welcome page that should have been created from our YAML
  $page = Page::load('welcome');
  // should only be one page variant, so we'll just grab the first one
  $page_variants = $page->getVariants();
  reset($page_variants);
  $page_variant = array_values($page_variants)[0];

  $variant_plugin = $page_variant->getVariantPlugin();
  // Add Welcome block
  $add_welcome_block = array(
    'id' => 'block_content:' . $welcome_block_content->uuid(),
    'label' => 'Welcome Banner',
    'provider' => 'block_content',
    'label_display' => FALSE,
    'view_mode' => 'full',
    'status' => TRUE,
    'region' => 'col_1',
    'weight' => 0,
    'context_mapping' => array()
  );
  $variant_plugin->addBlock($add_welcome_block);

  // Add getting started block
  $add_gsg_block = array(
    'id' => 'block_content:' . $gsg_block_content->uuid(),
    'label' => 'Getting Started',
    'provider' => 'block_content',
    'view_mode' => 'full',
    'status' => TRUE,
    'region' => 'col_1',
    'weight' => 2,
    'context_mapping' => array(),
    'label_display' => FALSE,
  );
  $variant_plugin->addBlock($add_gsg_block);

  // Add featured APIs block
  $add_featured_block = array(
    'id' => 'featuredcontent',
    'plugin' => 'featuredcontent',
    'label' => 'Featured Content',
    'provider' => 'featuredcontent',
    'region' => 'col_1',
    'nodeType' => 'product',
    'selectionType' => 'CREATED',
    'numberOfTiles' => 3,
    'theme' => 'connect_theme',
    'weight' => 1,
    'context_mapping' => array(),
    'label_display' => FALSE,
  );
  $variant_plugin->addBlock($add_featured_block);

  // add socialblock
  $add_social_block = array(
    'id' => 'social_block',
    'plugin' => 'social_block',
    'label' => 'Social Block',
    'provider' => 'socialblock',
    'region' => 'col_1',
    'twitterSearchBy' => 0,
    'twitterSearchParameter' => "IBMintegration",
    'twitterTweetTypes' => 0,
    'forumsList' => array("1"),
    'numberOfTiles' => 9,
    'theme' => 'connect_theme',
    'weight' => 3,
    'context_mapping' => array(),
    'label_display' => FALSE,
  );
  $variant_plugin->addBlock($add_social_block);
  // go ahead
  $add_goahead_block = array(
    'id' => 'block_content:' . $goahead_block_content->uuid(),
    'label' => 'Go ahead',
    'provider' => 'block_content',
    'label_display' => FALSE,
    'view_mode' => 'full',
    'status' => TRUE,
    'region' => 'col_1',
    'weight' => 4,
    'context_mapping' => array()
  );
  $variant_plugin->addBlock($add_goahead_block);
  // getting help
  $add_gettinghelp_block = array(
    'id' => 'block_content:' . $gethelp_block_content->uuid(),
    'label' => 'Get help',
    'provider' => 'block_content',
    'label_display' => FALSE,
    'view_mode' => 'full',
    'status' => TRUE,
    'region' => 'col_1',
    'weight' => 5,
    'context_mapping' => array()
  );
  $variant_plugin->addBlock($add_gettinghelp_block);
  $page_variant->save();
}

function get_localized_welcomebanner_content($langcode) {

  $bee_svg = '<?xml version="1.0" encoding="iso-8859-1"?>
<!-- Generator: Adobe Illustrator 20.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 128 128" xml:space="preserve">
<g>
	<path d="M47.373,54h33.254c-3.586-4.822-9.692-8-16.627-8C57.064,46,50.959,49.178,47.373,54z"/>
	<path d="M44.131,62C44.047,62.656,44,63.324,44,64v6h40v-6c0-0.676-0.047-1.344-0.131-2H44.131z"/>
	<path d="M44,84c0,0.676,0.047,1.344,0.131,2h39.738C83.953,85.344,84,84.676,84,84v-6H44V84z"/>
	<path d="M64,102c6.935,0,13.041-3.178,16.627-8H47.373C50.959,98.822,57.064,102,64,102z"/>
	<path d="M12.922,58.197c-1.703,0.707-3.305,1.746-4.689,3.131c-1.387,1.387-2.424,2.984-3.131,4.688
		c-0.733,1.77-1.104,3.65-1.102,5.531c-0.002,1.887,0.369,3.766,1.102,5.533c0.707,1.709,1.744,3.305,3.131,4.689
		c1.385,1.387,2.986,2.424,4.688,3.133C14.678,85.629,16.551,86,18.424,86c0.01,0,0.02,0,0.029,0c0.01,0,0.02,0,0.029,0
		c1.871,0,3.742-0.371,5.502-1.102c1.707-0.705,3.303-1.744,4.688-3.129c1.385-1.385,2.426-2.982,3.133-4.689L44,46L12.922,58.197z
		 M28.096,75.581c-0.528,1.263-1.286,2.394-2.253,3.36c-0.974,0.974-2.113,1.734-3.392,2.263C21.178,81.732,19.843,82,18.482,82
		h-0.059c-1.363,0-2.701-0.268-3.965-0.79c-1.287-0.536-2.429-1.299-3.4-2.271c-0.971-0.971-1.732-2.11-2.262-3.391
		C8.267,74.27,7.998,72.924,8,71.543c-0.002-1.371,0.267-2.716,0.796-3.994c0.531-1.279,1.293-2.42,2.265-3.392
		c0.962-0.962,2.093-1.719,3.361-2.251l22.505-8.833L28.096,75.581z"/>
	<path d="M122.898,66.016c-0.707-1.703-1.744-3.301-3.131-4.688c-1.385-1.385-2.986-2.424-4.689-3.131L84,46l12.195,31.08
		c0.707,1.707,1.748,3.305,3.133,4.689c1.385,1.385,2.98,2.424,4.688,3.129c1.76,0.73,3.631,1.102,5.502,1.102
		c0.01,0,0.02,0,0.029,0c0.01,0,0.02,0,0.029,0c1.873,0,3.746-0.371,5.504-1.098c1.701-0.709,3.303-1.746,4.688-3.133
		c1.387-1.385,2.424-2.98,3.131-4.689c0.732-1.768,1.104-3.646,1.102-5.533C124.002,69.666,123.631,67.785,122.898,66.016z
		 M119.202,75.551c-0.528,1.278-1.29,2.418-2.265,3.393c-0.967,0.968-2.109,1.73-3.385,2.262c-1.275,0.527-2.613,0.795-3.976,0.795
		h-0.059c-1.361,0-2.696-0.268-3.975-0.799c-1.272-0.525-2.412-1.286-3.386-2.26c-0.967-0.967-1.725-2.098-2.253-3.36l-8.831-22.509
		l22.505,8.833c1.268,0.532,2.399,1.289,3.361,2.251c0.972,0.973,1.734,2.113,2.264,3.389c0.53,1.282,0.799,2.627,0.797,4.006
		C120.001,72.924,119.733,74.27,119.202,75.551z"/>
	<path d="M54,42c4.418,0,8-3.58,8-8s-3.582-8-8-8s-8,3.58-8,8S49.582,42,54,42z M54,30c2.206,0,4,1.795,4,4s-1.794,4-4,4
		c-2.206,0-4-1.795-4-4S51.794,30,54,30z"/>
	<path d="M74,42c4.418,0,8-3.58,8-8s-3.582-8-8-8s-8,3.58-8,8S69.582,42,74,42z M74,30c2.206,0,4,1.795,4,4s-1.794,4-4,4
		c-2.206,0-4-1.795-4-4S71.794,30,74,30z"/>
</g>
</svg>';

  $html = '<div class="welcomebanner"><div class="welcomeimage bee">' . $bee_svg . '</div><div class="welcomebannertext"><h2>';
  $html .= t('Brace yourselves.', [], ['langcode' => $langcode]) . '<br />';
  $html .= t('APIs are coming.', [], ['langcode' => $langcode]) . '</h2><p>';
  $html .= t('Explore, subscribe to and be creative with our APIs.', [], ['langcode' => $langcode]) . '<br />';
  $html .= t('We can\'t wait to see what you come up with!', [], ['langcode' => $langcode]) . '</p><div class="bannerButtonRow">';
  $html .= '<a class="button cta-btns--white__btn" href="product">';
  $html .= t('Explore API Documentation', [], ['langcode' => $langcode]) . '</a></div></div></div>';
  return $html;
}

function get_localized_gsb_content($langcode) {
  $html = '<div class="startguide"><div class="gsstep signup"><div class="placeholder"></div>';
  $html .= t('<h3>Sign up</h3>', [], ['langcode' => $langcode]) . '<p>';
  $html .= t('<a href="@link">Create a new account</a> and get started with our APIs. It\'s free to join.', ['@link' => 'user/register'], ['langcode' => $langcode]);
  $html .= '</div><div class="gsstep explore"><div class="placeholder"></div>';
  $html .= t('<h3>Explore our APIs</h3>', [], ['langcode' => $langcode]) . '<p>';
  $html .= t('Take a look at our <a href="@link">API products</a> and quickly find APIs to construct a fully featured application', ['@link' => 'product'], ['langcode' => $langcode]) . '</p>';
  $html .= '</div><div class="gsstep subscribe"><div class="placeholder"></div>';
  $html .= t('<h3>Create</h3>', [], ['langcode' => $langcode]) . '<p>';
  $html .= t('Subscribe to a plan and create your application to make use of our APIs.', [], ['langcode' => $langcode]) . '</p></div></div>';
  return $html;
}

function get_localized_gethelp_content($langcode) {
  $forward_svg = '<?xml version="1.0" encoding="iso-8859-1"?>
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 32 32" width="24" height="24" style="enable-background:new 0 0 32 32;" xml:space="preserve">
<g>
	<polygon  points="15.293,10.707 19.586,15 8,15 8,17 19.586,17 15.293,21.293 16.707,22.707 23.414,16 16.707,9.293 	"/>
	<path  d="M16,2C8.269,2,2,8.269,2,16s6.269,14,14,14c7.731,0,14-6.269,14-14S23.731,2,16,2z M16,28C9.383,28,4,22.617,4,16
		S9.383,4,16,4c6.617,0,12,5.383,12,12S22.617,28,16,28z"/>
</g>
</svg>';
  $html = '<div class="get_help" ><div class="column col1"><h3>' . t('Getting Help', [], ['langcode' => $langcode]) . '</h3><div>' . t('Be sure to check out these extra help resources.', [], ['langcode' => $langcode]) . '</div></div>';
  $html .= '<div class="column col2"><h4>' . t('Get Started', [], ['langcode' => $langcode]) . '</h4><div>' . t('Get started with our APIs by creating an account and exploring the documentation to find what\'s right for you.', [], ['langcode' => $langcode]) . '</div><div><a href="user/register">'. $forward_svg . t('Create an Account', [], ['langcode' => $langcode]) . '</a></div></div>';
  $html .= '<div class="column col3"><h4>' . t('Forum', [], ['langcode' => $langcode]) . '</h4><div>' . t('Ask a question in the forums or search to forum history to see if it\'s been asked before.', [], ['langcode' => $langcode]) . '</div><div><a href="forum">'. $forward_svg . t('Join the discussion', [], ['langcode' => $langcode]) . '</a></div></div>';
  $html .= '<div class="column col4"><h4>' . t('Contact Us', [], ['langcode' => $langcode]) . '</h4><div>' . t('Can\'t find the answer to your question? Need more help? Have some feedback? Let us know!', [], ['langcode' => $langcode]) . '</div><div><a href="contact">'. $forward_svg . t('Email us', [], ['langcode' => $langcode]) . '</a></div></div></div>';
  return $html;
}

function get_localized_goahead_content($langcode) {
  $html = '<div class="goahead" ><div class="goaheadtext"><h3>' . t('Go ahead! Start building with our APIs today!', [], ['langcode' => $langcode]) . '</h3><a class="button cta-btns--white__btn" href="product">' . t('Explore API documentation', [], ['langcode' => $langcode]) . '</a></div></div>';
  return $html;
}

/*
 * Function to update all the links in the getting started guide from /products to /product
 */
function update_gsg_block() {
  $query = \Drupal::entityQuery('block_content');
  $query->condition('info.value', 'Getting Started [en]');
  $nids = $query->execute();

  if (isset($nids) && !empty($nids)) {
    $blockid = array_shift($nids);
  }
  $block = BlockContent::load($blockid);
  // update english
  $bodyValue = $block->body->value;
  // href="products"
  $updated = str_replace('href="products"','href="product"',$bodyValue);
  $block->set('body',array(
    'value' => $updated,
    'format' => 'full_html',
  ));
  $block->save();

  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    if ($language->getId() === 'en') {
      continue;
    }
    $translation = $block->getTranslation($language->getId());
    $bodyValue = $translation->body->value;
    $updated = str_replace('href="products"','href="product"', $bodyValue);
    $translation->set('body',array(
      'value' => $updated,
      'format' => 'full_html',
    ));
    $translation->save();
  }
}