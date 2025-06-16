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

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\Controller\ApicOidcAzCodeController;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Symfony\Component\HttpFoundation\InputBag;


/**
 * @coversDefaultClass \Drupal\auth_apic\Service\OidcStateService
 *
 * @group auth_apic
 */
class ApicOidcAzCodeControllerTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $siteConfig;

  /**
   * @var \Drupal\ibm_apim\Service\Utils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $utils;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userRegistryService;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $loginService;

  protected $authApicSessionStore;

  /**
   * @var \Drupal\auth_apic\Controller\ApicOidcAzCodeController
   */
  protected ApicOidcAzCodeController $controller;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $mgmtServer;

  /**
   * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $storeFactory;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $store;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

    /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryption;

  /**
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $profileManager;
  protected $encryptionProfile;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    $this->apimUtils = $this->prophet->prophesize('Drupal\ibm_apim\Service\ApimUtils');
    $this->siteConfig = $this->prophet->prophesize('Drupal\ibm_apim\Service\SiteConfig');
    $this->utils = $this->prophet->prophesize('Drupal\ibm_apim\Service\Utils');
    $this->userRegistryService = $this->prophet->prophesize('Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface');
    $this->loginService = $this->prophet->prophesize('Drupal\auth_apic\UserManagement\ApicLoginServiceInterface');
    $this->storeFactory = $this->prophet->prophesize('Drupal\Core\TempStore\PrivateTempStoreFactory');
    $this->store = $this->prophet->prophesize('Drupal\Core\TempStore\PrivateTempStore');
    $this->requestStack = $this->prophet->prophesize('Symfony\Component\HttpFoundation\RequestStack');
    $this->request = $this->prophet->prophesize('Symfony\Component\HttpFoundation\Request');
    $this->mgmtServer = $this->prophet->prophesize('Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface');
    $this->messenger = $this->prophet->prophesize('Drupal\Core\Messenger\Messenger');
    $this->encryption = $this->prophet->prophesize('\Drupal\encrypt\EncryptServiceInterface');
    $this->profileManager = $this->prophet->prophesize('\Drupal\encrypt\EncryptionProfileManagerInterface');
    $this->encryptionProfile = $this->prophet->prophesize('\Drupal\encrypt\EncryptionProfileInterface');
    $translator = $this->prophet->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->storeFactory->get('auth_apic_storage')->willReturn($this->store);
    $route = $this->prophet->prophesize('Drupal\Core\Routing\UrlGenerator');
    $container = new ContainerBuilder();


    $container->set('url_generator', $route->reveal());
    \Drupal::setContainer($container);

    $this->controller = new ApicOidcAzCodeController(
      $this->utils->reveal(),
      $this->loginService->reveal(),
      $this->userRegistryService->reveal(),
      $this->apimUtils->reveal(),
      $this->siteConfig->reveal(),
      $this->logger->reveal(),
      $this->storeFactory->reveal(),
      $this->requestStack->reveal(),
      $this->mgmtServer->reveal(),
      $this->messenger->reveal(),
      $this->encryption->reveal(),
      $this->profileManager->reveal()
    );
    $this->controller->setStringTranslation($translator->reveal());
    $this->request->reveal();
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function testValidateOidcRedirect(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('error', '');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->request->query->set('state', 'ImtleSI=');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->store->get('redirect_to')->willReturn();
    $this->store->delete(Argument::any())->willReturn();
    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->loginService->loginViaAzCode('601e0142-55c2-406e-98e3-10ba1fa3f2e8', 'registryUrl')
      ->willReturn("https://correctRedirectLocation.com");

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    self::assertEquals("https://correctRedirectLocation.com", $this->controller->validateOidcRedirect());
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function testValidateOidcRedirectError(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('error', 'code 20805');
    $this->request->query->set('error_description', 'Server crashed');

    $this->logger->error("validateOidcRedirect error: @errordes", ["@errordes" => "Server crashed"])->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateOidcRedirect());
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function testValidateOidcRedirectMissingCode(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('error', '');
    $this->request->query->set('code', '');

    $this->logger->error(Argument::containingString('Missing authorization code parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateOidcRedirect());
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function testValidateOidcRedirectMissingState(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('error', '');
    $this->request->query->set('code', 'code');
    $this->request->query->set('state', '');

    $this->logger->error(Argument::containingString('Missing state parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateOidcRedirect());
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function testValidateOidcRedirectIncorrectState(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('error', '');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->request->query->set('state', 'ImtleSI=');

    $this->encryption->decrypt('key', Argument::any())->willReturn();

    $this->encryption->decrypt('data', Argument::any())->willReturn(json_encode(array("registry_url" => 'as')));

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');

    $this->logger->error(Argument::containingString('Could not get state'), Argument::any())->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateOidcRedirect());
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function testValidateOidcRedirectLoginFailed(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('error', '');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->request->query->set('state', 'ImtleSI=');

    $this->store->delete(Argument::any())->willReturn();
    $this->store->get('redirect_to')->willReturn();

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->loginService->loginViaAzCode('601e0142-55c2-406e-98e3-10ba1fa3f2e8', 'registryUrl')->willReturn("ERROR");

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    self::assertEquals("<front>", $this->controller->validateOidcRedirect());
  }


  public function testValidateApimOidcRedirect(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('state', 'ImtleSI=_apimstate');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->request->query->set('q', 'ibm_apim/oidcredirect');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8';
    $result = new RestResponse();
    $result->setHeaders(['Location' => "https://correctRedirectLocation.com"]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    self::assertEquals("https://correctRedirectLocation.com", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectWithExtraParams(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('state', 'ImtleSI=_apimstate');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->request->query->set('q', 'ibm_apim/oidcredirect');
    $this->request->query->set('scope', 'Looking glass');
    $this->request->query->set('xtoken', 'e0142');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8' .
      '&scope=Looking%20glass' .
      '&xtoken=e0142';
    $result = new RestResponse();
    $result->setHeaders(['Location' => "https://correctRedirectLocation.com"]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    self::assertEquals("https://correctRedirectLocation.com", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectError(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);
    $this->request->query->set('error', 'code 20805');
    $this->request->query->set('error_description', 'Server died');

    $this->logger->error("validateApimOidcRedirect error: @errordes", ["@errordes" => "Server died"])->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectMissingState(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error(Argument::containingString('Missing state parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectMissingCode(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('state', 'state');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error(Argument::containingString('Missing authorization code parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectMissingApimState(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('state', 'badState');
    $this->request->query->set('code', 'code');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error("validateApimOidcRedirect error: Invalid state parameter: @state", ["@state" => "badState"])->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectInvalidStateReceived(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('state', 'badState_apimstate');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->utils->base64_url_decode('badState')->willReturn('s:6:"badKey";');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('badKey', Argument::any())->willReturn();

    $this->logger->error("validateApimOidcRedirect error: Invalid state parameter: @state", ["@state" => "badState"])->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectIncorrectResponseCode(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('state', 'ImtleSI=_apimstate');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->request->query->set('q', 'ibm_apim/oidcredirect');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8';

    $result = new RestResponse();
    $result->setHeaders(['Location' => "https://correctRedirectLocation.com"]);
    $result->setCode(400);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error("validateApimOidcRedirect error: Response code @code", ["@code" => 400])->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcRedirect());
  }

  public function testValidateApimOidcRedirectMissingLocationHeader(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('state', 'ImtleSI=_apimstate');
    $this->request->query->set('code', '601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->request->query->set('q', 'ibm_apim/oidcredirect');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8';

    $result = new RestResponse();
    $result->setHeaders([]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::containingString('Location header'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcRedirect());
  }


  public function testValidateApimOidcAz(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=ImtleSI=' .
      '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
      '&realm=trueRealm' .
      '&response_type=code';
    $url = 'https://oidcServer.com/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    self::assertEquals("https://oidcServer.com/path?redirect_uri=" . urlencode('https://correctRedirectLocation.com/route'), $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzWithPort(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=ImtleSI=' .
      '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
      '&realm=trueRealm' .
      '&response_type=code';
    $url = 'https://oidcServer.com:339/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';


    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    self::assertEquals("https://oidcServer.com:339/path?redirect_uri=" . urlencode('https://correctRedirectLocation.com/route'), $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzMissingClientId(): void {
    $this->request->query = new InputBag();
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error(Argument::containingString('Missing client_id parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzMissingState(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error(Argument::containingString('Missing state parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzMissingRedirect(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'state');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error(Argument::containingString('Missing redirect_uri parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzMissingRealm(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'state');
    $this->request->query->set('redirect_uri', 'redirect_uri');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error(Argument::containingString('Missing realm parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzMissingResponseType(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'state');
    $this->request->query->set('redirect_uri', 'redirect_uri');
    $this->request->query->set('realm', 'realm');
    $this->requestStack->getCurrentRequest()->willReturn($this->request);


    $this->logger->error(Argument::containingString('Missing response_type parameter'))->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzIncorrectResponseType(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'state');
    $this->request->query->set('redirect_uri', 'redirect_uri');
    $this->request->query->set('realm', 'realm');
    $this->request->query->set('response_type', 'data');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->logger->error("validateApimOidcAz error: Incorrect response_type parameter: @responseType", ["@responseType" => "data"])
      ->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzIncorrectState(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'badState');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->utils->base64_url_decode('badState')->willReturn('');

    $this->logger->error("validateApimOidcAz error: Invalid state parameter: @state", ["@state" => "badState"])->shouldBeCalled();
    self::assertEquals("<front>", $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzInvalidRegistryUrl(): void {
    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('wrongRegistryUrl')->willReturn();

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=ImtleSI=' .
      '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
      '&realm=trueRealm' .
      '&response_type=code';
    $url = 'https://oidcServer.com/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'wrongRegistryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error("validateApimOidcAz error: Invalid user registry url: @registryUrl", ["@registryUrl" => "wrongRegistryUrl"])
      ->shouldBeCalled();
    self::assertEquals('<front>', $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzInvalidRealm(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'wrongRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();


    $this->logger->error("validateApimOidcAz error: Invalid realm parameter: @realm does not match @getrealm", [
      "@realm" => "wrongRealm",
      "@getrealm" => "trueRealm",
    ])->shouldBeCalled();
    self::assertEquals('<front>', $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzInvalidClientId(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('wrongClientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();

    $this->logger->error("validateApimOidcAz error: Invalid client_id parameter: @clientId does not match @getclientId", [
      "@clientId" => "clientId",
      "@getclientId" => "wrongClientId",
    ])->shouldBeCalled();
    self::assertEquals('<front>', $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzInvalidRedirect(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/badRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();

    $this->logger->error("validateApimOidcAz error: Invalid redirect_uri parameter: @redirectUri does not match @expectedRedirectUri",
      [
        "@redirectUri" => "https://correctRedirectLocation.com/badRoute",
        "@expectedRedirectUri" => "https://correctRedirectLocation.com/incorrectRoute",
      ])->shouldBeCalled();
    self::assertEquals('<front>', $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzWrongResponseCode(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=ImtleSI=' .
      '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
      '&realm=trueRealm' .
      '&response_type=code';
    $url = 'https://oidcServer.com/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(400);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error("validateApimOidcAz error: Response code @code", ["@code" => 400])->shouldBeCalled();
    self::assertEquals('<front>', $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzMissingLocationHeader(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=ImtleSI=' .
      '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
      '&realm=trueRealm' .
      '&response_type=code';

    $result = new RestResponse();
    $result->setHeaders([]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);


    $this->logger->error(Argument::containingString("Location header"))->shouldBeCalled();
    self::assertEquals('<front>', $this->controller->validateApimOidcAz());
  }

  public function testValidateApimOidcAzInvalidUrl(): void {
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');

    $this->request->query = new InputBag();
    $this->request->query->set('client_id', 'clientId');
    $this->request->query->set('state', 'ImtleSI=');
    $this->request->query->set('redirect_uri', 'https://correctRedirectLocation.com/incorrectRoute');
    $this->request->query->set('realm', 'trueRealm');
    $this->request->query->set('response_type', 'code');

    $this->requestStack->getCurrentRequest()->willReturn($this->request);

    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('ImtleSI=')->willReturn('"key"');
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $this->encryption->decrypt('key', Argument::any())->willReturn('key');
    $this->store->get('key')->willReturn('encryptedData');
    $this->encryption->decrypt('encryptedData', Argument::any())->willReturn(json_encode(array("registry_url" => 'registryUrl')));

    $this->store->get('action')->willReturn();
    $this->store->get('invitation_object')->willReturn();

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=ImtleSI=' .
      '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
      '&realm=trueRealm' .
      '&response_type=code';
    $url = 'https://oidcServer.com';

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData([]);
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error("validateApimOidcAz error: Failed to parse redirect: @redirect_location", ["@redirect_location" => $url])->shouldBeCalled();
    self::assertEquals('<front>', $this->controller->validateApimOidcAz());
  }

}