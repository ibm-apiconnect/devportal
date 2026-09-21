<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_csp_extension\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\csp\Csp;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\Nonce;
use Drupal\ibm_csp_extension\EventSubscriber\CspPolicySubscriber;
use Drupal\ibm_csp_extension\Service\ApiEndpointService;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for the CspPolicySubscriber class.
 *
 * @group ibm_csp_extension
 * @coversDefaultClass \Drupal\ibm_csp_extension\EventSubscriber\CspPolicySubscriber
 */
class CspPolicySubscriberTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The API endpoint service.
   *
   * @var \Drupal\ibm_csp_extension\Service\ApiEndpointService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $apiEndpointService;

  /**
   * The CSP nonce service.
   *
   * @var \Drupal\csp\Nonce|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cspNonce;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * The CSP policy subscriber under test.
   *
   * @var \Drupal\ibm_csp_extension\EventSubscriber\CspPolicySubscriber
   */
  protected $cspPolicySubscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->apiEndpointService = $this->prophesize(ApiEndpointService::class);
    $this->cspNonce = $this->prophesize(Nonce::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);

    $this->cspPolicySubscriber = new CspPolicySubscriber(
      $this->apiEndpointService->reveal(),
      $this->cspNonce->reveal(),
      $this->moduleHandler->reveal(),
    );
  }

  /**
   * Tests the getSubscribedEvents method.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $events = CspPolicySubscriber::getSubscribedEvents();
    $this->assertArrayHasKey('csp.policy_alter', $events);
    $this->assertEquals(['onCspPolicyAlter'], $events['csp.policy_alter']);
  }

  /**
   * Tests the onCspPolicyAlter method with no connect-src directive and no default-src.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoConnectSrcNoDefaultSrc() {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(FALSE);

    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with no connect-src directive but default-src with 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoConnectSrcDefaultSrcWithSelf() {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(TRUE);
    $policy->getDirective('default-src')->willReturn(["'self'", 'https://default.example.com']);
    $policy->setDirective('connect-src', ["'self'", 'https://default.example.com'])->shouldBeCalled();

    $this->apiEndpointService->getCustomEndpoints()->willReturn([
      'https://api.example.com',
      'https://api2.example.com',
    ]);

    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://default.example.com']);
    $policy->appendDirective('connect-src', 'https://api.example.com')->shouldBeCalled();
    $policy->appendDirective('connect-src', 'https://api2.example.com')->shouldBeCalled();

    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with no connect-src directive and default-src without 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoConnectSrcDefaultSrcNoSelf() {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(TRUE);
    $policy->getDirective('default-src')->willReturn(['https://default.example.com']);

    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with an existing connect-src directive but no 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithConnectSrcNoSelf() {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(['https://existing.example.com']);
    $policy->hasDirective('default-src')->willReturn(FALSE);

    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with an existing connect-src directive that includes 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithConnectSrcWithSelf() {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://existing.example.com']);

    $this->apiEndpointService->getCustomEndpoints()->willReturn([
      'https://api.example.com',
    ]);

    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://existing.example.com']);
    $policy->appendDirective('connect-src', 'https://api.example.com')->shouldBeCalled();

    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with no endpoints from the service.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoEndpoints() {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(["'self'"]);

    $this->apiEndpointService->getCustomEndpoints()->willReturn([]);
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with duplicate endpoints.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithDuplicateEndpoints() {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://api.example.com']);

    $this->apiEndpointService->getCustomEndpoints()->willReturn([
      'https://api.example.com',
      'https://api2.example.com',
    ]);

    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://api.example.com']);
    $policy->appendDirective('connect-src', 'https://api.example.com')->shouldNotBeCalled();
    $policy->appendDirective('connect-src', 'https://api2.example.com')->shouldBeCalled();

    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests that the nonce is added to style-src when ckeditor5 is active and style-src is set.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testStyleSrcNonceAddedWhenCkeditor5ActiveAndStyleSrcSet(): void {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(FALSE);

    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(TRUE);
    $this->cspNonce->getSource()->willReturn("'nonce-abc123'");
    $policy->fallbackAwareAppendIfEnabled('style-src-elem', "'nonce-abc123'")->shouldBeCalled();

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests that the nonce is added to style-src-elem when ckeditor5 is active and default-src is set.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testStyleSrcNonceAddedWhenCkeditor5ActiveAndDefaultSrcSet(): void {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(TRUE);
    $policy->getDirective('default-src')->willReturn(["'self'"]);
    $policy->setDirective('connect-src', ["'self'"])->shouldBeCalled();
    $policy->getDirective('connect-src')->willReturn(["'self'"]);

    $this->apiEndpointService->getCustomEndpoints()->willReturn([]);
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(TRUE);
    $this->cspNonce->getSource()->willReturn("'nonce-xyz789'");
    $policy->fallbackAwareAppendIfEnabled('style-src-elem', "'nonce-xyz789'")->shouldBeCalled();

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests that the nonce is NOT added when ckeditor5 is not active.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testStyleSrcNonceNotAddedWhenCkeditor5Inactive(): void {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(FALSE);

    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(FALSE);
    $this->cspNonce->getSource()->shouldNotBeCalled();
    $policy->fallbackAwareAppendIfEnabled(\Prophecy\Argument::cetera())->shouldNotBeCalled();

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests that fallbackAwareAppendIfEnabled is called for style-src-elem when ckeditor5 is active.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testStyleSrcNonceCalledWhenCkeditor5Active(): void {
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(FALSE);

    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    $this->moduleHandler->moduleExists('ckeditor5')->willReturn(TRUE);
    $this->cspNonce->getSource()->willReturn("'nonce-test'");
    $policy->fallbackAwareAppendIfEnabled('style-src-elem', "'nonce-test'")->shouldBeCalled();

    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());

    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

}
