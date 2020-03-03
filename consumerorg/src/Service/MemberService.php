<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2017, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Service;

use Drupal\consumerorg\ApicType\Member;
use Drupal\ibm_apim\Service\ApicUserService;

class MemberService {

  private $userService;

  public function __construct(ApicUserService $user_service) {
    $this->userService = $user_service;
  }


  /**
   * Parse an ApicType/Member object out of the given JSON structure (e.g. from a webhook body or snapshot)
   *
   * @param object $json
   *
   * @return \Drupal\consumerorg\ApicType\Member
   */
  public function createFromJSON($json): Member {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $member = new Member();

    if (!empty($json['url'])) {
      $member->setUrl($json['url']);
    }

    if (!empty($json['state'])) {
      $member->setState($json['state']);
    }

    if (!empty($json['user_url'])) {
      $member->setUserUrl($json['user_url']);
    }

    if (!empty($json['user'])) {
      $member->setUser($this->userService->getUserFromJSON($json['user']));
    }

    if (!empty($json['role_urls'])) {
      $member->setRoleUrls($json['role_urls']);
    }

    if (!empty($json['org_url'])) {
      $member->setOrgUrl($json['org_url']);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $member);
    return $member;
  }

}