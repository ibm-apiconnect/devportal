<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for api routes.
 */
class ApiController extends ControllerBase {

  /**
   * This method loads the node with the node having been loaded via a ParamConverter
   *
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   */
  public function apiView(NodeInterface $apiNode = NULL) {
    if (\Drupal::currentUser()->isAnonymous() && !((boolean) \Drupal::config('ibm_apim.settings')->get('show_anonymous_apis'))) {
      $url =  Url::fromRoute('system.401', ['redirectto' => Url::fromRoute('<current>')->getInternalPath()])->toString();
      $returnValue = new RedirectResponse($url);
    } elseif ($apiNode !== NULL && $apiNode->bundle() === 'api') {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $build = $view_builder->view($apiNode, 'full');
      $returnValue = $build;
    }
    else {
      \Drupal::logger('apic_api')->error('apiView: not a valid api.', []);
      $this->messenger()->addWarning(t('The specified arguments were not correct.'));
      $url = Url::fromRoute('<front>')->toString();
      $returnValue = new RedirectResponse($url);
    }
    return $returnValue;
  }

  /**
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   *
   * @return string
   */
  public function apiTitle(NodeInterface $apiNode = NULL): string {
    if ($apiNode !== NULL && $apiNode->bundle() === 'api') {
      $returnValue = $apiNode->getTitle() . ' - ' . \Drupal::config('system.site')->get('name');
    }
    else {
      \Drupal::logger('apic_api')->error('apiView: not a valid api.', []);
      $this->messenger()->addWarning(t('The specified arguments were not correct.'));
      $returnValue = 'ERROR';
    }
    return $returnValue;
  }

}
