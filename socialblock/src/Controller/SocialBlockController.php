<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

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
      \Drupal::messenger()->addError("Security policy violation reported. " . $data);
    }
    return new JsonResponse(['status' => 'ok']);
  }
}