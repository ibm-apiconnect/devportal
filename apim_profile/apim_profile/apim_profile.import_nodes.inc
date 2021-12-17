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

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
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
  try {
    apim_profile_create_footerlinks();
    apim_profile_create_terms_page();
    apim_profile_create_privacy_page();
    apim_profile_create_faqs();
    apim_profile_create_asyncapi_pages();
  } catch (PluginNotFoundException | PluginException | EntityStorageException $e) {
  }
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_create_terms_page() {
  $node = Node::create([
    'type' => 'page',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'summary' => t('This is the terms of use page', [], ['langcode' => 'en']),
    'title' => t("Terms of use", [], ['langcode' => 'en']),
    'body' => [
      'value' => get_localized_terms_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  $node->enforceIsNew();
  $node->save();
  add_terms_node_translations($node);

  $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
    'path' => '/node/' . $node->id(),
    'alias' => '/tsandcs',
  ]);
  \Drupal::entityTypeManager()->getStorage('path_alias')->save($path_alias);

  $link = MenuLinkContent::create([
    'title' => t('Terms and Conditions', [], ['langcode' => 'en']),
    'description' => t('Terms and Conditions', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/tsandcs', [], ['langcode' => 'en']],
    'menu_name' => 'footer',
    'weight' => 30,
    'langcode' => 'en',
  ]);
  $link->save();
  add_terms_menu_translations($link);
}

/**
 * @param $link
 */
function add_terms_menu_translations($link): void {

  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Terms and Conditions', [], ['langcode' => $lang]),
      'description' => t('Terms and Conditions', [], ['langcode' => $lang]),
      'langcode' => $lang,
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

    if ($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Terms of use', [], ['langcode' => $lang]),
      'body' => [
        'value' => get_localized_terms_content($lang),
        'format' => 'full_html',
      ],
      'summary' => t('This is the terms of use page', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_create_privacy_page() {

  $node = Node::create([
    'type' => 'page',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'summary' => t('This is the privacy policy page', [], ['langcode' => 'en']),
    'title' => t("Privacy Policy", [], ['langcode' => 'en']),
    'body' => [
      'value' => get_localized_privacy_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  $node->enforceIsNew();
  $node->save();
  add_privacy_node_translations($node);

  $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
    'path' => '/node/' . $node->id(),
    'alias' => '/privacy',
  ]);
  \Drupal::entityTypeManager()->getStorage('path_alias')->save($path_alias);

  $link = MenuLinkContent::create([
    'title' => t('Privacy Policy', [], ['langcode' => 'en']),
    'description' => t('Privacy Policy', [], ['langcode' => 'en']),
    'link' => ['uri' => 'internal:/privacy'],
    'menu_name' => 'footer',
    'weight' => 40,
    'langcode' => 'en',
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
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Privacy Policy', [], ['langcode' => $lang]),
      'description' => t('Privacy Policy', [], ['langcode' => $lang]),
      'langcode' => $lang,
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
    if ($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Privacy Policy', [], ['langcode' => $lang]),
      'body' => [
        'value' => get_localized_privacy_content($lang),
        'format' => 'full_html',
      ],
      'summary' => t('This is the privacy policy page', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \Drupal\Component\Plugin\Exception\PluginException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
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
    'langcode' => 'en',
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
    if ($link->hasTranslation($lang)) {
      $link->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Contact', [], ['langcode' => $lang]),
      'description' => t('Contact us', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $link->addTranslation($lang, $translation);
  }
  $link->save();
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_create_faqs() {

  $node = Node::create([
    'type' => 'faq',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'title' => t('Using our APIs', [], ['langcode' => 'en']),
    'faqs' => get_localized_apis_content('en'),
    'langcode' => 'en',
  ]);
  $node->enforceIsNew();
  $node->save();
  add_api_faq_node_translations($node);

  $node = Node::create([
    'type' => 'faq',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'title' => t("Apps", [], ['langcode' => 'en']),
    'faqs' => get_localized_apps_content('en'),
    'langcode' => 'en',
  ]);
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
    if ($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Apps', [], ['langcode' => $lang]),
      'faqs' => get_localized_apps_content($lang),
      'langcode' => $lang,
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
    if ($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Using our APIs', [], ['langcode' => $lang]),
      'faqs' => get_localized_apis_content($lang),
      'langcode' => $lang,
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

function get_localized_apis_content($langcode): array {
  $array = [
    0 => [
      'question' => t('I just want to use an API? What are plans?', [], ['langcode' => $langcode]),
      'answer' => t('<p>A plan is collection of API resources or subsets of resources from one or more APIs. A plan can contain a mixture of operation types from different APIs. A plan can have a common rate limit for all the resources or each resource can have a different rate limit. Rate limits specify how many requests an application is allowed to make during a specified time interval.</p><p>Use this Developer Portal to browse the different plans that are available to you and select a plan that is most suitable for your requirements. Some plans have restricted access that you must request access to use. When you submit your request, the organization is notified, the API administrator assesses your request and they might contact you for more details. Other plans are available to use straight away.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html',
    ],
    1 => [
      'question' => t('How do I see my API usage?', [], ['langcode' => $langcode]),
      'answer' => t('<p>The numbers of requests, for different APIs, that your application has made are shown on your application page.</p><p>Click \'Apps\' in the main menu and then click on your application. In the \'Product Subscriptions\' table you will see all plans your application is subscribed to.</p><p>For each API contained in that plan you can see the usage compared to the rate limit of the plan.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html',
    ],
    2 => [
      'question' => t('How can I test an API?', [], ['langcode' => $langcode]),
      'answer' => t('<p>It is possible to test an API from this Developer Portal.</p><p>When looking at the details of an API you will see a list of the operations contained in the API. This will show the verb and path to use for the operation.</p><p>If you click on the operation you will see more information about it, what parameters it might take, what it returns, what possible return codes it might use and what they mean.</p><p>There is also a \'Try\' button on REST APIs which enables you to try the operation out direct from the Developer Portal.</p><p>If the API requires a client ID or a client secret for identification then you can specify these using your application credentials at the top of the \'Try\' section.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html',
    ],
  ];
  return $array;
}

function get_localized_apps_content($langcode): array {
  $array = [
    0 => [
      'question' => t('How do I register an application?', [], ['langcode' => $langcode]),
      'answer' => t('<p>When you add an application you are provided with an API Key and Secret for the application. You must supply these credentials when you call an API that requires you to authenticate your application.</p><p>To register an application click on Apps in the main menu and then click on the \'Create new app\' link. Once you have provided an application name, description, etc you will be shown your application API Key and Secret.</p><p><span class="important">Make a note of your API Secret because it is only displayed once.</span></p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html',
    ],
    1 => [
      'question' => t('How do I reset my application API Secret?', [], ['langcode' => $langcode]),
      'answer' => t('<p>Your API Secret is stored encrypted so we cannot retrieve the unencrypted version to tell you the value if you forget it.</p><p>You can reset it, which will update the stored value and return the new value to you.</p><p>To do that click \'Apps\' in the main menu, click on the application in question and then you can click the \'Reset\' link in the \'API Secret\' section.</p><p>Your new Secret will be displayed at the top of the page.</p>', [], ['langcode' => $langcode]),
      'answer_format' => 'basic_html',
    ],
  ];
  return $array;
}

/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function apim_profile_create_asyncapi_pages() {

  $node = Node::create([
    'type' => 'page',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'summary' => t('An overview of AsyncAPI', [], ['langcode' => 'en']),
    'title' => t("AsyncAPI", [], ['langcode' => 'en']),
    'body' => [
      'value' => get_localized_asyncapi_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  $node->enforceIsNew();
  $node->save();
  add_asyncapi_node_translations($node);

  $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
    'path' => '/node/' . $node->id(),
    'alias' => '/asyncapi',
  ]);
  \Drupal::entityTypeManager()->getStorage('path_alias')->save($path_alias);

  $node = Node::create([
    'type' => 'page',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'summary' => t('An overview of Kafka', [], ['langcode' => 'en']),
    'title' => t("Kafka", [], ['langcode' => 'en']),
    'body' => [
      'value' => get_localized_kafka_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  $node->enforceIsNew();
  $node->save();
  add_asyncapi_kafka_node_translations($node);

  $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
    'path' => '/node/' . $node->id(),
    'alias' => '/asyncapi-kafka',
  ]);
  \Drupal::entityTypeManager()->getStorage('path_alias')->save($path_alias);

  $node = Node::create([
    'type' => 'page',
    'status' => TRUE,
    'promote' => 0,
    'uid' => 1,
    'revision' => 0,
    'summary' => t('An overview of MQ', [], ['langcode' => 'en']),
    'title' => t("MQ", [], ['langcode' => 'en']),
    'body' => [
      'value' => get_localized_mq_content('en'),
      'format' => 'full_html',
    ],
    'langcode' => 'en',
  ]);
  $node->enforceIsNew();
  $node->save();
  add_asyncapi_mq_node_translations($node);

  $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
    'path' => '/node/' . $node->id(),
    'alias' => '/asyncapi-mq',
  ]);
  \Drupal::entityTypeManager()->getStorage('path_alias')->save($path_alias);
}

function get_localized_asyncapi_content($langcode): string {
  // done using multiple strings as the translation process has a maximum line length
  $html = t('<h2>What is an asynchronous API?</h2>', [], ['langcode' => $langcode]);
  $html .= t('<p>Instead of the immediate request-response mechanism of synchronous APIs, asynchronous APIs are event-based. This means that when your application uses an asynchronous API, it does not need to poll or know when to request data because your application subscribes to a stream of events, and will receive events as they become available.</p>', [], ['langcode' => $langcode]);
  $html .= t('<p>For example, an HTTP-based orders API may expose a \'GET\' operation on the path \'/orders\'. As a consumer of the API, your application would need to make repeated requests to the \'/orders\' path to know when there are any new orders.</p>', [], ['langcode' => $langcode]);
  $html .= t('<p>In comparison, as a consumer of an asynchronous API, your application would subscribe to the orders event stream and receive new order events as they arrive.</p>', [], ['langcode' => $langcode]);
  $html .= t('<h2>What is AsyncAPI?</h2>', [], ['langcode' => $langcode]);
  $html .= t('<p> AsyncAPI is a specification for describing asynchronous APIs, similar to the way OpenAPI is a specification for describing synchronous HTTP APIs.</p>', [], ['langcode' => $langcode]);
  $html .= t('<p>The key difference is that while OpenAPI describes paths and the request and response for an operation, AsyncAPI describes channels that contain events together with the structure of an event.</p>', [], ['langcode' => $langcode]);
  return $html;
}

function get_localized_kafka_content($langcode): string {
  // done using multiple strings as the translation process has a maximum line length
  $html = t('<h2>What is Kafka?</h2>', [], ['langcode' => $langcode]);
  $html .= t('<p>This asynchronous API is implemented using the Apache Kafka protocol. It is a publish-subscribe events platform designed to be fault-tolerant, providing a high-throughput and low-latency platform for handling real-time data feeds.</p>', [], ['langcode' => $langcode]);
  $html .= t('<p>Kafka runs as a cluster of one or more servers (Kafka brokers). The load is balanced across the cluster by distributing it amongst the servers.</p>', [], ['langcode' => $langcode]);
  $html .= t('<h3>Key concepts</h3>', [], ['langcode' => $langcode]);
  $html .= t('<h4>Topic</h4>', [], ['langcode' => $langcode]);
  $html .= t('<p>A stream of messages is stored in categories called topics. Topics are represented as channels in an AsyncAPI document. Each topic comprises one or more partitions. Each partition is an ordered list of messages. The messages on a partition are each given a monotonically increasing number called the offset.</p>', [], ['langcode' => $langcode]);
  $html .= t('<h4>Message/Record</h4>', [], ['langcode' => $langcode]);
  $html .= t('<p>The unit of data in Kafka which comprises two parts: headers and value. Headers are commonly used for data about the message and the value is the body of the message.</p>', [], ['langcode' => $langcode]);
  $html .= t('<h4>Producer</h4>', [], ['langcode' => $langcode]);
  $html .= t('<p>A process that publishes streams of messages to Kafka topics. A producer can publish to one or more topics and can optionally choose the partition that stores the data.</p>', [], ['langcode' => $langcode]);
  $html .= t('<h4>Consumer</h4>', [], ['langcode' => $langcode]);
  $html .= t('<p>A process that consumes messages from Kafka topics and processes the feed of messages. A consumer can consume from one or more topics or partitions.</p>', [], ['langcode' => $langcode]);
  $html .= t('<h4>Consumer group</h4>', [], ['langcode' => $langcode]);
  $html .= t('<p>A named group of one or more consumers that together consume the messages from a set of topics. Each consumer in the group reads messages from specific partitions that it is assigned to. Each partition is assigned to one consumer in the group only. Assignment is controlled by the \'client.id\' and \'group.id\' Consumer properties.</p>', [], ['langcode' => $langcode]);
  $html .= t('<h3>Consuming from this API</h3>', [], ['langcode' => $langcode]);
  $html .= t('<p>You will require a Kafka client to connect and subscribe. Kafka client libraries are available for a variety of programming languages, and the sample code for each channel is a starting point for using a Java Kafka client to consume events using a Kafka Consumer.</p>', [], ['langcode' => $langcode]);
  return $html;
}

function get_localized_mq_content($langcode) {
  $html = t("<p>This is an overview of MQ.</p>", [], ['langcode' => $langcode]);
  return $html;
}

/**
 * @param $node
 */
function add_asyncapi_node_translations($node): void {
  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if ($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('AsyncAPI', [], ['langcode' => $lang]),
      'body' => [
        'value' => get_localized_asyncapi_content($lang),
        'format' => 'full_html',
      ],
      'summary' => t('An overview of AsyncAPI', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

/**
 * @param $node
 */
function add_asyncapi_kafka_node_translations($node): void {
  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if ($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('Kafka', [], ['langcode' => $lang]),
      'body' => [
        'value' => get_localized_kafka_content($lang),
        'format' => 'full_html',
      ],
      'summary' => t('An overview of Kafka', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}

/**
 * @param $node
 */
function add_asyncapi_mq_node_translations($node): void {
  $languages = \Drupal::languageManager()->getLanguages();
  foreach ($languages as $language) {
    $lang = $language->getId();
    if ($lang === 'en') {
      continue;
    }
    if ($node->hasTranslation($lang)) {
      $node->removeTranslation($lang);
    }
    $translation = [
      'title' => t('MQ', [], ['langcode' => $lang]),
      'body' => [
        'value' => get_localized_mq_content($lang),
        'format' => 'full_html',
      ],
      'summary' => t('An overview of MQ', [], ['langcode' => $lang]),
      'langcode' => $lang,
    ];
    $node->addTranslation($lang, $translation);
  }
  $node->save();
}


