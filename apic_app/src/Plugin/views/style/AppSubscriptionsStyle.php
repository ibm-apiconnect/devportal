<?php

namespace Drupal\apic_app\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use \Drupal\views\ResultRow;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
/**
 * Style plugin to render application subscriptions as a table.
 *
 * @ViewsStyle(
 *   id = "app_subscriptions_style",
 *   title = @Translation("Application Subscriptions Table"),
 *   help = @Translation("Wraps rows in a table structure."),
 *   theme = "app_subscriptions",
 *   display_types = {"normal", "feed", "attachment", "embed", "entity_reference"}
 * )
 */
class AppSubscriptionsStyle extends StylePluginBase
{
  protected RowPluginBase $row_plugin;
  public function query()
  {
    // This ensures the SELECT clause includes the primary key
    $this->view->query->addField('apic_app_application_subs', 'id');
    $this->view->query->addField('apic_app_application_subs', 'uuid');
    $this->view->query->addField('apic_app_application_subs', 'app_url');
    $this->view->query->addField('apic_app_application_subs', 'plan');
    $this->view->query->addField('apic_app_application_subs', 'product_url');
    $this->view->query->addField('apic_app_application_subs', 'state');
  }
  public function getCacheTags()
  {
    // Add any relevant entity or view-based tags.
    return ['apic_app_application_subs_list'];
  }

  public function getCacheMaxAge()
  {
    // You want this to be permanent (or finite) to allow invalidation via tags.
    return Cache::PERMANENT;
  }
  /**
   * {@inheritdoc}
   */
  public function render()
  {

    $userUtils = \Drupal::service('ibm_apim.user_utils');


    // Permissions
    $userHasAppManage = $userUtils->checkHasPermission('app:manage');
    $userHasSubView = $userUtils->checkHasPermission('subscription:view');
    $userHasSubManage = $userUtils->checkHasPermission('subscription:manage');

    // Config/state
    $ibmApimShowVersions = \Drupal::config('ibm_apim.settings')->get('show_versions') ?? TRUE;
    $billingEnabled = (bool) \Drupal::state()->get('ibm_apim.billing_enabled');
    $node = \Drupal::routeMatch()->getParameter('node');

    $rows = [];

    if ($this->usesRowPlugin()) {
      $row_plugin = $this->displayHandler->getPlugin('row');
      if ($row_plugin) {
        foreach ($this->view->result as $row_index => $row) {
          $rendered_row = $row_plugin->render($row);
          // Only add non-empty rows (skip subscriptions for retired/deleted products)
          if (!empty($rendered_row)) {
            $rows[] = $rendered_row;
          }
        }
      }
    }
    $nodeArray = [
      'application_id' => ['value' => $node->application_id->value],
      'subscriptions' => $rows,
      'id' => $node->id(),
    ];
    $build = [
      '#theme' => 'app_subscriptions',
      '#userHasAppManage' => $userHasAppManage,
      '#userHasSubView' => $userHasSubView,
      '#userHasSubManage' => $userHasSubManage,
      '#showVersions' => $ibmApimShowVersions,
      '#billing_enabled' => $billingEnabled,
      '#node' => $nodeArray,
      '#attached' => [
        'library' => ['apic_app/basic'],
      ],
    ];
    return $build;
  }
  /**
   * {@inheritdoc}
   */
  public function usesRowPlugin()
  {
    return TRUE; 
  }
}
