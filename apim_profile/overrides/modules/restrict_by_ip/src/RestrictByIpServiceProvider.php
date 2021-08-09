<?php

/**
 * @file
 * Contains Drupal\restrict_by_ip\RestrictByIpServiceProvider
 */

namespace Drupal\restrict_by_ip;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Rename current_user service and replace with one provided by restrict_by_ip
 * module.
 */
class RestrictByIpServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Rename current_user service.
    $coreService = $container->getDefinition('current_user');
    $container->setDefinition('restrict_by_ip.current_user', $coreService);

    // Register this modules class as default for current_user service.
    $newService = new Definition('Drupal\restrict_by_ip\Session\AccountProxy');
    $newService->addArgument(new Reference('restrict_by_ip.current_user'));
    $newService->addArgument(new Reference('restrict_by_ip.role_firewall'));
    $container->setDefinition('current_user', $newService);
  }
}