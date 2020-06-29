<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

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
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   */
  public function refreshAccessToken(PostResponseEvent $event) : void{
    if (\Drupal::currentUser()->id() == 0 || \Drupal::currentUser()->id() == 1) {
      return;
    }

    $refresh = $this->sessionStore->get('refresh');
    $expires_in = $this->sessionStore->get('expires_in');
    $refresh_expires_in = $this->sessionStore->get('refresh_expires_in');

    if (isset($refresh) && isset($expires_in) && (int) $expires_in < time()) {
      if (!isset($refresh_expires_in) || (int) $refresh_expires_in > time()) {
        $this->mgmtServer->refreshAuth();
      } else {
        $this->logger->notice('Refresh token has expired.');
      }
    }
  }

  /**
   * @return array|mixed
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['refreshAccessToken'];
    return $events;
  }
}