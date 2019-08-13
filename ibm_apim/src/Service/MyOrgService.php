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

namespace Drupal\ibm_apim\Service;


use Drupal\consumerorg\ApicType\Member;

class MyOrgService {

  public function __construct() {

  }

  /**
   * @param $orgmember
   *
   * @return array
   */
  public function prepareOrgMemberForDisplay(Member $orgmember): array {
    $member = [];
    $user = $orgmember->getUser();
    $member['username'] = $user->getUsername();

    // name - will be used to identify the user in the myorg page.
    // we need to ensure we always have a value so fallback onto username if we don't have first/last name
    if (!empty($user->getFirstname()) && !empty($user->getLastname())) {
      $member['name'] = $user->getFirstname() . ' ' . $user->getLastname();
    }
    else {
      $member['name'] = $member['username'];
    }
    // details - this is the username and email address.
    // We:
    // - will always have username
    // - possibly won't have an email address
    // - don't need to show both if they are the same
    $details = $user->getUsername();

    if (!empty($user->getMail()) && $user->getMail() !== $user->getUsername()) {
      $details = $details . ' (' . $user->getMail() . ')';
    }
    $member['details'] = $details;

    $member['state'] = $user->getState();
    $member['id'] = $orgmember->getId();

    $member['role_urls'] = $orgmember->getRoleUrls();

    // file_create_url is not available as a service yet: https://www.drupal.org/project/drupal/issues/2669074
    // don't use it for unit tests.
    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      $userStorage = \Drupal::service('ibm_apim.user_storage');
      $entity = $userStorage->load($user);
      if (!empty($entity->user_picture) && $entity->user_picture->isEmpty() === FALSE) {
        $image = $entity->user_picture;
        $uri = $image->entity->getFileUri();
        $member['user_picture'] = file_create_url($uri);
      }
      else {
        $member['user_picture'] = 'https://file/is/here/for/tests';
      }
    }
    return $member;
  }

}
