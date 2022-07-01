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

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\JWTParser;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\auth_apic\Service\JWTParser
 *
 * Use jwt.io for debugging
 *
 * @group auth_apic
 */
class JWTParserTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Utils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $utils;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    $this->utils = $this->prophet->prophesize(\Drupal\ibm_apim\Service\Utils::class);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * Positive parser test.
   *
   * @throws \JsonException
   * @throws \Exception
   */
  public function testValidParse(): void {

    $this->utils->base64_url_decode($this->getValidHeaders())->willReturn(\base64_decode($this->getValidHeaders()));
    $this->utils->base64_url_decode($this->getValidPayload())->willReturn(\base64_decode($this->getValidPayload()));
    $this->utils->base64_url_decode($this->getValidSignature())->willReturn(\base64_decode($this->getValidSignature()));

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal());
    $result = $parser->parse($this->getValidEncodedJWT());
    self::assertEquals($this->getValidJWTObject(), $result, 'Unexpected token object produced from parser with encoded token');

    $result = $parser->parse($this->getValidJWT());
    self::assertEquals($this->getValidJWTObject(), $result, 'Unexpected token object produced from parser with jwt token');
  }

  /**
   * Invalid - missing headers section from encoded JWT
   *
   * @throws \Exception
   */
  public function testInvalidTokenParse(): void {

    $this->logger->error('Invalid JWT token. Expected 3 period separated elements.')->shouldBeCalled();
    $this->logger->error('invalid invitation JWT')->shouldBeCalled();

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal());
    $result = $parser->parse($this->getEncodedJWTWithMissingHeaders());

    self::assertNull($result, 'Result from invalid parse is not NULL');
  }

  /**
   * Invalid - missing URL in token.
   *
   * @throws \Exception
   */
  public function testMissingUrlParse(): void {

    $this->logger->error('payload.scopes.url not available in activation JWT')->shouldBeCalled();
    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal());
    $result = $parser->parse($this->getEncodedJWTWithMissingUrl());
    self::assertNull($result, 'Unexpected response when parsing a token with missing URL');

  }


  /**
   * Parse NULL.
   *
   */
  public function testParseNULL(): void {
    $this->expectException(\Exception::class);

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal());
    $parser->parse(NULL);
  }

  public function testIsBase64() {

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal());
    self::assertFalse($parser->isBase64($this->getValidJWT()));
    self::assertTrue($parser->isBase64($this->getValidEncodedJWT()));
  }

  /**
   * @throws \JsonException
   */
  private function getValidJWTObject(): JWTToken {
    $jwt = new JWTToken();
    $jwt->setUrl($this->getValidUrl());
    $jwt->setDecodedJwt($this->getValidJWT());
    $jwt->setHeaders(json_decode(\base64_decode($this->getValidHeaders()), TRUE, 512, JSON_THROW_ON_ERROR));
    $jwt->setPayload(json_decode(\base64_decode($this->getValidPayload()), TRUE, 512, JSON_THROW_ON_ERROR));
    $jwt->setSignature($this->getValidSignature());
    return $jwt;
  }


  /**
   * Get decoded JWT
   *
   * - use https://jwt.io/ and https://www.base64encode.org/ to debug.
   *
   * @return string
   *   JWT :
   *
   * {
   * "alg": "HS256",
   * "typ": "JWT"
   * }
   * {
   * "jti": "64452290-4976-4a21-ac08-8a0b3e5da50c",
   * "namespace": "cloud",
   * "aud": "n/a",
   * "sub": "andre@example.com",
   * "email": "andre@example.com",
   * "iss": "IBM API Connect",
   * "token_type": "temporary",
   * "iat": 1525862785,
   * "exp": 1526121985,
   * "scopes": {
   * "url": "/consumer-api/activate?activation_id=7a5a3515-a8c1-4db6-9be2-dd393266df78",
   * "actions": [
   * "activate"
   * ]
   * }
   * }
   * HMACSHA256(
   * base64UrlEncode(header) + "." +
   * base64UrlEncode(payload),
   *
   * your-256-bit-secret
   *
   * )
   *
   */
  private function getValidJWT(): string {
    return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.ew0KICAianRpIjogIjY0NDUyMjkwLTQ5NzYtNGEyMS1hYzA4LThhMGIzZTVkYTUwYyIsDQogICJuYW1lc3BhY2UiOiAiY2xvdWQiLA0KICAiYXVkIjogIm4vYSIsDQogICJzdWIiOiAiYW5kcmVAZXhhbXBsZS5jb20iLA0KICAiZW1haWwiOiAiYW5kcmVAZXhhbXBsZS5jb20iLA0KICAiaXNzIjogIklCTSBBUEkgQ29ubmVjdCIsDQogICJ0b2tlbl90eXBlIjogInRlbXBvcmFyeSIsDQogICJpYXQiOiAxNTI1ODYyNzg1LA0KICAiZXhwIjogMTUyNjEyMTk4NSwNCiAgInNjb3BlcyI6IHsNCiAgICAidXJsIjogIi9jb25zdW1lci1hcGkvYWN0aXZhdGU/YWN0aXZhdGlvbl9pZD03YTVhMzUxNS1hOGMxLTRkYjYtOWJlMi1kZDM5MzI2NmRmNzgiLA0KICAgICJhY3Rpb25zIjogWw0KICAgICAgImFjdGl2YXRlIg0KICAgIF0NCiAgfQ0KfQ==.ePZXLdPjGhYOcWjFLfgJvKdXNicjJuvl9v4wpt-ybD0';
  }

  /**
   * Get base64 encoded version of jwt returned from getValidJWT()
   *
   * @return string
   */
  private function getValidEncodedJWT(): string {
    return 'ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV3MEtJQ0FpYW5ScElqb2dJalkwTkRVeU1qa3dMVFE1TnpZdE5HRXlNUzFoWXpBNExUaGhNR0l6WlRWa1lUVXdZeUlzRFFvZ0lDSnVZVzFsYzNCaFkyVWlPaUFpWTJ4dmRXUWlMQTBLSUNBaVlYVmtJam9nSW00dllTSXNEUW9nSUNKemRXSWlPaUFpWVc1a2NtVkFaWGhoYlhCc1pTNWpiMjBpTEEwS0lDQWlaVzFoYVd3aU9pQWlZVzVrY21WQVpYaGhiWEJzWlM1amIyMGlMQTBLSUNBaWFYTnpJam9nSWtsQ1RTQkJVRWtnUTI5dWJtVmpkQ0lzRFFvZ0lDSjBiMnRsYmw5MGVYQmxJam9nSW5SbGJYQnZjbUZ5ZVNJc0RRb2dJQ0pwWVhRaU9pQXhOVEkxT0RZeU56ZzFMQTBLSUNBaVpYaHdJam9nTVRVeU5qRXlNVGs0TlN3TkNpQWdJbk5qYjNCbGN5STZJSHNOQ2lBZ0lDQWlkWEpzSWpvZ0lpOWpiMjV6ZFcxbGNpMWhjR2t2WVdOMGFYWmhkR1UvWVdOMGFYWmhkR2x2Ymw5cFpEMDNZVFZoTXpVeE5TMWhPR014TFRSa1lqWXRPV0psTWkxa1pETTVNekkyTm1SbU56Z2lMQTBLSUNBZ0lDSmhZM1JwYjI1eklqb2dXdzBLSUNBZ0lDQWdJbUZqZEdsMllYUmxJZzBLSUNBZ0lGME5DaUFnZlEwS2ZRPT0uZVBaWExkUGpHaFlPY1dqRkxmZ0p2S2RYTmljakp1dmw5djR3cHQteWJEMA==';
  }


  // The following functions just return specific parts of the valid token.
  private function getValidHeaders(): string {
    return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
  }


  private function getValidPayload(): string {
    return 'ew0KICAianRpIjogIjY0NDUyMjkwLTQ5NzYtNGEyMS1hYzA4LThhMGIzZTVkYTUwYyIsDQogICJuYW1lc3BhY2UiOiAiY2xvdWQiLA0KICAiYXVkIjogIm4vYSIsDQogICJzdWIiOiAiYW5kcmVAZXhhbXBsZS5jb20iLA0KICAiZW1haWwiOiAiYW5kcmVAZXhhbXBsZS5jb20iLA0KICAiaXNzIjogIklCTSBBUEkgQ29ubmVjdCIsDQogICJ0b2tlbl90eXBlIjogInRlbXBvcmFyeSIsDQogICJpYXQiOiAxNTI1ODYyNzg1LA0KICAiZXhwIjogMTUyNjEyMTk4NSwNCiAgInNjb3BlcyI6IHsNCiAgICAidXJsIjogIi9jb25zdW1lci1hcGkvYWN0aXZhdGU/YWN0aXZhdGlvbl9pZD03YTVhMzUxNS1hOGMxLTRkYjYtOWJlMi1kZDM5MzI2NmRmNzgiLA0KICAgICJhY3Rpb25zIjogWw0KICAgICAgImFjdGl2YXRlIg0KICAgIF0NCiAgfQ0KfQ==';
  }

  private function getValidSignature(): string {
    return 'ePZXLdPjGhYOcWjFLfgJvKdXNicjJuvl9v4wpt-ybD0';
  }

  private function getValidUrl(): string {
    return '/activate?activation_id=7a5a3515-a8c1-4db6-9be2-dd393266df78';
  }

  // Invalid tokens... for specific tests
  private function getEncodedJWTWithMissingHeaders(): string {
    return 'ZXcwS0lDQWlhblJwSWpvZ0lqWTBORFV5TWprd0xUUTVOell0TkdFeU1TMWhZekE0TFRoaE1HSXpaVFZrWVRVd1l5SXNEUW9nSUNKdVlXMWxjM0JoWTJVaU9pQWlZMnh2ZFdRaUxBMEtJQ0FpWVhWa0lqb2dJbTR2WVNJc0RRb2dJQ0p6ZFdJaU9pQWlZVzVrY21WQVpYaGhiWEJzWlM1amIyMGlMQTBLSUNBaVpXMWhhV3dpT2lBaVlXNWtjbVZBWlhoaGJYQnNaUzVqYjIwaUxBMEtJQ0FpYVhOeklqb2dJa2xDVFNCQlVFa2dRMjl1Ym1WamRDSXNEUW9nSUNKMGIydGxibDkwZVhCbElqb2dJblJsYlhCdmNtRnllU0lzRFFvZ0lDSnBZWFFpT2lBeE5USTFPRFl5TnpnMUxBMEtJQ0FpWlhod0lqb2dNVFV5TmpFeU1UazROU3dOQ2lBZ0luTmpiM0JsY3lJNklIc05DaUFnSUNBaWRYSnNJam9nSWk5amIyNXpkVzFsY2kxaGNHa3ZZV04wYVhaaGRHVS9ZV04wYVhaaGRHbHZibDlwWkQwM1lUVmhNelV4TlMxaE9HTXhMVFJrWWpZdE9XSmxNaTFrWkRNNU16STJObVJtTnpnaUxBMEtJQ0FnSUNKaFkzUnBiMjV6SWpvZ1d3MEtJQ0FnSUNBZ0ltRmpkR2wyWVhSbElnMEtJQ0FnSUYwTkNpQWdmUTBLZlE9PS5lUFpYTGRQakdoWU9jV2pGTGZnSnZLZFhOaWNqSnV2bDl2NHdwdC15YkQw';
  }

  private function getEncodedJWTWithMissingUrl(): string {
    return 'ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJMk5EUTFNakk1TUMwME9UYzJMVFJoTWpFdFlXTXdPQzA0WVRCaU0yVTFaR0UxTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbUZ1WkhKbFFHVjRZVzF3YkdVdVkyOXRJaXdpWlcxaGFXd2lPaUpoYm1SeVpVQmxlR0Z0Y0d4bExtTnZiU0lzSW1semN5STZJa2xDVFNCQlVFa2dRMjl1Ym1WamRDSXNJblJ2YTJWdVgzUjVjR1VpT2lKMFpXMXdiM0poY25raUxDSnBZWFFpT2pFMU1qVTROakkzT0RVc0ltVjRjQ0k2TVRVeU5qRXlNVGs0TlN3aWMyTnZjR1Z6SWpwN0ltRmpkR2x2Ym5NaU9sc2lZV04wYVhaaGRHVWlYWDE5LlhBSkJENnRONlowWXpxM1kzOWkzd3JkdVBWaE5KVGRwUFhrT1dVS3dlU3M=';
  }

}
