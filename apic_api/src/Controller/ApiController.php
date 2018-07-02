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

namespace Drupal\apic_api\Controller;

use Drupal\apic_api\Api;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for api routes.
 */
class ApiController extends ControllerBase {

  /**
   * This method loads the node with the node having been loaded via a ParamConverter
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function apiView(NodeInterface $apiNode = NULL) {
    if ($apiNode->bundle() == 'api') {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $build = $view_builder->view($apiNode, 'full');
      return $build;
    }
    else {
      \Drupal::logger('apic_api')->error('apiView: not a valid api.', array());
      drupal_set_message(t('The specified arguments were not correct.'), 'warning');
      $url = Url::fromRoute('<front>')->toString();
      return new RedirectResponse($url);
    }
  }

  /**
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   * @return string
   */
  public function apiTitle(NodeInterface $apiNode = NULL) {
    if ($apiNode->bundle() == 'api') {
      return $apiNode->getTitle() .' - ' . \Drupal::config('system.site')->get('name');
    } else {
      \Drupal::logger('apic_api')->error('apiView: not a valid api.', array());
      drupal_set_message(t('The specified arguments were not correct.'), 'warning');
      return 'ERROR';
    }
  }
}
