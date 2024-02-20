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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Messenger\Messenger;
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
 * @coversDefaultClass \Drupal\ibm_apim\Rest\Payload\TokenResponseReader
 *
 * @group ibm_apim
 */
class TokenResponseReaderTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private  $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private  $moduleHandler;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private  $messenger;

  protected function setup(): void {
    $this->prophet = new Prophet();
  }

  /**
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function testCanReadNoRefreshTokenResponse(): void {

    $data = ['access_token' => 'The-bearer-Token',
    'token_type' => 'Bearer',
    'expires_in' => 123456];
    $response = new \stdClass();
    $response->code = 200;
    $response->headers = ['content-type' => 'text/html'];
    $response->data = $data;

    $this->config = $this->prophet->prophesize(ConfigFactoryInterface::class);
    $this->moduleHandler = $this->prophet->prophesize(ModuleHandlerInterface::class);
    $this->messenger = $this->prophet->prophesize(MessengerInterface::class);

    $this->config->willImplement(ConfigFactoryInterface::class);
    $this->messenger->willImplement(MessengerInterface::class);
    $this->moduleHandler->willImplement(ModuleHandlerInterface::class);

    $tokenResponseReader = new TokenResponseReader($this->moduleHandler->reveal(), $this->config->reveal(), $this->messenger->reveal());
    $token_response = $tokenResponseReader->read($response);

    self::assertNotNull($token_response);
    self::assertEquals('The-bearer-Token', $token_response->getBearerToken());
    self::assertNull($token_response->getRefreshToken());
    self::assertNull($token_response->getRefreshExpiresIn());
  }

  /**
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function testCanReadRefreshTokenResponse(): void {
    $data = ['access_token' => 'The-bearer-Token',
    'token_type' => 'Bearer',
    'expires_in' => 123456,
    'refresh_token' => 'The-refresh-Token'];
    $response = new \stdClass();
    $response->code = 200;
    $response->headers = ['content-type' => 'text/html'];
    $response->data = $data;

    $this->config = $this->prophet->prophesize(ConfigFactory::class);
    $this->moduleHandler = $this->prophet->prophesize(ModuleHandlerInterface::class);
    $this->messenger = $this->prophet->prophesize(Messenger::class);

    $this->config->willImplement(ConfigFactoryInterface::class);
    $this->messenger->willImplement(MessengerInterface::class);
    $this->moduleHandler->willImplement(ModuleHandlerInterface::class);

    $tokenResponseReader = new TokenResponseReader($this->moduleHandler->reveal(), $this->config->reveal(), $this->messenger->reveal());
    $token_response = $tokenResponseReader->read($response);

    self::assertNotNull($token_response);
    self::assertEquals('The-bearer-Token', $token_response->getBearerToken());
    self::assertEquals('The-refresh-Token', $token_response->getRefreshToken());
  }
}
