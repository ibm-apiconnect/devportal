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

use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\MyOrgService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\MyOrgService
 *
 * NOTE - doesn't test user_picture on member because of non-testable code - see comments in service.
 *
 * @group ibm_apim
 */
class MyOrgServiceTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Drupal\Core\File\FileUrlGenerator|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fileUrlGenerator;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->fileUrlGenerator = $this->prophet->prophesize(FileUrlGenerator::class);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  public function testPrepareOrgMemberForDisplay(): void {

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setFirstname('Andre');
    $user->setLastname('Andresson');
    $user->setMail('andre@example.com');
    $user->setState('active');

    $member = new Member();
    $member->setUrl('/member/or/org');
    $member->setUser($user);
    $member->setRoleUrls([]);

    $service = new MyOrgService($this->fileUrlGenerator->reveal());
    $result = $service->prepareOrgMemberForDisplay($member);

    self::assertNotNull($result, 'nothing returned from service');
    self::assertEquals('Andre Andresson', $result['name'], 'unexpected name');
    self::assertEquals('andre (andre@example.com)', $result['details'], 'unexpected details');
    self::assertEquals('active', $result['state']);
    self::assertEquals('org', $result['id']); // basename of member url

  }

  public function testNoFirstName(): void {

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setFirstname(NULL);
    $user->setLastname('Andresson');
    $user->setMail('andre@example.com');
    $user->setState('active');

    $member = new Member();
    $member->setUrl('/member/or/org');
    $member->setUser($user);
    $member->setRoleUrls([]);

    $service = new MyOrgService($this->fileUrlGenerator->reveal());
    $result = $service->prepareOrgMemberForDisplay($member);

    self::assertNotNull($result, 'nothing returned from service');
    self::assertEquals('andre', $result['name'], 'unexpected name');
    self::assertEquals('andre (andre@example.com)', $result['details'], 'unexpected details');

  }

  public function testNoFirstOrLastName(): void {

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setFirstname(NULL);
    $user->setLastname(NULL);
    $user->setMail('andre@example.com');
    $user->setState('active');

    $member = new Member();
    $member->setUrl('/member/or/org');
    $member->setUser($user);
    $member->setRoleUrls([]);

    $service = new MyOrgService($this->fileUrlGenerator->reveal());
    $result = $service->prepareOrgMemberForDisplay($member);

    self::assertNotNull($result, 'nothing returned from service');
    self::assertEquals('andre', $result['name'], 'unexpected name');
    self::assertEquals('andre (andre@example.com)', $result['details'], 'unexpected details');

  }

  public function testEmptyFirstOrLastName(): void {

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setFirstname('');
    $user->setLastname('');
    $user->setMail('andre@example.com');
    $user->setState('active');

    $member = new Member();
    $member->setUrl('/member/or/org');
    $member->setUser($user);
    $member->setRoleUrls([]);

    $service = new MyOrgService($this->fileUrlGenerator->reveal());
    $result = $service->prepareOrgMemberForDisplay($member);

    self::assertNotNull($result, 'nothing returned from service');
    self::assertEquals('andre', $result['name'], 'unexpected name');
    self::assertEquals('andre (andre@example.com)', $result['details'], 'unexpected details');

  }

  public function testNoEmail(): void {

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setFirstname('Andre');
    $user->setLastname('Andresson');
    $user->setMail(NULL);
    $user->setState('active');

    $member = new Member();
    $member->setUrl('/member/or/org');
    $member->setUser($user);
    $member->setRoleUrls([]);

    $service = new MyOrgService($this->fileUrlGenerator->reveal());
    $result = $service->prepareOrgMemberForDisplay($member);

    self::assertNotNull($result, 'nothing returned from service');
    self::assertEquals('andre', $result['details'], 'unexpected details');

  }

  public function testEmptyEmail(): void {

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setFirstname('Andre');
    $user->setLastname('Andresson');
    $user->setMail('');
    $user->setState('active');

    $member = new Member();
    $member->setUrl('/member/or/org');
    $member->setUser($user);
    $member->setRoleUrls([]);

    $service = new MyOrgService($this->fileUrlGenerator->reveal());
    $result = $service->prepareOrgMemberForDisplay($member);

    self::assertNotNull($result, 'nothing returned from service');
    self::assertEquals('andre', $result['details'], 'unexpected details');

  }

  public function testMatchingEmailAndUsername(): void {

    $user = new ApicUser();
    $user->setUsername('andre@example.com');
    $user->setFirstname('Andre');
    $user->setLastname('Andresson');
    $user->setMail('andre@example.com');
    $user->setState('active');

    $member = new Member();
    $member->setUrl('/member/or/org');
    $member->setUser($user);
    $member->setRoleUrls([]);

    $service = new MyOrgService($this->fileUrlGenerator->reveal());
    $result = $service->prepareOrgMemberForDisplay($member);

    self::assertNotNull($result, 'nothing returned from service');
    self::assertEquals('andre@example.com', $result['details'], 'unexpected details');

  }


}
