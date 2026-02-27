<?php

namespace Drupal\Tests\ibm_csp_extension\Unit;

use Drupal\csp\Csp;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\ibm_csp_extension\EventSubscriber\CspPolicySubscriber;
use Drupal\ibm_csp_extension\Service\ApiEndpointService;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * The CSP policy subscriber.
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
    $this->cspPolicySubscriber = new CspPolicySubscriber(
      $this->apiEndpointService->reveal()
    );
  }

  /**
   * Tests the getSubscribedEvents method.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $events = CspPolicySubscriber::getSubscribedEvents();
    $this->assertArrayHasKey('csp.policy.alter', $events);
    $this->assertEquals('onCspPolicyAlter', $events['csp.policy.alter']);
  }

  /**
   * Tests the onCspPolicyAlter method with no connect-src directive and no default-src.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoConnectSrcNoDefaultSrc() {
    // Create a mock CSP policy.
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(FALSE);
    
    // No endpoints should be added since neither connect-src nor default-src contain 'self'
    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    
    // Create a mock event.
    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());
    
    // Call the method.
    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with no connect-src directive but default-src with 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoConnectSrcDefaultSrcWithSelf() {
    // Create a mock CSP policy.
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(TRUE);
    $policy->getDirective('default-src')->willReturn(["'self'", 'https://default.example.com']);
    $policy->setDirective('connect-src', ["'self'", 'https://default.example.com'])->shouldBeCalled();
    
    // Mock the API endpoint service to return some endpoints.
    $this->apiEndpointService->getCustomEndpoints()->willReturn([
      'https://api.example.com',
      'https://api2.example.com',
    ]);
    
    // Mock the policy to append each endpoint.
    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://default.example.com']);
    $policy->appendDirective('connect-src', 'https://api.example.com')->shouldBeCalled();
    $policy->appendDirective('connect-src', 'https://api2.example.com')->shouldBeCalled();
    
    // Create a mock event.
    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());
    
    // Call the method.
    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with no connect-src directive and default-src without 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoConnectSrcDefaultSrcNoSelf() {
    // Create a mock CSP policy.
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(FALSE);
    $policy->hasDirective('default-src')->willReturn(TRUE);
    $policy->getDirective('default-src')->willReturn(['https://default.example.com']);
    
    // No endpoints should be added since default-src doesn't contain 'self'
    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    
    // Create a mock event.
    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());
    
    // Call the method.
    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with an existing connect-src directive but no 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithConnectSrcNoSelf() {
    // Create a mock CSP policy.
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(['https://existing.example.com']);
    
    // No endpoints should be added since connect-src doesn't contain 'self'
    $this->apiEndpointService->getCustomEndpoints()->shouldNotBeCalled();
    
    // Create a mock event.
    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());
    
    // Call the method.
    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with an existing connect-src directive that includes 'self'.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithConnectSrcWithSelf() {
    // Create a mock CSP policy.
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://existing.example.com']);
    
    // Mock the API endpoint service to return some endpoints.
    $this->apiEndpointService->getCustomEndpoints()->willReturn([
      'https://api.example.com',
    ]);
    
    // Mock the policy to append each endpoint.
    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://existing.example.com']);
    $policy->appendDirective('connect-src', 'https://api.example.com')->shouldBeCalled();
    
    // Create a mock event.
    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());
    
    // Call the method.
    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }

  /**
   * Tests the onCspPolicyAlter method with no endpoints from the service.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithNoEndpoints() {
    // Create a mock CSP policy.
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(["'self'"]);
    
    // Mock the API endpoint service to return no endpoints.
    $this->apiEndpointService->getCustomEndpoints()->willReturn([]);
    
    // Create a mock event.
    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());
    
    // Call the method.
    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }
  
  /**
   * Tests the onCspPolicyAlter method with duplicate endpoints.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testOnCspPolicyAlterWithDuplicateEndpoints() {
    // Create a mock CSP policy.
    $policy = $this->prophesize(Csp::class);
    $policy->hasDirective('connect-src')->willReturn(TRUE);
    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://api.example.com']);
    
    // Mock the API endpoint service to return endpoints including a duplicate.
    $this->apiEndpointService->getCustomEndpoints()->willReturn([
      'https://api.example.com', // This is already in the policy
      'https://api2.example.com',
    ]);
    
    // Only the new endpoint should be added, not the duplicate
    $policy->getDirective('connect-src')->willReturn(["'self'", 'https://api.example.com']);
    $policy->appendDirective('connect-src', 'https://api.example.com')->shouldNotBeCalled();
    $policy->appendDirective('connect-src', 'https://api2.example.com')->shouldBeCalled();
    
    // Create a mock event.
    $event = $this->prophesize(PolicyAlterEvent::class);
    $event->getPolicy()->willReturn($policy->reveal());
    
    // Call the method.
    $this->cspPolicySubscriber->onCspPolicyAlter($event->reveal());
  }
}
