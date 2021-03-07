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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\Rest\Payload\TokenResponseReader;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;

/**
 * @coversDefaultClass Drupal\ibm_apim\Rest\Payload\TokenResponseReader
 *
 * @group ibm_apim
 */
class TokenResponseReaderTest extends UnitTestCase {

  private $prophet;

  private $config;

  private $moduleHandler;

  private $messenger;
  
  protected function setup() {
    $this->prophet = new Prophet();
  }

  public function testCanReadNoRefreshTokenReponse(): void {

    $data = ['access_token' => 'The-bearer-Token',
    'token_type' => 'Bearer',
    'expires_in' => 123456];
    $response = new \stdClass();
    $response->code = 200;
    $response->headers = ['content-type' => 'text/html'];
    $response->data = $data;

    $this->config = $this->prophet->prophesize(\Drupal\Core\Config\ConfigFactory::class);
    $this->moduleHandler = $this->prophet->prophesize(Drupal\Core\Extension\ModuleHandler::class);
    $this->messenger = $this->prophet->prophesize(Drupal\Core\Messenger\Messenger::class);

    $this->config->willImplement(ConfigFactoryInterface::class);
    $this->messenger->willImplement(MessengerInterface::class);
    $this->moduleHandler->willImplement(ModuleHandlerInterface::class);

    $tokenResponseReader = new TokenResponseReader($this->moduleHandler->reveal(), $this->config->reveal(), $this->messenger->reveal());
    $token_response = $tokenResponseReader->read($response);
    
    $this->assertNotNull($token_response);
    $this->assertEquals($token_response->getBearerToken(), 'The-bearer-Token');
    $this->assertNull($token_response->getRefreshToken());
    $this->assertNull($token_response->getRefreshExpiresIn());
  }

  public function testCanReadRefreshTokenResponse(): void {
    $data = ['access_token' => 'The-bearer-Token',
    'token_type' => 'Bearer',
    'expires_in' => 123456,
    'refresh_token' => 'The-refresh-Token'];
    $response = new \stdClass();
    $response->code = 200;
    $response->headers = ['content-type' => 'text/html'];
    $response->data = $data;

    $this->config = $this->prophet->prophesize(\Drupal\Core\Config\ConfigFactory::class);
    $this->moduleHandler = $this->prophet->prophesize(Drupal\Core\Extension\ModuleHandler::class);
    $this->messenger = $this->prophet->prophesize(Drupal\Core\Messenger\Messenger::class);

    $this->config->willImplement(ConfigFactoryInterface::class);
    $this->messenger->willImplement(MessengerInterface::class);
    $this->moduleHandler->willImplement(ModuleHandlerInterface::class);

    $tokenResponseReader = new TokenResponseReader($this->moduleHandler->reveal(), $this->config->reveal(), $this->messenger->reveal());
    $token_response = $tokenResponseReader->read($response);

    $this->assertNotNull($token_response);
    $this->assertEquals($token_response->getBearerToken(), 'The-bearer-Token');
    $this->assertEquals($token_response->getRefreshToken(), 'The-refresh-Token');
  }
}
