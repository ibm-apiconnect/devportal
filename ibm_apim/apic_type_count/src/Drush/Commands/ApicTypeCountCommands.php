<?php


namespace Drupal\apic_type_count\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;
use Drupal\apic_type_count\Controller\ApicConfigController;
use Drupal\apic_type_count\Controller\ApicNodeListController;
use Drupal\apic_type_count\Controller\ApicTypeCountController;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\UserSession;
use Drupal\node\NodeInterface;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\Role;
use Throwable;

/**
 * Class ApicTypeCountCommands.
 *
 * @package Drupal\apic_type_count\Drush\Commands
 */
class ApicTypeCountCommands extends DrushCommands {

  /**
   * This will output a list of nodes per node type
   *
   * @return array
   *
   */
  #[CLI\Command(name: 'apic_type_count-totals', aliases: ['nodecount'])]
  #[CLI\Usage(name: 'drush apic_type_count-totals', description: 'Count nodes of each type.')]
  public function drush_apic_type_count_totals(array $options = [ 'format' => 'json' ]): array {
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
   */
  #[CLI\Command(name: 'apic_type_count-users', aliases: ['usercount'])]
  #[CLI\Usage(name: 'drush apic_type_count-users', description: 'Count users of each role.')]
  public function drush_apic_type_count_users(array $options = [ 'format' => 'json' ]): array {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $result_final = [];
    $roles = Role::loadMultiple();
    $results = array_map(fn(RoleInterface $role) => $role->label(), $roles);
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
   */
  #[CLI\Command(name: 'apic_type_count-entities', aliases: ['entitycount'])]
  #[CLI\Usage(name: 'drush apic_type_count-entities', description: 'Count entities of each type.')]
  public function drush_apic_type_count_entities(array $options = [ 'format' => 'json' ]): array {
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
   */
  #[CLI\Command(name: 'apic_type_count-productlist', aliases: ['productlist'])]
  #[CLI\Usage(name: 'drush apic_type_count-productlist', description: 'List products.')]
  public function drush_apic_type_count_productlist(array $options = [ 'format' => 'json' ]): array {
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
   */
  #[CLI\Command(name: 'apic_type_count-apilist', aliases: ['apilist'])]
  #[CLI\Usage(name: 'drush apic_type_count-apilist', description: 'List APIs.')]
  public function drush_apic_type_count_apilist(array $options = [ 'format' => 'json' ]): array {
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
   */
  #[CLI\Command(name: 'apic_type_count-consumerorglist', aliases: ['consumerorglist'])]
  #[CLI\Usage(name: 'drush apic_type_count-consumerorglist', description: 'List Consumer orgs.')]
  public function drush_apic_type_count_consumerorglist(array $options = [ 'format' => 'json' ]): array {
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
   */
  #[CLI\Command(name: 'apic_type_count-applicationlist', aliases: ['applicationlist'])]
  #[CLI\Usage(name: 'drush apic_type_count-applicationlist', description: 'List applications.')]
  public function drush_apic_type_count_applicationlist(array $options = [ 'format' => 'json' ]): array {
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
   */
  #[CLI\Command(name: 'apic_type_count-apiget', aliases: ['apiget'])]
  #[CLI\Usage(name: 'drush apic_type_count-apiget', description: 'Get an api.')]
  public function drush_apic_type_count_apiget($input, array $options = [ 'format' => 'json' ]): ?array {
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
   * @param $input - The API ID or name:version
   *
   * @return string|null
   *
   */
  #[CLI\Command(name: 'apic_type_count-apidocument', aliases: ['apidocument'])]
  #[CLI\Usage(name: 'drush apic_type_count-apidocument', description: 'Get an api document.')]
  public function drush_apic_type_count_apidocument($input, array $options = [ 'format' => 'string' ]): ?string {
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
        $results = ApicNodeListController::getAPI($input, 'document');
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
   */
  #[CLI\Command(name: 'apic_type_count-productget', aliases: ['productget'])]
  #[CLI\Usage(name: 'drush apic_type_count-productget', description: 'Get a product.')]
  public function drush_apic_type_count_productget($input, array $options = [ 'format' => 'json' ]): ?array {
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
   * @param $input - The product ID or name:version
   *
   * @return array|null
   *
   */
  #[CLI\Command(name: 'apic_type_count-productdocument', aliases: ['productdocument'])]
  #[CLI\Usage(name: 'drush apic_type_count-productdocument', description: 'Get a product document.')]
  public function drush_apic_type_count_productdocument($input, array $options = [ 'format' => 'json' ]): ?array {
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
        $results = ApicNodeListController::getProduct($input, 'document');
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
   */
  #[CLI\Command(name: 'apic_type_count-applicationget', aliases: ['applicationget'])]
  #[CLI\Usage(name: 'drush apic_type_count-applicationget', description: 'Get an application.')]
  public function drush_apic_type_count_applicationget($input, array $options = [ 'format' => 'json' ]): ?array {
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
   */
  #[CLI\Command(name: 'apic_type_count-consumerorgget', aliases: ['consumerorgget'])]
  #[CLI\Usage(name: 'drush apic_type_count-consumerorgget', description: 'Get a Consumer org.')]
  public function drush_apic_type_count_consumerorgget($input, array $options = [ 'format' => 'json' ]): ?array {
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
   */
  #[CLI\Command(name: 'apic_type_count-apic_config', aliases: ['apic-config', 'apicconfig'])]
  #[CLI\Usage(name: 'drush apic_type_count-apic_config', description: 'Get a site configuration.')]
  public function drush_apic_type_count_apic_config(array $options = [ 'format' => 'json' ]): ?array {
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
