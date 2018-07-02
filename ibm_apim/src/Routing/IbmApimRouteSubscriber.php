<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class IbmApimRouteSubscriber extends RouteSubscriberBase {
  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('node.add_page')) {
      $route->setDefaults(array(
        '_title' => 'Add content',
        '_controller' => '\Drupal\ibm_apim\Controller\IbmApimNodeController::addPage'
      ));
    }
    if ($route = $collection->get('system.themes_page')) {
      // Override the themes list controller with an extended version of it
      $route->setDefault(
        '_controller',
        'Drupal\ibm_apim\Controller\IbmApimThemeController::themesPage'
      );
    }
    if ($route = $collection->get('system.theme_uninstall')) {
      // Override the themes uninstall controller with an extended version of it
      $route->setDefault(
        '_controller',
        'Drupal\ibm_apim\Controller\IbmApimThemeInstallController::uninstall'
      );
    }
    if ($route = $collection->get('system.theme_install')) {
      // Override the themes install controller with an extended version of it
      $route->setDefault(
        '_controller',
        'Drupal\ibm_apim\Controller\IbmApimThemeInstallController::install'
      );
    }
    if ($route = $collection->get('system.theme_set_default')) {
      // Override the themes install controller with an extended version of it
      $route->setDefault(
        '_controller',
        'Drupal\ibm_apim\Controller\IbmApimThemeInstallController::setDefaultTheme'
      );
    }
    // Remove the admin create new user route.
    $collection->remove('user.admin_create');

    // remove access to drupal's user cancel form (where users can cancel their own accounts)
    // APIC has its own
    $collection->remove('entity.user.cancel_form');

    // do not use admin theme for user pages
    if ($route = $collection->get('user.page')) {
      $route->setOption('_admin_route', FALSE);
    }
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setOption('_admin_route', FALSE);
    }
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setOption('_admin_route', FALSE);
    }
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_title', 'Sign in');
    }
    // add a requirement for delete node type to ensure it isnt one of our node types
    if ($route = $collection->get('entity.node_type.delete_form')) {
      // (second argument must be a string)
      $route->addRequirements(array('_ibm_node_type_check' => 'TRUE'));
    }

    // add a requirement for delete field to ensure it isnt one of ours
    if ($route = $collection->get('entity.field_config.node_field_delete_form')) {
      // (second argument must be a string)
      $route->addRequirements(array('_ibm_field_type_check' => 'TRUE'));
    }

    // ensure do not delete our view modes
    if ($route = $collection->get('entity.entity_view_mode.delete_form')) {
      // (second argument must be a string)
      $route->addRequirements(array('_ibm_view_mode_check' => 'TRUE'));
    }
    // ensure do not delete our form modes
    if ($route = $collection->get('entity.entity_form_mode.delete_form')) {
      // (second argument must be a string)
      $route->addRequirements(array('_ibm_form_mode_check' => 'TRUE'));
    }
    // ensure do not delete our views
    if ($route = $collection->get('entity.view.delete_form')) {
      // (second argument must be a string)
      $route->addRequirements(array('_ibm_view_check' => 'TRUE'));
    }
    // ensure do not delete tags vocabulary
    if ($route = $collection->get('entity.taxonomy_vocabulary.delete_form')) {
      // (second argument must be a string)
      $route->addRequirements(array('_ibm_taxonomy_check' => 'TRUE'));
    }

    // prevent removing our account fields
    if ($route = $collection->get('entity.field_config.user_field_delete_form')) {
      // (second argument must be a string)
      $route->addRequirements(array('_ibm_user_field_check' => 'TRUE'));
    }

    if($route = $collection->get('entity.configurable_language.delete_form')) {
      $route->addRequirements(array('_ibm_langauge_delete_check' => 'TRUE'));
    }

  }

  /**
   * ensure we run last
   *
   * @return mixed
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }
}
