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

namespace Drupal\apic_app\Routing;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ResponseSubscriber extends HttpExceptionSubscriberBase {

  protected $currentUser;

  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  protected function getHandledFormats() : array{
    return ['html'];
  }

  public function on404(GetResponseForExceptionEvent $event) : void{
    $request = $event->getRequest();
    $is_anonymous = $this->currentUser->isAnonymous();
    $pathInfo = $request->getPathInfo();
    $is_apic_app = strpos($pathInfo, '/application/') === 0;

    if ($is_anonymous && $is_apic_app) {
      $options = [
        'absolute' => TRUE,
      ];

      $options['query']['redirectto'] = $pathInfo;
      $login_uri = Url::fromRoute('user.login', [], $options)->toString();
      $externalRedirect = UrlHelper::isExternal($login_uri);
      if ($externalRedirect) {
        $returnResponse = new TrustedRedirectResponse($login_uri);
      } else {
        $returnResponse = new RedirectResponse($login_uri);
      }

      $event->setResponse($returnResponse);
    }
  }

}