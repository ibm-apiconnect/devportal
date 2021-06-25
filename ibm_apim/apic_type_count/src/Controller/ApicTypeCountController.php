<?php

/**
 * @file
 * Contains \Drupal\apic_type_count\Controller\ApicTypeCountController.
 */

namespace Drupal\apic_type_count\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Controller routines for page example routes.
 */
class ApicTypeCountController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'admin/reports/apic-type-count'.
   *
   * @return array
   */
  public function nodeTypeCountPublished(): array {
    // We are going to output the results in a table with a nice header.
    $header = [
      t('Title'),
      t('Type'),
      t('Published'),
      t('UnPublished'),
    ];
    $result_final = [];

    $result = node_type_get_names();
    if (is_array($result)) {
      foreach ($result as $node_type_machine_name => $content_type_title) {
        // Get the value as key and value pair.
        $result_arr['title'] = Html::escape($content_type_title);
        $result_arr['machine_name'] = $node_type_machine_name;
        $result_arr['published'] = self::nodeCountState(NodeInterface::PUBLISHED, $node_type_machine_name);
        $result_arr['unpublished'] = self::nodeCountState(NodeInterface::NOT_PUBLISHED, $node_type_machine_name);
        $result_final[$node_type_machine_name] = $result_arr;
      }
    }
    $rows = [];
    foreach ($result_final as $row) {
      // Normally we would add some nice formatting to our rows
      // but for our purpose we are simply going to add our row
      // to the array.
      $rows[] = ['data' => (array) $row];
    }
    // Build the table for the nice output.
    $build = [
      '#markup' => '<p>' . t('The layout here is a themed as a table
           that is sortable by clicking the header name.') . '</p>',
    ];
    $build['tablesort_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;

  }

  /**
   * This code (noted in the callback above) creates the.
   *
   * Contents of the page for User count.
   *
   * @return array
   */
  public function userRoleCount(): array {
    // We are going to output the results in a table with a nice header.
    $header = [
      t('Role Name'),
      t('Role Machine Name'),
      t('Number of Users'),
    ];
    $result_final = [];
    $results = user_role_names();
    if (is_array($results)) {
      foreach ($results as $user_role_machine_name => $content_type_title) {
        // Get the value as key and value pair.
        $result_arr['title'] = Html::escape($content_type_title);
        $result_arr['machine_name'] = $user_role_machine_name;
        $result_arr['count'] = self::userCountByRole($user_role_machine_name);
        $result_final[$user_role_machine_name] = $result_arr;
      }
    }
    $rows = [];
    foreach ($result_final as $row) {
      // Normally we would add some nice formatting to our rows
      // but for our purpose we are simply going to add our row
      // to the array.
      $rows[] = ['data' => (array) $row];
    }
    // Build the table for the nice output.
    $build = [
      '#markup' => '<p>' . t('The layout here is a themed as a table
           that is sortable by clicking the header name.') . '</p>',
    ];
    $build['tablesort_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'admin/reports/apic-type-count/entities'.
   *
   * @return array
   */
  public function entityRoleCount(): array {
    // We are going to output the results in a table with a nice header.
    $header = [
      t('Title'),
      t('Type'),
      t('Total'),
    ];
    $result_final = [];

    $types = self::getEntityTypes();
    if (is_array($types)) {
      foreach ($types as $type_machine_name => $type_title) {
        // Get the value as key and value pair.
        $result_arr['title'] = Html::escape($type_title);
        $result_arr['machine_name'] = $type_machine_name;
        $result_arr['total'] = self::entityCountState($type_machine_name);
        $result_final[$type_machine_name] = $result_arr;
      }
    }
    $rows = [];
    foreach ($result_final as $row) {
      // Normally we would add some nice formatting to our rows
      // but for our purpose we are simply going to add our row
      // to the array.
      $rows[] = ['data' => (array) $row];
    }
    // Build the table for the nice output.
    $build = [
      '#markup' => '<p>' . t('The layout here is a themed as a table
           that is sortable by clicking the header name.') . '</p>',
    ];
    $build['tablesort_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;

  }

  /**
   * This is the helper function for nodeCountState() to get the count.
   *
   * Of the published or unpublished content of particular content type.
   *
   * @param $status
   * @param $type
   *
   * @return array|int
   */
  public static function nodeCountState($status, $type) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', $status)
      ->condition('type', $type);
    return $query->count()->execute();
  }

  /**
   * Count User Role.
   *
   * @param $role_type_machine_name
   *
   * @return int|void
   */
  public static function userCountByRole($role_type_machine_name) {
    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');

    $query = $user_storage->getQuery();
    $query->condition('uid', 0, '<>');
    if ($role_type_machine_name !== 'authenticated') {
      $query->condition('roles', $role_type_machine_name);
    }
    $results = $query->execute();
    return count($results);
  }

  /**
   * This is the helper function for nodeTypeCountPublished() to get the count.
   *
   * @param string $type
   *
   * @return array|int
   */
  public static function entityCountState($type) {
    $result = NULL;
    if ($type !== NULL) {
      $query = \Drupal::entityQuery($type);
      $result = $query->count()->execute();
    }
    return $result;
  }

  /**
   * Hardcoded list of entities to output information for
   *
   * @return array
   */
  public static function getEntityTypes(): array {
    return ['apic_app_application_subs' => 'Subscriptions', 'apic_app_application_creds' => 'Credentials', 'consumerorg_payment_method' => 'Payment Methods', 'event_log' => 'Event Logs'];
  }

}
