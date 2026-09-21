<?php


namespace Drupal\ibm_apic_session_check\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

class SessionCheckController extends ControllerBase {
  
  public function status() {
    $user = \Drupal::currentUser();
    return new JsonResponse([
      'logged_in' => $user->isAuthenticated(),
    ]);
  }
}