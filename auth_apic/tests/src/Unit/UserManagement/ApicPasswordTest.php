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

namespace Drupal\Tests\auth_apic\Unit {

  use Drupal\auth_apic\JWTToken;
  use Drupal\auth_apic\UserManagement\ApicPasswordService;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Field\FieldItemList;
  use Drupal\Core\Messenger\Messenger;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\Rest\RestResponse;
  use Drupal\ibm_apim\Service\APIMServer;
  use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
  use Drupal\Tests\auth_apic\Unit\UserManagement\AuthApicUserManagementBaseTestClass;
  use Drupal\user\Entity\User;
  use Drupal\user\UserStorageInterface;
  use Prophecy\Argument;
  use Prophecy\Prophet;
  use Psr\Log\LoggerInterface;


  /**
   * @group auth_apic
   */
  class ApicPasswordTest extends AuthApicUserManagementBaseTestClass {

    /**
     * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $mgmtServer;

    /**
     * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $messenger;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $apicUserStorage;

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $entityTypeManager;

    /**
     * @var \Drupal\user\UserStorageInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $drupalUserStorage;

    /**
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    protected function setup(): void {
      $this->prophet = new Prophet();
      $this->mgmtServer = $this->prophet->prophesize(APIMServer::class);
      $this->messenger = $this->prophet->prophesize(Messenger::class);
      $this->logger = $this->prophet->prophesize(LoggerInterface::class);
      $this->apicUserStorage = $this->prophet->prophesize(ApicUserStorageInterface::class);

      $this->drupalUserStorage = $this->prophet->prophesize(UserStorageInterface::class);
      $this->entityTypeManager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
      $this->entityTypeManager->getStorage('user')->willReturn($this->drupalUserStorage->reveal());

    }

    protected function tearDown(): void {
      $this->prophet->checkPredictions();
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testChangePassword(): void {
      $goodResponse = new RestResponse();
      $goodResponse->setCode(204);

      $account = $this->createAccountStub();

      $this->mgmtServer->changePassword('oldun', 'newun')->willReturn($goodResponse);

      $this->logger->notice('changePassword called for @username', ['@username' => 'andre'])->shouldBeCalled();
      $this->logger->notice('Password changed successfully.')->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = $this->getApicPasswordService();
      $result = $service->changePassword($account, 'oldun', 'newun');
      self::assertTrue($result, 'positive result expected from change password.');
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testChangePasswordFail(): void {
      $badResponse = new RestResponse();
      $badResponse->setCode(400);

      $account = $this->createAccountStub();

      $this->mgmtServer->changePassword('oldun', 'newun')->willReturn($badResponse);
      $this->logger->notice('changePassword called for @username', ['@username' => 'andre'])->shouldBeCalled();
      $this->logger->error('Password change failure.')->shouldBeCalled();

      $service = $this->getApicPasswordService();

      $result = $service->changePassword($account, 'oldun', 'newun');
      self::assertFalse($result, 'negative result expected from change password.');
    }

    /**
     * Test successful (204) response from management server.
     *
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testResetPasswordSuccess(): void {
      $jwt = $this->createJWT();
      $password = 'abc123';

      $response = new RestResponse();
      $response->setCode(204);

      $this->mgmtServer->resetPassword($jwt, $password)->willReturn($response);
      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = $this->getApicPasswordService();
      $rc = $service->resetPassword($jwt, $password);
      self::assertEquals(204, $rc);
    }

    /**
     * Test with non-204 response from management server.
     *
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testResetPasswordFail(): void {
      $obj = $this->createJWT();
      $password = 'abc123';

      $response = new RestResponse();
      $response->setCode(400);
      $response->setErrors(['one', 'two']);

      $this->mgmtServer->resetPassword($obj, $password)->willReturn($response);
      $this->logger->notice('Error resetting password.')->shouldBeCalled();
      $this->logger->error('Reset password response: @result', Argument::type('array'))->shouldBeCalled();
      $this->messenger->addError('Error resetting password. Contact the system administrator.')->shouldBeCalled();
      $this->messenger->addError('Error detail:')->shouldBeCalled();
      $this->messenger->addError('  one')->shouldBeCalled();
      $this->messenger->addError('  two')->shouldBeCalled();

      $service = $this->getApicPasswordService();
      $rc = $service->resetPassword($obj, $password);

      self::assertEquals(400, $rc);

    }

    public function testLookupAccountAdminByName(): void {

      $admin = $this->prophet->prophesize(User::class);
      $admin->getAccountName()->willReturn('admin');
      $this->drupalUserStorage->load(1)->willReturn($admin->reveal());

      $this->logger->notice('lookUpAccount: identified user as admin account')->shouldBeCalled();
      $this->apicUserStorage->loadUserByEmailAddress(Argument::any())->shouldNotBeCalled();
      $this->apicUserStorage->load(Argument::any())->shouldNotBeCalled();

      $service = $this->getApicPasswordService();
      $account = $service->lookupUpAccount('admin');

      self::assertNotNull($account);
    }

    public function testLookupAccountAdminByEmail(): void {

      $admin = $this->prophet->prophesize(User::class);
      $admin->getAccountName()->willReturn('admin');

      $mail_field = $this->prophet->prophesize(FieldItemList::class);
      $mail_field->getValue()->willReturn([['value' => 'admin@example.com']]);
      $admin->get('mail')->willReturn($mail_field->reveal());
      $this->drupalUserStorage->load(1)->willReturn($admin->reveal());

      $this->logger->notice('lookUpAccount: identified user as admin account')->shouldBeCalled();
      $this->apicUserStorage->loadUserByEmailAddress(Argument::any())->shouldNotBeCalled();
      $this->apicUserStorage->load(Argument::any())->shouldNotBeCalled();

      $service = $this->getApicPasswordService();
      $account = $service->lookupUpAccount('admin@example.com');

      self::assertNotNull($account);
    }

    public function testLookupAccountAndreByName(): void {

      $admin = $this->prophet->prophesize(User::class);
      $admin->getAccountName()->willReturn('admin');

      $mail_field = $this->prophet->prophesize(FieldItemList::class);
      $mail_field->getValue()->willReturn([['value' => 'admin@example.com']]);
      $admin->get('mail')->willReturn($mail_field->reveal());
      $this->drupalUserStorage->load(1)->willReturn($admin->reveal());

      $lookup_user = new ApicUser();
      $lookup_user->setUsername('andre');
      $lookup_user->setApicUserRegistryUrl('/reg/lur1');

      $this->logger->notice('lookUpAccount: identified user as admin account')->shouldNotBeCalled();
      $this->apicUserStorage->loadUserByEmailAddress('andre')->willReturn(NULL);
      $this->apicUserStorage->load($lookup_user)->willReturn($this->prophet->prophesize(User::class)->reveal());

      $service = $this->getApicPasswordService();
      $account = $service->lookupUpAccount('andre', '/reg/lur1');

      self::assertNotNull($account);
    }

    public function testLookupAccountAndreByMailAddress(): void {

      $admin = $this->prophet->prophesize(User::class);
      $admin->getAccountName()->willReturn('admin');

      $mail_field = $this->prophet->prophesize(FieldItemList::class);
      $mail_field->getValue()->willReturn([['value' => 'admin@example.com']]);
      $admin->get('mail')->willReturn($mail_field->reveal());
      $this->drupalUserStorage->load(1)->willReturn($admin->reveal());


      $this->logger->notice('lookUpAccount: identified user as admin account')->shouldNotBeCalled();
      $this->apicUserStorage->loadUserByEmailAddress('andre@example.com')->willReturn($this->prophet->prophesize(User::class)->reveal());
      $this->apicUserStorage->load(Argument::any())->shouldNotBeCalled();

      $service = $this->getApicPasswordService();
      $account = $service->lookupUpAccount('andre@example.com', '/reg/lur1');

      self::assertNotNull($account);
    }

    public function testLookupAccountNotKnownByName(): void {

      $admin = $this->prophet->prophesize(User::class);
      $admin->getAccountName()->willReturn('admin');

      $mail_field = $this->prophet->prophesize(FieldItemList::class);
      $mail_field->getValue()->willReturn([['value' => 'admin@example.com']]);
      $admin->get('mail')->willReturn($mail_field->reveal());
      $this->drupalUserStorage->load(1)->willReturn($admin->reveal());

      $lookup_user = new ApicUser();
      $lookup_user->setUsername('notknown');
      $lookup_user->setApicUserRegistryUrl('/reg/lur1');

      $this->logger->notice('lookUpAccount: identified user as admin account')->shouldNotBeCalled();
      $this->apicUserStorage->loadUserByEmailAddress('notknown')->willReturn(NULL);
      $this->apicUserStorage->load($lookup_user)->willReturn(NULL);

      $service = $this->getApicPasswordService();
      $account = $service->lookupUpAccount('notknown', '/reg/lur1');

      self::assertNull($account);
    }

    public function testLookupAccountNotKnownByMailAddress(): void {

      $admin = $this->prophet->prophesize(User::class);
      $admin->getAccountName()->willReturn('admin');

      $mail_field = $this->prophet->prophesize(FieldItemList::class);
      $mail_field->getValue()->willReturn([['value' => 'admin@example.com']]);
      $admin->get('mail')->willReturn($mail_field->reveal());
      $this->drupalUserStorage->load(1)->willReturn($admin->reveal());


      $this->logger->notice('lookUpAccount: identified user as admin account')->shouldNotBeCalled();
      $this->apicUserStorage->loadUserByEmailAddress('notknown@example.com')->willReturn(NULL);
      $this->apicUserStorage->load(Argument::any())->willReturn(NULL);

      $service = $this->getApicPasswordService();
      $account = $service->lookupUpAccount('notknown@example.com', '/reg/lur1');

      self::assertNull($account);
    }

    private function createJWT(): JWTToken {
      $jwt = new JWTToken();
      $jwt->setUrl('j/w/t');
      return $jwt;
    }

    /**
     * @return \Drupal\auth_apic\UserManagement\ApicPasswordService
     */
    private function getApicPasswordService(): ApicPasswordService {
      return new ApicPasswordService($this->mgmtServer->reveal(),
        $this->messenger->reveal(),
        $this->logger->reveal(),
        $this->apicUserStorage->reveal(),
        $this->entityTypeManager->reveal());
    }

  }

}
