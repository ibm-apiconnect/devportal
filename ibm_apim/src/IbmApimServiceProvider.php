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

namespace Drupal\ibm_apim;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class IbmApimServiceProvider
 * See https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection/altering-existing-services-providing-dynamic
 * for how this class is loaded
 *
 * @package Drupal\ibm_apim
 */
class IbmApimServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides session_manager class to do Strict session cookies if not using OIDC.
    $definition = $container->getDefinition('session_manager');
    $definition->setClass(\Drupal\ibm_apim\Session\APICSessionManager::class)->addArgument(new Reference('ibm_apim.user_registry'));

    // Overrides entity.autocomplete_matcher class to allow custom labels to be returned
    $autoCompleteDefinition = $container->getDefinition('entity.autocomplete_matcher');
    $autoCompleteDefinition->setClass('Drupal\ibm_apim\EntityAutocompleteMatcher');
  }
}