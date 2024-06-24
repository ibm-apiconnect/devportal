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

namespace Drupal\ibm_apim\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\Core\Url;
use Drupal\Core\Messenger\Messenger;

class ConsumerOrgCheckSubscriber implements EventSubscriberInterface
{

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;


  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;
  /**
   * APIMServer constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   */
  public function __construct(
    UserUtils $userUtils,
    SiteConfig $siteConfig,
    LoggerInterface $logger,
    Messenger $messenger
  ) {
    $this->userUtils = $userUtils;
    $this->logger = $logger;
    $this->siteConfig = $siteConfig;
    $this->messenger = $messenger;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function consumerorgCheck(RequestEvent $event): void
  {
    if (
      \Drupal::routeMatch()->getRouteName() === 'consumerorg.create' ||
      \Drupal::routeMatch()->getRouteName() === 'auth_apic.oidc_first_time_login' ||
      \Drupal::routeMatch()->getRouteName() === 'ibm_apim.noperms' ||
      \Drupal::routeMatch()->getRouteName() === 'session_limit.limit_form' ||
      \Drupal::routeMatch()->getRouteName() === 'system.css_asset' ||
      \Drupal::routeMatch()->getRouteName() === 'system.js_asset' ||
      \Drupal::routeMatch()->getRouteName() === 'auth_apic.invitation' ||
      \Drupal::routeMatch()->getRouteName() === 'user.logout' ||
      !\Drupal::currentUser()->isAuthenticated() ||
      (int) \Drupal::currentUser()->id() === 0 ||
      (int) \Drupal::currentUser()->id() === 1 ||
      in_array('administrator', \Drupal::currentUser()->getRoles())
    ) {
      return;
    }
    $currentCOrg = $this->userUtils->getCurrentConsumerorg();

    if (!isset($currentCOrg)) {
      if (!$this->siteConfig->isSelfOnboardingEnabled()) {
        $response = new RedirectResponse(Url::fromRoute('ibm_apim.noperms')->toString());
        $event->setResponse($response);
        $this->logger->notice('User is not part of a consumer organization. Redirecting to no permissions');
        $this->messenger->addError(t('You are not part of a consumer organization. Please contact your system administrator.'));
      } else {
        $response = new RedirectResponse(Url::fromRoute('consumerorg.create')->toString());
        $event->setResponse($response);
        $this->logger->notice('User is not part of a consumer organization. Redirecting to create consumer organization');
        $this->messenger->addError(t('You are not part of a consumer organization. Please create one before continuing.'));
      }
    }
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents()
  {
    $events[KernelEvents::REQUEST][] = ['consumerorgCheck'];
    return $events;
  }
}
