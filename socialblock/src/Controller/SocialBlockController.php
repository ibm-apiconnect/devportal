<?php


namespace Drupal\socialblock\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

class SocialBlockController extends ControllerBase {
  
  public function status() {
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasRole('administrator')) {
      return;
    }
    $request = \Drupal::request();
    $data = $request->query->get('data');
  
    if ($data) {
      \Drupal::messenger()->addError($this->t("Security policy violation reported. ") . $data);
    }
    return new JsonResponse(['status' => 'ok']);
  }
}