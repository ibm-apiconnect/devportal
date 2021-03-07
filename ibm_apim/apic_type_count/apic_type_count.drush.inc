<?php

use Drupal\Component\Utility\Html;
use Drupal\Core\Session\UserSession;
use Drupal\node\NodeInterface;
use Drupal\apic_type_count\Controller\ApicConfigController;
use Drupal\apic_type_count\Controller\ApicTypeCountController;
use Drupal\apic_type_count\Controller\ApicNodeListController;

/**
 * Implements hook_drush_command
 *
 * @return mixed
 */
function apic_type_count_drush_command() {

  $items['apic_type_count-totals'] = [
    'description' => 'Count nodes of each type',
    'aliases' => ['nodecount'],
    'outputformat' => [
      'default' => 'table',
    ],
  ];
  $items['apic_type_count-users'] = [
    'description' => 'Count users of each role',
    'aliases' => ['usercount'],
    'outputformat' => [
      'default' => 'table',
    ],
  ];
  $items['apic_type_count-entities'] = [
    'description' => 'Count entities of each type',
    'aliases' => ['entitycount'],
    'outputformat' => [
      'default' => 'table',
    ],
  ];
  $items['apic_type_count-productlist'] = [
    'description' => 'List products',
    'aliases' => ['productlist'],
    'outputformat' => [
      'default' => 'table',
    ],
  ];
  $items['apic_type_count-apilist'] = [
    'description' => 'List APIs',
    'aliases' => ['apilist'],
    'outputformat' => [
      'default' => 'table',
    ],
  ];
  $items['apic_type_count-consumerorglist'] = [
    'description' => 'List Consumer orgs',
    'aliases' => ['consumerorglist'],
    'outputformat' => [
      'default' => 'table',
    ],
  ];
  $items['apic_type_count-applicationlist'] = [
    'description' => 'List Applications',
    'aliases' => ['applicationlist'],
    'outputformat' => [
      'default' => 'table',
    ],
  ];
  $items['apic_type_count-apiget'] = [
    'description' => 'Get an API',
    'aliases' => ['apiget'],
    'arguments' => [
      'input' => 'The API ID or name:version',
    ],
    'outputformat' => [
      'default' => 'json',
    ],
  ];
  $items['apic_type_count-productget'] = [
    'description' => 'Get a product',
    'aliases' => ['productget'],
    'arguments' => [
      'input' => 'The product ID or name:version',
    ],
    'outputformat' => [
      'default' => 'json',
    ],
  ];
  $items['apic_type_count-applicationget'] = [
    'description' => 'Get an application',
    'aliases' => ['applicationget'],
    'arguments' => [
      'input' => 'The application ID',
    ],
    'outputformat' => [
      'default' => 'json',
    ],
  ];
  $items['apic_type_count-consumerorgget'] = [
    'description' => 'Get a consumer organization',
    'aliases' => ['consumerorgget'],
    'arguments' => [
      'input' => 'The consumer organization ID',
    ],
    'outputformat' => [
      'default' => 'json',
    ],
  ];
  $items['apic_type_count-apic_config'] = [
    'description' => 'Get site configuration',
    'aliases' => ['apic-config', 'apicconfig'],
    'outputformat' => [
      'default' => 'json',
    ],
  ];
  return $items;
}

/**
 * This will output a list of nodes per node type
 *
 * @return array
 */
function drush_apic_type_count_totals() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  $result_final = [];
  $result = node_type_get_names();
  if (is_array($result)) {
    foreach ($result as $node_type_machine_name => $content_type_title) {
      // Get the value as key and value pair.
      $result_arr['title'] = Html::escape($content_type_title);
      $result_arr['machine_name'] = $node_type_machine_name;
      $result_arr['published'] = ApicTypeCountController::nodeCountState(NodeInterface::PUBLISHED, $node_type_machine_name);
      $result_arr['unpublished'] = ApicTypeCountController::nodeCountState(NodeInterface::NOT_PUBLISHED, $node_type_machine_name);
      $result_final[$node_type_machine_name] = $result_arr;
    }
  }
  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}

/**
 * This will output a list of users per role
 *
 * @return array
 */
function drush_apic_type_count_users() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  $result_final = [];
  $results = user_role_names();
  if (is_array($results)) {
    foreach ($results as $user_role_machine_name => $content_type_title) {
      // Get the value as key and value pair.
      $result_arr['title'] = Html::escape($content_type_title);
      $result_arr['machine_name'] = $user_role_machine_name;
      $result_arr['count'] = ApicTypeCountController::userCountByRole($user_role_machine_name);
      $result_final[$user_role_machine_name] = $result_arr;
    }
  }

  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}

/**
 * This will output a list of entities per entity type
 *
 * @return array
 */
function drush_apic_type_count_entities() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  $result_final = [];
  $types = ApicTypeCountController::getEntityTypes();
  if (is_array($types)) {
    foreach ($types as $type_machine_name => $type_title) {
      // Get the value as key and value pair.
      $result_arr['title'] = Html::escape($type_title);
      $result_arr['machine_name'] = $type_machine_name;
      $result_arr['total'] = ApicTypeCountController::entityCountState($type_machine_name);
      $result_final[$type_machine_name] = $result_arr;
    }
  }
  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}

/**
 * @return array
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function drush_apic_type_count_productlist() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  $results = ApicNodeListController::getNodesForType('product');
  if (is_array($results)) {
    foreach ($results as $result) {
      $result_final[$result['ref']] = $result;
    }
  }

  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}

/**
 * @return array
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function drush_apic_type_count_apilist() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  $results = ApicNodeListController::getNodesForType('api');
  if (is_array($results)) {
    foreach ($results as $result) {
      $result_final[$result['ref']] = $result;
    }
  }

  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}

/**
 * @return array
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function drush_apic_type_count_consumerorglist() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  $results = ApicNodeListController::getNodesForType('consumerorg');
  if (is_array($results)) {
    foreach ($results as $result) {
      $result_final[$result['name']] = $result;
    }
  }

  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}

/**
 * @return array
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function drush_apic_type_count_applicationlist() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  $results = ApicNodeListController::getNodesForType('application');
  if (is_array($results)) {
    foreach ($results as $result) {
      $result_final[$result['name']] = $result;
    }
  }

  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}

function drush_apic_type_count_apiget($input) {
  $results = NULL;
  if ($input !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    try {
      $results = ApicNodeListController::getAPI($input);
    } catch (\Exception $e) {
      \Drupal::logger("apic_type_count")
        ->error(dt("An exception occurred. That may mean the api does not exist or you do not have access to it."));
    }

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    \Drupal::logger("apic_type_count")
      ->error(dt("No input provided."));
  }
  return $results;
}

function drush_apic_type_count_productget($input) {
  $results = NULL;
  if ($input !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    try {
      $results = ApicNodeListController::getProduct($input);
    } catch (\Exception $e) {
      \Drupal::logger("apic_type_count")
        ->error(dt("An exception occurred. That may mean the product does not exist or you do not have access to it."));
    }

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    \Drupal::logger("apic_type_count")
      ->error(dt("No input provided."));
  }
  return $results;
}

function drush_apic_type_count_applicationget($input) {
  $results = NULL;
  if ($input !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    try {
      $results = ApicNodeListController::getApplication($input);
    } catch (\Exception $e) {
      \Drupal::logger("apic_type_count")
        ->error(dt("An exception occurred. That may mean the application does not exist or you do not have access to it."));
    }

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    \Drupal::logger("apic_type_count")
      ->error(dt("No input provided."));
  }
  return $results;
}

function drush_apic_type_count_consumerorgget($input) {
  $results = NULL;
  if ($input !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    try {
      $results = ApicNodeListController::getConsumerorg($input);
    } catch (\Exception $e) {
      \Drupal::logger("apic_type_count")
        ->error(dt("An exception occurred. That may mean the consumer organization does not exist or you do not have access to it."));
    }

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }

  }
  else {
    \Drupal::logger("apic_type_count")
      ->error(dt("No input provided."));
  }
  return $results;
}

function drush_apic_type_count_apic_config() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  try {
    $results = ApicConfigController::getConfig();
  } catch (\Exception $e) {
    \Drupal::logger("apic_type_count")->error(dt("An exception occurred."));
  }


  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $results;
}