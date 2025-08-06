<?php

namespace Drupal\apic_app\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Cache\Cache;
use Drupal\apic_app\Service\ApplicationService;
/**
 * @ViewsRow(
 *   id = "app_subscription_row",
 *   title = @Translation("Subscription Row"),
 *   help = @Translation("Displays custom subscription layout."),
 *  
 *   display_types = {"normal", "feed", "attachment", "embed", "entity_reference"} 
 * )
 */
class SubscriptionCustomRow extends RowPluginBase
{
  /**
   * @var \Drupal\apic_app\Service\ApplicationService
   */
  protected ApplicationService $applicationService;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ApplicationService $applicationService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->applicationService = $applicationService;
  }

  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('apic_app.application')
    );
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\views\ResultRow $row
   */
  public function render($row)
  {
    $uuid = $row->{'apic_app_application_subs_uuid'};
    $app_url = $row->{'apic_app_application_subs_app_url'};
    $product_url = $row->{'apic_app_application_subs_product_url'};
    $plan = $row->{'apic_app_application_subs_plan'};
    $state = $row->{'apic_app_application_subs_state'};
    $config = \Drupal::config('ibm_apim.settings');
    $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
    $subArray=$this->applicationService->processSubscription($product_url, $plan, $state, $uuid, $ibmApimShowPlaceholderImages);
    return $subArray;
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
}
