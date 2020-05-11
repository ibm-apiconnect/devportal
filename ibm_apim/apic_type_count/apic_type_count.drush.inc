<?php

use Drupal\Component\Utility\Html;
use Drupal\Core\Session\UserSession;
use Drupal\node\NodeInterface;
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
    'outputformat' => array(
      'default' => 'table'
    ),
  ];
  $items['apic_type_count-users'] = [
    'description' => 'Count users of each role',
    'aliases' => ['usercount'],
    'outputformat' => array(
      'default' => 'table'
    ),
  ];
  $items['apic_type_count-entities'] = [
    'description' => 'Count entities of each type',
    'aliases' => ['entitycount'],
    'outputformat' => array(
      'default' => 'table'
    ),
  ];
  $items['apic_type_count-productlist'] = [
    'description' => 'List products',
    'aliases' => ['productlist'],
    'outputformat' => array(
      'default' => 'table'
    ),
  ];
  $items['apic_type_count-apilist'] = [
    'description' => 'List APIs',
    'aliases' => ['apilist'],
    'outputformat' => array(
      'default' => 'table'
    ),
  ];
  $items['apic_type_count-consumerorglist'] = [
    'description' => 'List Consumer orgs',
    'aliases' => ['consumerorglist'],
    'outputformat' => array(
      'default' => 'table'
    ),
  ];
  $items['apic_type_count-applicationlist'] = [
    'description' => 'List Applications',
    'aliases' => ['applicationlist'],
    'outputformat' => array(
      'default' => 'table'
    ),
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