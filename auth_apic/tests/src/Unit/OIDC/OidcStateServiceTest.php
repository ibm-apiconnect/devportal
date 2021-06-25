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

use Drupal\auth_apic\Service\OidcStateService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\auth_apic\Service\OidcStateService
 *
 * @group auth_apic
 */
class OidcStateServiceTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Drupal\Core\State\StateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $state;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\encrypt\EncryptServiceInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptService;

  /**
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptionProfileManager;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * @var \Drupal\Component\Datetime\Time|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $time;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->state = $this->prophet->prophesize('Drupal\Core\State\StateInterface');
    $this->logger = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    $this->encryptService = $this->prophet->prophesize('Drupal\encrypt\EncryptServiceInterface');
    $this->encryptionProfileManager = $this->prophet->prophesize('Drupal\encrypt\EncryptionProfileManagerInterface');
    $this->session = $this->prophet->prophesize('Symfony\Component\HttpFoundation\Session\Session');
    $this->time = $this->prophet->prophesize('Drupal\Component\Datetime\Time');
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * store($data) tests
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function testStoreValid(): void {

    $data = ['registry_url' => '/registry/url'];
    $key = '12345678:/registry/url:testsession123';
    $encrypted_key = 'ENCRYPTED_KEY';
    $encrypted_data = 'ENCRYPTED_DATA';

    $state_key = 'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';
    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();

    $initial_state = ['one' => []];
    // note - storing with unencrypted key as this is within the service.
    $updated_state = \array_merge($initial_state, [$key => $encrypted_data]);

    // required for the key.
    $this->session->getId()->willReturn('testsession123');
    $this->time->getCurrentTime()->willReturn('12345678');

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);
    $this->encryptService->encrypt($key, $encryptionProfile)->willReturn($encrypted_key)->shouldBeCalled();
    $this->encryptService->encrypt(serialize($data), $encryptionProfile)->willReturn($encrypted_data)->shouldBeCalled();
    $this->state->set($state_key, serialize($updated_state))->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $key = $service->store($data);

    self::assertNotNull($key, 'expected a key to be returned from store()');
    self::assertEquals('ENCRYPTED_KEY', $key, 'unexpected encrypted key returned.');

  }


  /**
   * get(string $key) tests
   */
  public function testGetValid(): void {
    $encrypted_key = 'ENCRYPTED_KEY';
    $encrypted_data = 'ENCRYPTED_DATA';
    $initial_state = ['one' => $encrypted_data];
    $initial_state_value_decrypted = ['registry_url' => '/registry/url'];
    $state_key = 'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';

    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->encryptService->decrypt($encrypted_key, $encryptionProfile)->willReturn('one')->shouldBeCalled();
    $this->encryptService->decrypt($encrypted_data, $encryptionProfile)
      ->willReturn(serialize($initial_state_value_decrypted))
      ->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $data = $service->get($encrypted_key);
    self::assertNotNull($data, 'expected data to be returned from get()');
    self::assertEquals($data, $initial_state_value_decrypted, 'unexpected data returned from get()');
  }


  /**
   * delete(string $key) tests
   */
  public function testDeleteValid(): void {
    $encrypted_key = 'ENCRYPTED_KEY';
    $initial_state = ['one' => 'encrypted_data'];
    $updated_state = [];

    $state_key = 'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';

    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->encryptService->decrypt($encrypted_key, $encryptionProfile)->willReturn('one')->shouldBeCalled();

    $this->state->set($state_key, serialize($updated_state))->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $result = $service->delete($encrypted_key);

    self::assertTrue($result, 'expected success from delete()');

  }


  /**
   * prune() tests
   */
  public function testPruneValid(): void {
    $initial_state = ['one' => 'encrypted_data', 'two' => 'more_encrypted_data'];
    $updated_state = ['two' => 'more_encrypted_data'];
    $data1 = ['registry_url' => '/user/reg1', 'created' => 1];
    $data2 = ['registry_url' => '/user/reg2', 'created' => 3];
    $state_key = 'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';

    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->time->getCurrentTime()->willReturn(86400 + 2);
    $this->encryptService->decrypt('encrypted_data', $encryptionProfile)->willReturn($data1);
    $this->encryptService->decrypt('more_encrypted_data', $encryptionProfile)->willReturn($data2);

    $this->state->set($state_key, serialize($updated_state))->shouldBeCalled();

    $service = $this->getServiceUnderTest();
    $count = $service->prune();

    self::assertEquals(1, $count, 'unexpected number of items pruned from state');
  }


  /**
   * @return \Drupal\auth_apic\Service\OidcStateService
   */
  private function getServiceUnderTest(): OidcStateService {
    return new OidcStateService($this->state->reveal(),
      $this->encryptService->reveal(),
      $this->encryptionProfileManager->reveal(),
      $this->logger->reveal(),
      $this->session->reveal(),
      $this->time->reveal());
  }


}
