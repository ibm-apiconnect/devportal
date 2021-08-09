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

namespace Drupal\ibm_apim\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RefreshTokenSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $sessionStore;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  protected $mgmtServer;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

    /**
   * APIMServer constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
                              ManagementServerInterface $mgmtServer, 
                              LoggerInterface $logger ) {
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
    $this->mgmtServer = $mgmtServer;
    $this->logger = $logger;
  }


  /**
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function refreshAccessToken(TerminateEvent $event): void {
    if ((int) \Drupal::currentUser()->id() === 0 || (int) \Drupal::currentUser()->id() === 1) {
      return;
    }

    $refresh = $this->sessionStore->get('refresh');
    $expires_in = $this->sessionStore->get('expires_in');
    $refresh_expires_in = $this->sessionStore->get('refresh_expires_in');


    if (isset($refresh) && isset($expires_in) && (int) $expires_in < time()) {
      $refreshed = false;
      if (!isset($refresh_expires_in) || (int) $refresh_expires_in > time()) {
        $refreshed = $this->mgmtServer->refreshAuth();
      }
      if (!$refreshed) {
        $this->sessionStore->set('logout',true);
      }
    }
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function forceLogOut(ResponseEvent $event): void {
    if ((int) \Drupal::currentUser()->id() === 0 || (int) \Drupal::currentUser()->id() === 1) {
      return;
    }

    $refresh = $this->sessionStore->get('refresh');
    $expires_in = $this->sessionStore->get('expires_in');
    $refresh_expires_in = $this->sessionStore->get('refresh_expires_in');
    $logout = $this->sessionStore->get('logout');

    if (\Drupal::currentUser()->isAuthenticated() &&
    ($logout ||  (isset($expires_in) && (int) $expires_in < time() && (!isset($refresh) || (isset($refresh_expires_in) && (int) $refresh_expires_in < time()))))) {
      if ($logout) {
        $logout = $this->sessionStore->delete('logout');
        $this->logger->notice('Failed to refresh access token. Forcing logout.');
      } else {
        $this->logger->notice('Session expired based on token expires_in value. Forcing logout.');
      }
      // set a cookie so that we can display an error after we log the user out, picked up in AdminMessagesBlock
      // this was supposed to be handled by building the redirectresponse from the current request in the subsequent code, however the messages
      // were being lost, possibly because of the extra redirects in user.logout???... but this mechanism works.
      user_cookie_save(['ibm_apim_session_expired_on_token' => 'TRUE']);
      $logout_url = Url::fromRoute('user.logout');
      $response = new RedirectResponse($logout_url->toString());
      $request = \Drupal::request();
      $session_manager = \Drupal::service('session_manager');
      $session_manager->delete(\Drupal::currentUser()->id());
      $response->prepare($request);
      $event->setResponse($response);
    }
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['refreshAccessToken'];
    $events[KernelEvents::RESPONSE][] = ['forceLogOut'];
    return $events;
  }
}