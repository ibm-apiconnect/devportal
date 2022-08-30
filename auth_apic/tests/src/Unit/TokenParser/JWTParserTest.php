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

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\JWTParser;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Drupal\Core\State\State;
use Prophecy\Argument;

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

  /**
   * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $mgmtServer;
  /**
   * @var \Drupal\Core\State\State|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $state;
  /**
   * @var \Firebase\JWT\JWT|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $jwt;
  /**
   * @var \Firebase\JWT\JWK|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $jwk;




  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    $this->utils = $this->prophet->prophesize(\Drupal\ibm_apim\Service\Utils::class);
    $this->mgmtServer = $this->prophet->prophesize(\Drupal\ibm_apim\Service\APIMServer::class);
    $this->state = $this->prophet->prophesize(\Drupal\Core\State\State::class);
    $this->jwt = $this->prophet->prophesize(\Firebase\JWT\JWT::class);
    $this->jwk = $this->prophet->prophesize(\Firebase\JWT\JWK::class);
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
    $publicKeyJWK = [unserialize(base64_decode(getenv('JWK_B64_KEY')))];
    $this->state->get('ibm_apim.apim_keys')->willReturn(['keys' => $publicKeyJWK]);
    $this->mgmtServer->updateKeys(Argument::any())->shouldNotBeCalled();
    $this->utils->base64_url_decode($this->getValidHeaders())->willReturn(\base64_decode($this->getValidHeaders()));
    $this->utils->base64_url_decode($this->getValidPayload())->willReturn(\base64_decode($this->getValidPayload()));
    $this->utils->base64_url_decode($this->getValidSignature())->willReturn(\base64_decode($this->getValidSignature()));

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal(), $this->mgmtServer->reveal(), $this->state->reveal());
    $result = $parser->parse($this->getValidEncodedJWT());
    $this->mgmtServer->updateKeys(Argument::any())->shouldNotBeCalled();
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

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal(), $this->mgmtServer->reveal(), $this->state->reveal());
    $result = $parser->parse($this->getEncodedJWTWithMissingHeaders());

    self::assertNull($result, 'Result from invalid parse is not NULL');
  }

  /**
   * Invalid - missing URL in token.
   *
   * @throws \Exception
   */
  public function testMissingUrlParse(): void {
    $publicKeyJWK = [unserialize(base64_decode(getenv('JWK_B64_KEY')))];
    $this->state->get('ibm_apim.apim_keys')->willReturn(['keys' => $publicKeyJWK]);

    $this->logger->error('payload.scopes.url not available in activation JWT')->shouldBeCalled();
    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal(), $this->mgmtServer->reveal(), $this->state->reveal());
    $result = $parser->parse($this->getEncodedJWTWithMissingUrl());
    self::assertNull($result, 'Unexpected response when parsing a token with missing URL');

  }


  /**
   * Parse NULL.
   *
   */
  public function testParseNULL(): void {
    $this->expectException(\Exception::class);

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal(), $this->mgmtServer->reveal(), $this->state->reveal());
    $parser->parse(NULL);
  }

  public function testIsBase64() {

    $parser = new JWTParser($this->logger->reveal(), $this->utils->reveal(), $this->mgmtServer->reveal(), $this->state->reveal());
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
   *   "jti": "07dd3b04-98b8-412b-8f74-311599cae94b",
   *   "namespace": "cloud",
   *   "aud": "n/a",
   *   "sub": "n/a",
   *   "email": "andre.owner.invite@example.com",
   *   "iss": "IBM API Connect",
   *   "token_type": "invitation",
   *   "iat": 1559309290,
   *   "exp": 9869582090,
   *   "scopes": {
   *     "invitation": "/consumer-api/org-invitations/a9571903-854b-4a6e-863f-8647f1329464",
   *     "url": "/consumer-api/org-invitations/a9571903-854b-4a6e-863f-8647f1329464",
   *     "actions": [
   *       "register",
   *       "accept"
   *     ],
   *     "realm": "consumer:0c0ae3f2-b474-4034-837b-a24c6f46d5a6:07e99a47-ad32-4b27-be28-ee0d219c296f"
    *  }
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
    return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImNjOWU5ZGVjLTI2M2EtNDNjMi05YWY5LTRlNzhlZDhjNDExZCJ9.eyJqdGkiOiIwN2RkM2IwNC05OGI4LTQxMmItOGY3NC0zMTE1OTljYWU5NGIiLCJuYW1lc3BhY2UiOiJjbG91ZCIsImF1ZCI6Im4vYSIsInN1YiI6Im4vYSIsImVtYWlsIjoiYW5kcmUub3duZXIuaW52aXRlQGV4YW1wbGUuY29tIiwiaXNzIjoiSUJNIEFQSSBDb25uZWN0IiwidG9rZW5fdHlwZSI6Imludml0YXRpb24iLCJpYXQiOjE1NTkzMDkyOTAsImV4cCI6OTg2OTU4MjA5MCwic2NvcGVzIjp7Imludml0YXRpb24iOiIvY29uc3VtZXItYXBpL29yZy1pbnZpdGF0aW9ucy9hOTU3MTkwMy04NTRiLTRhNmUtODYzZi04NjQ3ZjEzMjk0NjQiLCJ1cmwiOiIvY29uc3VtZXItYXBpL29yZy1pbnZpdGF0aW9ucy9hOTU3MTkwMy04NTRiLTRhNmUtODYzZi04NjQ3ZjEzMjk0NjQiLCJhY3Rpb25zIjpbInJlZ2lzdGVyIiwiYWNjZXB0Il0sInJlYWxtIjoiY29uc3VtZXI6MGMwYWUzZjItYjQ3NC00MDM0LTgzN2ItYTI0YzZmNDZkNWE2OjA3ZTk5YTQ3LWFkMzItNGIyNy1iZTI4LWVlMGQyMTljMjk2ZiJ9fQ.eNhXCf9MMFVvX6j6MZibPJEnRJ8nQquzKY_bZfU5B3OHzV1c16e91sZiYYGsw796Fs4RNz_pa5__UmP8oh9A3i2lPBXKZ84uLfygJWRTItx5aw_QvlCPUCusKbC7tawsEwqcufC1JeWglEdPuicHF3t3A9rP1U30iJBAhzh8iOktv945RFfzIoVnWyJh1eqvqv3EcJPijHGvPhq2kzSSbRE7T6RDp9JPmXTSlec3JH95GZ57jq9F5NbN-8gDodYVVvpK4ndfiTULkeuRzOpbDXM3HI0ECGmM7CBIJ7ztA7T363zhMiKsgWNNtcbjhtLmEmz5n678fHpaLQQfRZw6hQ';
    }

  /**
   * Get base64 encoded version of jwt returned from getValidJWT()
   *
   * @return string
   */
  private function getValidEncodedJWT(): string {
    return 'ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNkdVpYSXVhVzUyYVhSbFFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW1sdWRtbDBZWFJwYjI0aUxDSnBZWFFpT2pFMU5Ua3pNRGt5T1RBc0ltVjRjQ0k2T1RnMk9UVTRNakE1TUN3aWMyTnZjR1Z6SWpwN0ltbHVkbWwwWVhScGIyNGlPaUl2WTI5dWMzVnRaWEl0WVhCcEwyOXlaeTFwYm5acGRHRjBhVzl1Y3k5aE9UVTNNVGt3TXkwNE5UUmlMVFJoTm1VdE9EWXpaaTA0TmpRM1pqRXpNamswTmpRaUxDSjFjbXdpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WnkxcGJuWnBkR0YwYVc5dWN5OWhPVFUzTVRrd015MDROVFJpTFRSaE5tVXRPRFl6WmkwNE5qUTNaakV6TWprME5qUWlMQ0poWTNScGIyNXpJanBiSW5KbFoybHpkR1Z5SWl3aVlXTmpaWEIwSWwwc0luSmxZV3h0SWpvaVkyOXVjM1Z0WlhJNk1HTXdZV1V6WmpJdFlqUTNOQzAwTURNMExUZ3pOMkl0WVRJMFl6Wm1ORFprTldFMk9qQTNaVGs1WVRRM0xXRmtNekl0TkdJeU55MWlaVEk0TFdWbE1HUXlNVGxqTWprMlppSjlmUS5lTmhYQ2Y5TU1GVnZYNmo2TVppYlBKRW5SSjhuUXF1ektZX2JaZlU1QjNPSHpWMWMxNmU5MXNaaVlZR3N3Nzk2RnM0Uk56X3BhNV9fVW1QOG9oOUEzaTJsUEJYS1o4NHVMZnlnSldSVEl0eDVhd19RdmxDUFVDdXNLYkM3dGF3c0V3cWN1ZkMxSmVXZ2xFZFB1aWNIRjN0M0E5clAxVTMwaUpCQWh6aDhpT2t0djk0NVJGZnpJb1ZuV3lKaDFlcXZxdjNFY0pQaWpIR3ZQaHEya3pTU2JSRTdUNlJEcDlKUG1YVFNsZWMzSkg5NUdaNTdqcTlGNU5iTi04Z0RvZFlWVnZwSzRuZGZpVFVMa2V1UnpPcGJEWE0zSEkwRUNHbU03Q0JJSjd6dEE3VDM2M3poTWlLc2dXTk50Y2JqaHRMbUVtejVuNjc4ZkhwYUxRUWZSWnc2aFE=';
  }


  // The following functions just return specific parts of the valid token.
  private function getValidHeaders(): string {
    return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImNjOWU5ZGVjLTI2M2EtNDNjMi05YWY5LTRlNzhlZDhjNDExZCJ9';
  }


  private function getValidPayload(): string {
    return 'eyJqdGkiOiIwN2RkM2IwNC05OGI4LTQxMmItOGY3NC0zMTE1OTljYWU5NGIiLCJuYW1lc3BhY2UiOiJjbG91ZCIsImF1ZCI6Im4vYSIsInN1YiI6Im4vYSIsImVtYWlsIjoiYW5kcmUub3duZXIuaW52aXRlQGV4YW1wbGUuY29tIiwiaXNzIjoiSUJNIEFQSSBDb25uZWN0IiwidG9rZW5fdHlwZSI6Imludml0YXRpb24iLCJpYXQiOjE1NTkzMDkyOTAsImV4cCI6OTg2OTU4MjA5MCwic2NvcGVzIjp7Imludml0YXRpb24iOiIvY29uc3VtZXItYXBpL29yZy1pbnZpdGF0aW9ucy9hOTU3MTkwMy04NTRiLTRhNmUtODYzZi04NjQ3ZjEzMjk0NjQiLCJ1cmwiOiIvY29uc3VtZXItYXBpL29yZy1pbnZpdGF0aW9ucy9hOTU3MTkwMy04NTRiLTRhNmUtODYzZi04NjQ3ZjEzMjk0NjQiLCJhY3Rpb25zIjpbInJlZ2lzdGVyIiwiYWNjZXB0Il0sInJlYWxtIjoiY29uc3VtZXI6MGMwYWUzZjItYjQ3NC00MDM0LTgzN2ItYTI0YzZmNDZkNWE2OjA3ZTk5YTQ3LWFkMzItNGIyNy1iZTI4LWVlMGQyMTljMjk2ZiJ9fQ';
  }

  private function getValidSignature(): string {
    return 'eNhXCf9MMFVvX6j6MZibPJEnRJ8nQquzKY_bZfU5B3OHzV1c16e91sZiYYGsw796Fs4RNz_pa5__UmP8oh9A3i2lPBXKZ84uLfygJWRTItx5aw_QvlCPUCusKbC7tawsEwqcufC1JeWglEdPuicHF3t3A9rP1U30iJBAhzh8iOktv945RFfzIoVnWyJh1eqvqv3EcJPijHGvPhq2kzSSbRE7T6RDp9JPmXTSlec3JH95GZ57jq9F5NbN-8gDodYVVvpK4ndfiTULkeuRzOpbDXM3HI0ECGmM7CBIJ7ztA7T363zhMiKsgWNNtcbjhtLmEmz5n678fHpaLQQfRZw6hQ';
  }

  private function getValidUrl(): string {
    return '/org-invitations/a9571903-854b-4a6e-863f-8647f1329464';
  }

  // Invalid tokens... for specific tests
  private function getEncodedJWTWithMissingHeaders(): string {
    return 'ZXcwS0lDQWlhblJwSWpvZ0lqWTBORFV5TWprd0xUUTVOell0TkdFeU1TMWhZekE0TFRoaE1HSXpaVFZrWVRVd1l5SXNEUW9nSUNKdVlXMWxjM0JoWTJVaU9pQWlZMnh2ZFdRaUxBMEtJQ0FpWVhWa0lqb2dJbTR2WVNJc0RRb2dJQ0p6ZFdJaU9pQWlZVzVrY21WQVpYaGhiWEJzWlM1amIyMGlMQTBLSUNBaVpXMWhhV3dpT2lBaVlXNWtjbVZBWlhoaGJYQnNaUzVqYjIwaUxBMEtJQ0FpYVhOeklqb2dJa2xDVFNCQlVFa2dRMjl1Ym1WamRDSXNEUW9nSUNKMGIydGxibDkwZVhCbElqb2dJblJsYlhCdmNtRnllU0lzRFFvZ0lDSnBZWFFpT2lBeE5USTFPRFl5TnpnMUxBMEtJQ0FpWlhod0lqb2dNVFV5TmpFeU1UazROU3dOQ2lBZ0luTmpiM0JsY3lJNklIc05DaUFnSUNBaWRYSnNJam9nSWk5amIyNXpkVzFsY2kxaGNHa3ZZV04wYVhaaGRHVS9ZV04wYVhaaGRHbHZibDlwWkQwM1lUVmhNelV4TlMxaE9HTXhMVFJrWWpZdE9XSmxNaTFrWkRNNU16STJObVJtTnpnaUxBMEtJQ0FnSUNKaFkzUnBiMjV6SWpvZ1d3MEtJQ0FnSUNBZ0ltRmpkR2wyWVhSbElnMEtJQ0FnSUYwTkNpQWdmUTBLZlE9PS5lUFpYTGRQakdoWU9jV2pGTGZnSnZLZFhOaWNqSnV2bDl2NHdwdC15YkQw';
  }

  private function getEncodedJWTWithMissingUrl(): string {
    return 'ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNkdVpYSXVhVzUyYVhSbFFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW1sdWRtbDBZWFJwYjI0aUxDSnBZWFFpT2pFMU5Ua3pNRGt5T1RBc0ltVjRjQ0k2T1RnMk9UVTRNakE1TUN3aWMyTnZjR1Z6SWpwN0ltbHVkbWwwWVhScGIyNGlPaUl2WTI5dWMzVnRaWEl0WVhCcEwyOXlaeTFwYm5acGRHRjBhVzl1Y3k5aE9UVTNNVGt3TXkwNE5UUmlMVFJoTm1VdE9EWXpaaTA0TmpRM1pqRXpNamswTmpRaUxDSmhZM1JwYjI1eklqcGJJbkpsWjJsemRHVnlJaXdpWVdOalpYQjBJbDBzSW5KbFlXeHRJam9pWTI5dWMzVnRaWEk2TUdNd1lXVXpaakl0WWpRM05DMDBNRE0wTFRnek4ySXRZVEkwWXpabU5EWmtOV0UyT2pBM1pUazVZVFEzTFdGa016SXROR0l5TnkxaVpUSTRMV1ZsTUdReU1UbGpNamsyWmlKOWZRLlJUa081UjYwSVVpbXd5ZFJqbURqSXVJQ2VnU2lvb0FmZDFGVThneVBFaW0tVW5xNVR0emxPUk9JbGkxdmhOYnVnTHRVZlpVVmZiMjJuVzh4TVY1S09uMkpaclRLamphaW1wQndobTRydldUd1lnYVk4T2dwUkd6Q1BTVWJ6VHRod2RsSU5rZmNUenJhN3B4ekxEV2dYSTRGblhnQklZMWI4TU1LRkstNTN3SEFtQV82XzFJOW9seWFYRHk4MjNKVmJXX2YtOVdUTEItbmZTT05YQzJFZE1PTGFCMXN3Qk1zWUlISXpaS1pNbmRnRTBReVFFRWI0bGlrR0NfUEdxaW80Z3drUmxBdGZ5eW92bmMyaElHSE9pTXNvR3Y5WHcyaklmd3BQX1NIWXM5bmxETExEVjlYeVZ0X0cxWV9nQ0FfVGFmbkdvZzJCY2M2UXZacUJMZFdyQQ==';
  }

}
