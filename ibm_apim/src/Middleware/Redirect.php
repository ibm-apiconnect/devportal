<?php

namespace Drupal\ibm_apim\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Redirect implements HttpKernelInterface {
  protected $httpKernel;
  protected $redirectResponse;

  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  public function handle(Request $request, int $type = self::MASTER_REQUEST, bool $catch = TRUE): Response {
    $response = $this->httpKernel->handle($request, $type, $catch);
    return $this->redirectResponse ?: $response;
  }

  public function setRedirectResponse(?RedirectResponse $redirectResponse) {
    $this->redirectResponse = $redirectResponse;
  }

}