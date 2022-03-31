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

namespace Drupal\apic_type_count\Commands;

use Drush\Commands\DrushCommands;
use Drupal\apic_type_count\Controller\ApicConfigController;
use Drupal\apic_type_count\Controller\ApicNodeListController;
use Drupal\apic_type_count\Controller\ApicTypeCountController;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\UserSession;
use Drupal\node\NodeInterface;
use Throwable;

/**
 * Class ApicTypeCountCommands.
 *
 * @package Drupal\apic_type_count\Commands
 */
class ApicTypeCountCommands extends DrushCommands {

  /**
   * This will output a list of nodes per node type
   *
   * @return array
   *
   * @command apic_type_count-totals
   * @usage drush apic_type_count-totals
   *   Count nodes of each type.
   * @aliases nodecount
   * @format table
   */
  public function drush_apic_type_count_totals(): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
   *
   * @command apic_type_count-users
   * @usage drush apic_type_count-users
   *   Count users of each role.
   * @aliases usercount
   * @format table
   */
  public function drush_apic_type_count_users(): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
   *
   * @command apic_type_count-entities
   * @usage drush apic_type_count-entities
   *   Count entities of each type.
   * @aliases entitycount
   * @format table
   */
  public function drush_apic_type_count_entities(): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
   *
   * @command apic_type_count-prodictlist
   * @usage drush apic_type_count-productlist
   *   List products.
   * @aliases productlist
   * @format table
   */
  public function drush_apic_type_count_productlist(): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $result_final = [];

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
   *
   * @command apic_type_count-apilist
   * @usage drush apic_type_count-apilist
   *   List APIs.
   * @aliases apilist
   * @format table
   */
  public function drush_apic_type_count_apilist(): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $result_final = [];

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
   *
   * @command apic_type_count-consumerorglist
   * @usage drush apic_type_count-consumerorglist
   *   List Consumer orgs.
   * @aliases consumerorglist
   * @format table
   */
  public function drush_apic_type_count_consumerorglist(): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $result_final = [];

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
   *
   * @command apic_type_count-applicationlist
   * @usage drush apic_type_count-applicationlist
   *   List applications.
   * @aliases applicationlist
   * @format table
   */
  public function drush_apic_type_count_applicationlist(): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $result_final = [];

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

  /**
   * @param $input - The API ID or name:version
   *
   * @return array|null
   *
   * @command apic_type_count-apiget
   * @usage drush apic_type_count-apiget
   *   Get an api.
   * @aliases apiget
   * @format json
   */
  public function drush_apic_type_count_apiget($input): ?array {
    $results = NULL;
    if ($input !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }

      try {
        $results = ApicNodeListController::getAPI($input);
      } catch (Throwable $e) {
        \Drupal::logger("apic_type_count")
          ->error("An exception occurred. That may mean the api does not exist or you do not have access to it.");
      }

      if (isset($originalUser) && (int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger("apic_type_count")->error("No input provided.");
    }
    return $results;
  }

  /**
   * @param $input - The product ID or name:version
   *
   * @return array|null
   *
   * @command apic_type_count-productget
   * @usage drush apic_type_count-productget
   *   Get an product.
   * @aliases productget
   * @format json
   */
  public function drush_apic_type_count_productget($input): ?array {
    $results = NULL;
    if ($input !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      try {
        $results = ApicNodeListController::getProduct($input);
      } catch (Throwable $e) {
        \Drupal::logger("apic_type_count")
          ->error("An exception occurred. That may mean the product does not exist or you do not have access to it.");
      }

      if (isset($originalUser) && (int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger("apic_type_count")->error("No input provided.");
    }
    return $results;
  }

  /**
   * @param $input - The application ID
   *
   * @return array|null
   *
   * @command apic_type_count-applicationget
   * @usage drush apic_type_count-applicationget
   *   Get an application.
   * @aliases applicationget
   * @format json
   */
  public function drush_apic_type_count_applicationget($input): ?array {
    $results = NULL;
    if ($input !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }

      try {
        $results = ApicNodeListController::getApplication($input);
      } catch (Throwable $e) {
        \Drupal::logger("apic_type_count")
          ->error("An exception occurred. That may mean the application does not exist or you do not have access to it.");
      }

      if (isset($originalUser) && (int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger("apic_type_count")->error("No input provided.");
    }
    return $results;
  }

  /**
   * @param $input - The consumer organization ID
   *
   * @return array|null
   *
   * @command apic_type_count-consumerorgget
   * @usage drush apic_type_count-consumerorgget
   *   Get a Consumer org.
   * @aliases consumerorgget
   * @format json
   */
  public function drush_apic_type_count_consumerorgget($input): ?array {
    $results = NULL;
    if ($input !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }

      try {
        $results = ApicNodeListController::getConsumerorg($input);
      } catch (Throwable $e) {
        \Drupal::logger("apic_type_count")
          ->error("An exception occurred. That may mean the consumer organization does not exist or you do not have access to it.");
      }

      if (isset($originalUser) && (int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger("apic_type_count")->error("No input provided.");
    }
    return $results;
  }

  /**
   * @return array|null
   *
   * @command apic_type_count-apic_config
   * @usage drush apic_type_count-apic_config
   *   Get a site configuration.
   * @aliases apic-config apicconfig
   * @format json
   */
  public function drush_apic_type_count_apic_config(): ?array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $results = [];

    try {
      $results = ApicConfigController::getConfig();
    } catch (Throwable $e) {
      \Drupal::logger("apic_type_count")->error("An exception occurred.");
    }

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    return $results;
  }

}