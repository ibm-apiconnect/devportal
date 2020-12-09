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

use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Drupal\auth_apic\Controller\ApicOidcAzCodeController;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\ibm_apim\Rest\RestResponse;



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
    $this->store->get('redirect_to')->willReturn();
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




  public function testValidateApimOidcRedirect() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==_apimstate');
    $this->query->all()->willReturn(['q' =>'ibm_apim/oidcredirect' ,'state' =>'czozOiJrZXkiOw==_apimstate', 'code' => '601e0142-55c2-406e-98e3-10ba1fa3f2e8']);
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8';
    $result = new RestResponse();
    $result->setHeaders(['Location' => "https://correctRedirectLocation.com"]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);
    
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "https://correctRedirectLocation.com");
  }

  public function testValidateApimOidcRedirectWithExtraParams() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==_apimstate');
    
    $this->query->all()->willReturn(['q' =>'ibm_apim/oidcredirect' ,'state' =>'czozOiJrZXkiOw==_apimstate', 'code' => '601e0142-55c2-406e-98e3-10ba1fa3f2e8', 
      'scope' => 'Looking glass', 'xtoken' => 'e0142']);
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8' .
      '&scope=Looking%20glass' .
      '&xtoken=e0142';
    $result = new RestResponse();
    $result->setHeaders(['Location' => "https://correctRedirectLocation.com"]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "https://correctRedirectLocation.com");
  }

  public function testValidateApimOidcRedirectError() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn('code 20805');
    $this->query->get('error_description')->willReturn('Server died');

    $this->logger->error(Argument::containingString('Server died'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "<front>");
  }

  public function testValidateApimOidcRedirectMissingState() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('state')->willReturn();

    $this->logger->error(Argument::containingString('Missing state parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "<front>");
  }

  public function testValidateApimOidcRedirectMissingCode() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('state')->willReturn('state');
    $this->query->get('code')->willReturn();

    $this->logger->error(Argument::containingString('Missing authorization code parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "<front>");
  }

  public function testValidateApimOidcRedirectMissingApimState() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('state')->willReturn('badState');
    $this->query->get('code')->willReturn('code');

    $this->logger->error(Argument::containingString('badState'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "<front>");
  }

  public function testValidateApimOidcRedirectInvalidStateReceived() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('badState_apimstate');

    $this->utils->base64_url_decode('badState')->willReturn('s:6:"badKey";');
    $this->oidcStateService->get('badKey')->willReturn();

 
    $this->logger->error(Argument::containingString('badState'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "<front>");
  }

  public function testValidateApimOidcRedirectIncorrectResponseCode() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==_apimstate');
    $this->query->all()->willReturn(['q' =>'ibm_apim/oidcredirect' ,'state' =>'czozOiJrZXkiOw==_apimstate', 'code' => '601e0142-55c2-406e-98e3-10ba1fa3f2e8']);
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8';

    $result = new RestResponse();
    $result->setHeaders(['Location' => "https://correctRedirectLocation.com"]);
    $result->setCode(400);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);    

    $this->logger->error(Argument::containingString('400'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "<front>");
  }

  public function testValidateApimOidcRedirectMissingLocationHeader() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('code')->willReturn('601e0142-55c2-406e-98e3-10ba1fa3f2e8');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==_apimstate');
    $this->query->all()->willReturn(['q' =>'ibm_apim/oidcredirect' ,'state' =>'czozOiJrZXkiOw==_apimstate', 'code' => '601e0142-55c2-406e-98e3-10ba1fa3f2e8']);
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);

    $arg = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=601e0142-55c2-406e-98e3-10ba1fa3f2e8';
      $result = new \stdClass();

    $result = new RestResponse();
    $result->setHeaders([]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);
    
    $this->logger->error(Argument::containingString('Location header'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcRedirect(), "<front>");
  }


  public function testValidateApimOidcAz() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId'.
    '&state=czozOiJrZXkiOw==' .
    '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
    '&realm=trueRealm' .
    '&response_type=code';
    $url = 'https://oidcServer.com/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "https://oidcServer.com/path?redirect_uri=" . urlencode('https://correctRedirectLocation.com/route'));
  }

  public function testValidateApimOidcAzWithPort() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId'.
    '&state=czozOiJrZXkiOw==' .
    '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
    '&realm=trueRealm' .
    '&response_type=code';
    $url = 'https://oidcServer.com:339/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';


    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "https://oidcServer.com:339/path?redirect_uri=" . urlencode('https://correctRedirectLocation.com/route'));
  }

  public function testValidateApimOidcAzMissingClientId() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn();
    
    $this->logger->error(Argument::containingString('Missing client_id parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "<front>");
  }

  public function testValidateApimOidcAzMissingState() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn();
    
    $this->logger->error(Argument::containingString('Missing state parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "<front>");
  }

  public function testValidateApimOidcAzMissingRedirect() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('state');
    $this->query->get('redirect_uri')->willReturn();
    
    $this->logger->error(Argument::containingString('Missing redirect_uri parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "<front>");
  }
  
  public function testValidateApimOidcAzMissingRealm() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('state');
    $this->query->get('redirect_uri')->willReturn('redirect_uri');
    $this->query->get('realm')->willReturn();
    
    $this->logger->error(Argument::containingString('Missing realm parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "<front>");
  }

  public function testValidateApimOidcAzMissingResponseType() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('state');
    $this->query->get('redirect_uri')->willReturn('redirect_uri');
    $this->query->get('realm')->willReturn('realm');
    $this->query->get('response_type')->willReturn();
    
    $this->logger->error(Argument::containingString('Missing response_type parameter'))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "<front>");
  }
  
  public function testValidateApimOidcAzIncorrectResponseType() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('state');
    $this->query->get('redirect_uri')->willReturn('redirect_uri');
    $this->query->get('realm')->willReturn('realm');
    $this->query->get('response_type')->willReturn('data');
    
    $this->logger->error(Argument::containingString("data"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "<front>");
  }

  public function testValidateApimOidcAzIncorrectState() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('badState');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->utils->base64_url_decode('badState')->willReturn();

    $this->logger->error(Argument::containingString("badState"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), "<front>");
  }

  public function testValidateApimOidcAzInvalidRegistryUrl() { 
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'wrongRegistryUrl']);
    $this->userRegistryService->get('wrongRegistryUrl')->willReturn();

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId'.
    '&state=czozOiJrZXkiOw==' .
    '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
    '&realm=trueRealm' .
    '&response_type=code';
    $url = 'https://oidcServer.com/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::containingString("wrongRegistryUrl"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), '<front>');
  }

  public function testValidateApimOidcAzInvalidRealm() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('wrongRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->logger->error(Argument::containingString("wrongRealm"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), '<front>');
  }

  public function testValidateApimOidcAzInvalidClientId() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('wrongClientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->logger->error(Argument::containingString("wrongClientId"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), '<front>');
  }

  public function testValidateApimOidcAzInvalidRedirect() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/badRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $this->logger->error(Argument::containingString("https://correctRedirectLocation.com/badRoute"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), '<front>');
  }

  public function testValidateApimOidcAzWrongResponseCode() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId'.
    '&state=czozOiJrZXkiOw==' .
    '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
    '&realm=trueRealm' .
    '&response_type=code';
    $url = 'https://oidcServer.com/path?redirect_uri=https://correctRedirectLocation.com/incorrectRoute';

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(400);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::containingString("400"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), '<front>');
  }

  public function testValidateApimOidcAzMissingLocationHeader() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId'.
    '&state=czozOiJrZXkiOw==' .
    '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
    '&realm=trueRealm' .
    '&response_type=code';

    $result = new RestResponse();
    $result->setHeaders([]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);


    $this->logger->error(Argument::containingString("Location header"))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), '<front>');
  }

  public function testValidateApimOidcAzInvalidUrl() { 
    $userRegistry = $this->prophet->prophesize('Drupal\ibm_apim\ApicType\UserRegistry');
    $this->requestStack->getCurrentRequest()->willReturn($this->requestStack);
    $this->query->get('error')->willReturn();
    $this->query->get('client_id')->willReturn('clientId');
    $this->query->get('state')->willReturn('czozOiJrZXkiOw==');
    $this->query->get('redirect_uri')->willReturn('https://correctRedirectLocation.com/incorrectRoute');
    $this->query->get('realm')->willReturn('trueRealm');
    $this->query->get('response_type')->willReturn('code');
    $this->query->get('invitation_scope')->willReturn();
    $this->query->get('title')->willReturn();
    $this->apimUtils->getHostUrl()->willReturn('https://correctRedirectLocation.com');
    $this->siteConfig->getClientId()->willReturn('clientId');
    $this->utils->base64_url_decode('czozOiJrZXkiOw==')->willReturn('s:3:"key";');
    $this->oidcStateService->get('key')->willReturn(['registry_url' => 'registryUrl']);
    $this->userRegistryService->get('registryUrl')->willReturn($userRegistry);
    $userRegistry->getRealm()->willReturn('trueRealm');

    $arg = '/consumer-api/oauth2/authorize?client_id=clientId'.
    '&state=czozOiJrZXkiOw==' .
    '&redirect_uri=https://correctRedirectLocation.com/incorrectRoute' .
    '&realm=trueRealm' .
    '&response_type=code';
    $url = 'https://oidcServer.com';

    $result = new RestResponse();
    $result->setHeaders(['Location' => $url]);
    $result->setCode(302);
    $result->setData('');
    $this->mgmtServer->get($arg)->willReturn($result);

    $this->logger->error(Argument::containingString($url))->shouldBeCalled();
    $this->assertEquals($this->controller->validateApimOidcAz(), '<front>');
  }

}
