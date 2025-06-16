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

namespace Drupal\ibm_apim\EventSubscriber;

if (!function_exists(__NAMESPACE__ . '\\base_path')) {
  function base_path() {
    return '/';
  }
}

namespace Drupal\Tests\ibm_apim\Unit\EventSubscriber;

use Drupal\ibm_apim\EventSubscriber\UserCheckSubscriber;
use Drupal\Core\Messenger\MessengerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RequestContext;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Path\CurrentPathStack;

/**
 * @coversDefaultClass \Drupal\ibm_apim\EventSubscriber\UserCheckSubscriber
 * @group ibm_apim
 */
class UserCheckSubscriberTest extends TestCase {

  /**
   * @var \Psr\Log\LoggerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $container;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->container = new ContainerBuilder();

    $this->requestStack = new RequestStack();
    $request = new Request();
    $this->requestStack->push($request);

    $this->container->set('current_user', $this->createMock(AccountInterface::class));
    $this->container->set('current_route_match', $this->createMock(RouteMatchInterface::class));
    $this->container->set('request_stack', $this->requestStack);

    $this->container->set('url_generator', $this->createMock(UrlGeneratorInterface::class));
    $this->container->set('router.request_context', new RequestContext());
    $this->container->set('path.current', $this->createMock(CurrentPathStack::class));

    \Drupal::setContainer($this->container);
  }

  public static function setUpBeforeClass(): void {
    if (!class_exists('Drupal\\Core\\Url', false)) {
      eval('
        namespace Drupal\Core;
        class Url {
          public static function fromRoute($route_name, $parameters = [], $options = []) {
            $destination = $options["query"]["destination"] ?? "/";
            return new class($destination) {
              private $destination;
              public function __construct($destination) {
                $this->destination = $destination;
              }
              public function toString() {
                return "/user/login?destination=" . urlencode($this->destination);
              }
            };
          }
        }
      ');
    }
  }

  /**
   * @throws \Exception
   */
  public function testRedirectsUnauthenticatedJsonRequest() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(false);

    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteObject')->willReturn(null);

    $this->container->set('current_user', $account);
    $this->container->set('current_route_match', $routeMatch);

    $request = Request::create('/api-endpoint', 'GET', [], [], [], [
      'HTTP_ACCEPT' => 'application/json',
      'HTTP_REFERER' => 'https://example.com/original-page'
    ]);
    $this->requestStack->pop();
    $this->requestStack->push($request);

    $kernel = $this->createMock(HttpKernelInterface::class);
    $response = new Response();
    $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $subscriber = new UserCheckSubscriber($this->logger, $this->messenger);
    $subscriber->userCheck($event);

    $newResponse = $event->getResponse();
    $this->assertInstanceOf(RedirectResponse::class, $newResponse);
    $this->assertEquals(307, $newResponse->getStatusCode());
    $this->assertStringContainsString('/user/login', $newResponse->getTargetUrl());
    $this->assertStringContainsString('destination=%2Foriginal-page', $newResponse->getTargetUrl());
    $this->assertTrue($newResponse->headers->has('X-UserCheck-Redirect'));
  }

  /**
   * @throws \Exception
   */
  public function testDoesNotRedirectForHtmlRequest() {
    $request = new Request();
    $request->headers->set('Accept', 'text/html');
    $request->headers->set('referer', '/home');
    $this->requestStack->pop();
    $this->requestStack->push($request);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(false);

    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteObject')->willReturn(null);

    $this->container->set('current_user', $account);
    $this->container->set('current_route_match', $routeMatch);

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      new Response()
    );

    $subscriber = new UserCheckSubscriber($this->logger, $this->messenger);
    $subscriber->userCheck($event);

    $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
  }

  /**
   * @throws \Exception
   */
  public function testDoesNotRedirectIfAuthenticated() {
    $request = new Request();
    $request->headers->set('Accept', 'application/json');
    $this->requestStack->pop();
    $this->requestStack->push($request);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(true);

    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteObject')->willReturn(null);

    $this->container->set('current_user', $account);
    $this->container->set('current_route_match', $routeMatch);

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      new Response()
    );

    $subscriber = new UserCheckSubscriber($this->logger, $this->messenger);
    $subscriber->userCheck($event);

    $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
  }

  /**
   * @throws \Exception
   */
  public function testDoesNotRedirectIfDestinationSet() {
    $request = new Request(['destination' => '/some-page']);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('referer', '/home');
    $this->requestStack->pop();
    $this->requestStack->push($request);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(false);

    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteObject')->willReturn(null);

    $this->container->set('current_user', $account);
    $this->container->set('current_route_match', $routeMatch);

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      new Response()
    );

    $subscriber = new UserCheckSubscriber($this->logger, $this->messenger);
    $subscriber->userCheck($event);

    $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
  }

  /**
   * @throws \Exception
   */
  public function testRedirectDoesNotEnterInfiniteLoopWhenHeaderIsSet() {
    $request = new Request();
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('X-UserCheck-Redirect', '1');
    $request->headers->set('referer', '/previous');
    $this->requestStack->pop();
    $this->requestStack->push($request);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(false);

    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteObject')->willReturn(null);

    $this->container->set('current_user', $account);
    $this->container->set('current_route_match', $routeMatch);

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      new Response()
    );

    $subscriber = new UserCheckSubscriber($this->logger, $this->messenger);
    $subscriber->userCheck($event);

    $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
  }
}
