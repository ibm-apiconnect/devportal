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

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;

/**
 * @file
 * Content importing functions for APIm sample nodes.
 */

/**
 * Create sample nodes
 */
function apim_profile_create_nodes() {
  apim_profile_create_footerlinks();
  apim_profile_create_terms_page();
  apim_profile_create_privacy_page();
  apim_profile_create_faqs();
}

function apim_profile_create_terms_page() {
  $node = Node::create(array(
    'type' => 'page',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'summary' => t('This is the terms of use page', [], ['langcode' => 'en']),
    'title' => t("Terms of use", [], ['langcode' => 'en']),
    'body' => array(
      'value' => get_localized_terms_content('en'),
      'format' => 'full_html'
    ),
    'langcode' => 'en'
  ));
  $node->enforceIsNew();
  $node->save();
  add_terms_node_translations($node);

  $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
    'source' => '/node/' . $node->id(),
    'alias' => '/tsandcs',
  ]);
  \Drupal::entityTypeManager()->getStorage('path_alias')->save($path_alias);

  $link = MenuLinkContent::create([
    'title' => t('Terms and Conditions', [], ['langcode' => 'en']),
    'description' => t('Terms and Conditions', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/tsandcs', [], ['langcode' => 'en']],
    'menu_name' => 'footer',
    'weight' => 30,
    'langcode' => 'en'
  ]);
  $link->save();
  add_terms_menu_translations($link);
}

/**
 * @param $link
 */
function add_terms_menu_translations( $link): void {

  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Terms and Conditions', [], ['langcode' => $lang]),
      'description' => t('Terms and Conditions', [], ['langcode' => $lang]),
      'langcode' => $lang
    ];
    $link->addTranslation($lang, $translation);
  }
  $link->save();
}

/**
 * @param $node
 */
function add_terms_node_translations($node): void {
  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }

    if($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Terms of use', [], ['langcode' => $lang]),
      'body' => [
        'value' => get_localized_terms_content($lang),
        'format' => 'full_html',
      ],
      'summary' => t('This is the terms of use page', [], ['langcode' => $lang]),
      'langcode' => $lang
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

function apim_profile_create_privacy_page() {

  $node = Node::create(array(
    'type' => 'page',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'summary' => t('This is the privacy policy page', [], ['langcode' => 'en']),
    'title' => t("Privacy Policy", [], ['langcode' => 'en']),
    'body' => array(
      'value' => get_localized_privacy_content('en'),
      'format' => 'full_html'
    ),
    'langcode' => 'en'
  ));
  $node->enforceIsNew();
  $node->save();
  add_privacy_node_translations($node);

  $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
    'source' => '/node/' . $node->id(),
    'alias' => '/privacy',
  ]);
  \Drupal::entityTypeManager()->getStorage('path_alias')->save($path_alias);

  $link = MenuLinkContent::create([
    'title' => t('Privacy Policy', [], ['langcode' => 'en']),
    'description' => t('Privacy Policy', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/privacy'],
    'menu_name' => 'footer',
    'weight' => 40,
    'langcode' => 'en'
  ]);
  $link->save();
  add_privacy_menu_translations($link);
}

/**
 * @param $link
 */
function add_privacy_menu_translations($link): void {

  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Privacy Policy', [], ['langcode' => $lang]),
      'description' => t('Privacy Policy', [], ['langcode' => $lang]),
      'langcode' => $lang
    ];
    $link->addTranslation($lang, $translation);
  }
  $link->save();
}

/**
 * @param $node
 */
function add_privacy_node_translations($node): void {
  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Privacy Policy', [], ['langcode' => $lang]),
      'body' => [
        'value' => get_localized_privacy_content($lang),
        'format' => 'full_html',
      ],
      'summary' => t('This is the privacy policy page', [], ['langcode' => $lang]),
      'langcode' => $lang
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

function apim_profile_create_footerlinks() {
  // Disable the Contact link from the contact module.
  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
  $contact_link = $menu_link_manager->getDefinition('contact.site_page');
  $contact_link['enabled'] = FALSE;
  $menu_link_manager->updateDefinition('contact.site_page', $contact_link);

  $link = MenuLinkContent::create([
    'title' => t('Contact', [], ['langcode' => 'en']),
    'description' => t('Contact us', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/contact'],
    'menu_name' => 'footer',
    'weight' => 0,
    'langcode' => 'en'
  ]);
  $link->save();
  add_contact_menu_translations($link);
}

/**
 * @param $link
 */
function add_contact_menu_translations($link): void {
  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Contact', [], ['langcode' => $lang]),
      'description' => t('Contact us', [], ['langcode' => $lang]),
      'langcode' => $lang
    ];
    $link->addTranslation($lang, $translation);
  }
  $link->save();
}

function apim_profile_create_faqs() {

  $node = Node::create(array(
    'type' => 'faq',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'title' => t('Using our APIs', [], ['langcode' => 'en']),
    'faqs' => get_localized_apis_content('en'),
    'langcode' => 'en'
  ));
  $node->enforceIsNew();
  $node->save();
  add_api_faq_node_translations($node);

  $node = Node::create(array(
    'type' => 'faq',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'title' => t("Apps", [], ['langcode' => 'en']),
    'faqs' => get_localized_apps_content('en'),
    'langcode' => 'en'
  ));
  $node->enforceIsNew();
  $node->save();
  add_app_faq_node_translations($node);
}

/**
 * @param $node
 */
function add_app_faq_node_translations($node): void {

  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Apps', [], ['langcode' => $lang]),
      'faqs' => get_localized_apps_content($lang),
      'langcode' => $lang
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

/**
 * @param $node
 */
function add_api_faq_node_translations($node): void {
  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Using our APIs', [], ['langcode' => $lang]),
      'faqs' => get_localized_apis_content($lang),
      'langcode' => $lang
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

function get_localized_terms_content($langcode) {
  $html = t("<p>The terms of service that are outlined here are a legal agreement between you and our company and by using this website you agree to these terms of service. Take time to read and understand what the terms are. If you do not agree to these terms, do not use this website. Without notification, the terms can be changed at any time. The content of this website might also change.</p>", [], ['langcode' => $langcode]);
  return $html;
}

function get_localized_privacy_content($langcode) {
  $html = t("<p>We respect your privacy and we want to maintain your trust. This website may use session and persistent cookies to collect the user session ID for session management, or for functional purposes. You can disable these cookies, but if you disable the cookies you also remove the function they provide. </p>", [], ['langcode' => $langcode]);
  return $html;
}

function get_localized_apis_content($langcode) {
  $array = array(
    0 => array(
      'question' => t('I just want to use an API? What are plans?', [], ['langcode' => $langcode]),
      'answer' => t('<p>A plan is collection of API resources or subsets of resources from one or more API. A plan can contain a mixture of HTTP GET, PUT, POST, and DELETE verbs from different APIs or it can contain all the GET verbs from various APIs. A plan can have a common rate limit for all the resources or each resource can have a different rate limit. Rate limits specify how many requests an application is allowed to make during a specified time interval.</p><p>Use this Developer Portal to browse the different plans that are available to you and select a plan that is most suitable for your requirements. Some plans have restricted access that you must request access to use. When you submit your request, the organization is notified, the API administrator assesses your request and they might contact you for more details. Other plans are available to use straight away.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html'
    ),
    1 => array(
      'question' => t('How do I see my API usage?', [], ['langcode' => $langcode]),
      'answer' => t('<p>The numbers of requests, for different APIs, that your application has made are shown on your application page.</p><p>Click \'Apps\' in the main menu and then click on your application. Under \'Subscribed Plans\' you will see all plans your application is subscribed to.</p><p>For each API contained in that plan you can see the usage compared to the rate limit of the plan.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html'
    ),
    2 => array(
      'question' => t('How can I test an API?', [], ['langcode' => $langcode]),
      'answer' => t('<p>It is possible to test an API from this Developer Portal.</p><p>When looking at the details of an API you will see a table of the operations contained in the API. This will show what method they are (GET, POST, PUT, DELETE, PATCH, HEAD or OPTIONS) and what path the Resource uses.</p><p>If you click on the Resource you will see more information about it, what parameters it might take, what it returns, what possible return codes it might use and what they mean.</p><p>There is also a \'Try\' button which enables you to try the Resource out direct from the Developer Portal.</p><p>If the API requires a client ID or a client secret for identification then you can specify these at the top of the \'Try\' section.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html'
    ),
  );
  return $array;
}

function get_localized_apps_content($langcode) {
  $array = array(
    0 => array(
      'question' => t('How do I register an application?', [], ['langcode' => $langcode]),
      'answer' => t('<p>When you add an application you are provided with a client ID and client secret for the application. You must supply the client ID when you call an API that requires you to identify your application by using a client ID, or a client ID and client secret.</p><p>To register an application click on Apps in the main menu and then click on the \'Create new app\' link. Once you have provided an application name, description, etc you will be shown your application client ID and client secret.</p><p><span class="important">Make a note of your client secret because it is only displayed once.</span> You must supply the client secret when you call an API that requires you to identify your application by using a Client ID and Client secret.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html'
    ),
    1 => array(
      'question' => t('How do I reset my application client secret?', [], ['langcode' => $langcode]),
      'answer' => t('<p>Your application client secret is stored encrypted so we cannot retrieve the unencrypted version to tell you the value if you forget it.</p><p>You can reset it, which will update the stored value and return the new value to you.</p><p>To do that click \'Apps\' in the main menu, click on the application in question and then you can click the \'Reset\' link in the \'Client Secret\' section.</p><p>Your new secret will be displayed at the top of the page.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html'
    ),
  );
  return $array;
}
