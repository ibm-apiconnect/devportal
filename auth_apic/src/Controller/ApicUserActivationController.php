<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\auth_apic\UserManagement\ApicActivationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\Messenger;
use Drupal\ibm_apim\Service\SiteConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApicUserActivationController extends ControllerBase {

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\TokenParserInterface
   */
  protected TokenParserInterface $jwtParser;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicActivationInterface
   */
  protected ApicActivationInterface $activation;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ApicUserActivationController constructor.
   *
   * @param \Drupal\auth_apic\Service\Interfaces\TokenParserInterface $tokenParser
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Drupal\auth_apic\UserManagement\ApicActivationInterface $activation_service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(TokenParserInterface $tokenParser,
                              SiteConfig $site_config,
                              ApicActivationInterface $activation_service,
                              Messenger $messenger) {
    $this->jwtParser = $tokenParser;
    $this->siteConfig = $site_config;
    $this->activation = $activation_service;
    $this->messenger = $messenger;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\auth_apic\Controller\ApicUserActivationController|static
   */
  public static function create(ContainerInterface $container): ApicUserActivationController {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('auth_apic.jwtparser'),
      $container->get('ibm_apim.site_config'),
      $container->get('auth_apic.activation'),
      $container->get('messenger')
    );
  }

  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function activate(): \Symfony\Component\HttpFoundation\RedirectResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $activationToken = \Drupal::request()->query->get('activation') ?: 'default';
    if (empty($activationToken)) {
      $this->messenger->addError(t('Missing activation token. Unable to proceed.'));
      return $this->redirect('<front>');
    }

    $jwtToken = $this->jwtParser->parse($activationToken);
    if ($jwtToken !== NULL) {
      $this->activation->activate($jwtToken);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->redirect('<front>');
  }

}
