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

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\auth_apic\UserManagement\ApicActivationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\Messenger;
use Drupal\ibm_apim\Service\SiteConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApicUserActivationController extends ControllerBase {

  protected $jwtParser;

  protected $siteConfig;

  protected $activation;

  protected $messenger;

  public function __construct(TokenParserInterface $tokenParser,
                              SiteConfig $site_config,
                              ApicActivationInterface $activation_service,
                              Messenger $messenger) {
    $this->jwtParser = $tokenParser;
    $this->siteConfig = $site_config;
    $this->activation = $activation_service;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('auth_apic.jwtparser'),
      $container->get('ibm_apim.site_config'),
      $container->get('auth_apic.activation'),
      $container->get('messenger')
    );
  }

  public function activate(): \Symfony\Component\HttpFoundation\RedirectResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $activationToken = \Drupal::request()->query->get('activation') ?: 'default';
    if (empty($activationToken)) {
      $this->messenger->addError(t('Missing activation token. Unable to proceed.'));
      return $this->redirect('<front>');
    }

    $jwttoken = $this->jwtParser->parse($activationToken);
    $this->activation->activate($jwttoken);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->redirect('<front>');
  }
}
