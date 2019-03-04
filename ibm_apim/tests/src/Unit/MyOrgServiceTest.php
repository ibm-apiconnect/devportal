<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\consumerorg\ApicType\Member;
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

  private $prophet;

  protected function setup() {
    $this->prophet = new Prophet();
  }

  protected function tearDown() {
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

    $service = new MyOrgService();
    $result = $service->prepareOrgMemberForDisplay($member);

    $this->assertNotNull($result, 'nothing returned from service');
    $this->assertEquals($result['name'], 'Andre Andresson', 'unexpected name');
    $this->assertEquals($result['details'], 'andre (andre@example.com)', 'unexpected details');
    $this->assertEquals($result['state'], 'active');
    $this->assertEquals($result['id'], 'org'); // basename of member url

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

    $service = new MyOrgService();
    $result = $service->prepareOrgMemberForDisplay($member);

    $this->assertNotNull($result, 'nothing returned from service');
    $this->assertEquals($result['name'], 'andre', 'unexpected name');
    $this->assertEquals($result['details'], 'andre (andre@example.com)', 'unexpected details');

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

    $service = new MyOrgService();
    $result = $service->prepareOrgMemberForDisplay($member);

    $this->assertNotNull($result, 'nothing returned from service');
    $this->assertEquals($result['name'], 'andre', 'unexpected name');
    $this->assertEquals($result['details'], 'andre (andre@example.com)', 'unexpected details');

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

    $service = new MyOrgService();
    $result = $service->prepareOrgMemberForDisplay($member);

    $this->assertNotNull($result, 'nothing returned from service');
    $this->assertEquals($result['name'], 'andre', 'unexpected name');
    $this->assertEquals($result['details'], 'andre (andre@example.com)', 'unexpected details');

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

    $service = new MyOrgService();
    $result = $service->prepareOrgMemberForDisplay($member);

    $this->assertNotNull($result, 'nothing returned from service');
    $this->assertEquals($result['details'], 'andre', 'unexpected details');

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

    $service = new MyOrgService();
    $result = $service->prepareOrgMemberForDisplay($member);

    $this->assertNotNull($result, 'nothing returned from service');
    $this->assertEquals($result['details'], 'andre', 'unexpected details');

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

    $service = new MyOrgService();
    $result = $service->prepareOrgMemberForDisplay($member);

    $this->assertNotNull($result, 'nothing returned from service');
    $this->assertEquals($result['details'], 'andre@example.com', 'unexpected details');

  }


}
