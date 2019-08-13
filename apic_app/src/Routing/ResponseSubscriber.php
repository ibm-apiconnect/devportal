<?php

namespace Drupal\apic_app\Routing;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

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