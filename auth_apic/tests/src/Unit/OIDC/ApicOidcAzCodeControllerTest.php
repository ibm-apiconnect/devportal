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

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\Controller\ApicOidcAzCodeController;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;


/**
 * @coversDefaultClass \Drupal\auth_apic\Service\OidcStateService
 *
 * @group auth_apic
 */
class ApicOidcAzCodeControllerTest extends UnitTestCase {

  private $prophet;

  /*
   Dependencies of OidcStateService.
   */
  protected $logger;
  protected $apimUtils;
  protected $siteConfig;
  protected $utils;
  protected $userRegistryService;
  protected $loginService;
  protected $oidcStateService;
  protected $authApicSessionStore;
  protected $controller;
  protected $query;
  protected $mgmtServer;
  protected $messenger;
  protected $storeFactory;
  protected $store;


  protected function setup() {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    $this->apimUtils = $this->prophet->prophesize('Drupal\ibm_apim\Service\ApimUtils');
    $this->siteConfig = $this->prophet->prophesize('Drupal\ibm_apim\Service\SiteConfig');
    $this->utils = $this->prophet->prophesize('Drupal\ibm_apim\Service\Utils');
    $this->userRegistryService = $this->prophet->prophesize('Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface');
    $this->loginService = $this->prophet->prophesize('Drupal\auth_apic\UserManagement\ApicLoginServiceInterface');
    $this->oidcStateService = $this->prophet->prophesize('Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface');
    $this->storeFactory = $this->prophet->prophesize('Drupal\session_based_temp_store\SessionBasedTempStoreFactory');
    $this->store = $this->prophet->prophesize('Drupal\session_based_temp_store\SessionBasedTempStore');
    $this->requestStack= $this->prophet->prophesize('Symfony\Component\HttpFoundation\RequestStack');
    $this->query = $this->prophet->prophesize('Symfony\Component\HttpFoundation\ParameterBag');
    $this->mgmtServer = $this->prophet->prophesize('Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface');
    $this->messenger = $this->prophet->prophesize('Drupal\Core\Messenger\Messenger');
    $translator = $this->prophet->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->storeFactory->get('auth_apic_invitation_token')->willReturn($this->store);
    $route = $this->prophet->prophesize('Drupal\Core\Routing\UrlGenerator;');
    $container = new ContainerBuilder();


    $container->set('url_generator', $route->reveal());
    \Drupal::setContainer($container);

    $this->controller = new ApicOidcAzCodeController(
      $this->utils->reveal(),
      $this->loginService->reveal(),
      $this->oidcStateService->reveal(),
      $this->userRegistryService->reveal(),
      $this->apimUtils->reveal(),
      $this->siteConfig->reveal(),
      $this->logger->reveal(),
      $this->storeFactory->reveal(),
      $this->requestStack->reveal(),
      $this->mgmtServer->reveal(),
      $this->messenger->reveal()
    );
    $this->controller->setStringTranslation($translator->reveal());
    $this->requestStack->query = $this->query;
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  public function testValidateOidcRedirect() {
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->oidcStateService->delete('key')->willReturn();
    $this->store->delete(Argument::any())->willReturn();
    $this->loginService->loginViaAzCode('601e0142-55c2-406e-98e3-10ba1fa3f2e8', 'registryUrl')->willReturn("https://correctRedirectLocation.com");

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->assertEquals($this->controller->validateOidcRedirect(), "https://correctRedirectLocation.com");
  }

  public function testValidateOidcRedirectError() {
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn('code 20805');
    $this->query->get('error_description')->willReturn('Server crashed');

    $this->logger->error(Argument::containingString('Server crashed'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateOidcRedirect(), "<front>");
  }

  public function testValidateOidcRedirectMissingCode() {
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn();

    $this->logger->error(Argument::containingString('Missing authorization code parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateOidcRedirect(), "<front>");
  }

  public function testValidateOidcRedirectMissingState() {
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('code');
    $this->query->get('state')->willReturn();

    $this->logger->error(Argument::containingString('Missing state parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateOidcRedirect(), "<front>");
  }

  public function testValidateOidcRedirectIncorrectState() {
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn();

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->assertEquals($this->controller->validateOidcRedirect(), "<front>");
  }

  public function testValidateOidcRedirectLoginFailed() {
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->oidcStateService->delete('key')->willReturn();
    $this->store->delete(Argument::any())->willReturn();
    $this->loginService->loginViaAzCode('601e0142-55c2-406e-98e3-10ba1fa3f2e8', 'registryUrl')->willReturn("ERROR");

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->assertEquals($this->controller->validateOidcRedirect(), "<front>");
  }

}