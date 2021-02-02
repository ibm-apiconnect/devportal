<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Service;


use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\ibm_apim\ApicType\ApicUser;
use Psr\Log\LoggerInterface;

class ConsumerOrgLoginService implements ConsumerOrgLoginInterface {

  private $orgService;
  private $logger;


  public function __construct(ConsumerOrgService $org_service,
                              LoggerInterface $logger) {
    $this->orgService = $org_service;
    $this->logger = $logger;
  }

  public function createOrUpdateLoginOrg(ConsumerOrg $consumerorg, ApicUser $user): ?ConsumerOrg {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $consumerorg->getUrl());
    }

    $theOrg = $this->orgService->get($consumerorg->getUrl());

    if ($theOrg === NULL) {
      // This consumerorg exists in APIC but not in drupal so create it.
      $this->logger->notice('Consumerorg @consumerorgname (url=@consumerorgurl) was not found in drupal database during login. It will be created.', [
        '@consumerorgurl' => $consumerorg->getUrl(),
        '@consumerorgname' => $consumerorg->getName(),
      ]);

      if ($consumerorg->getTitle() === NULL) {
        // Create call expects a 'title' value but we don't have one at this point. Use 'name'.
        $consumerorg->setTitle($consumerorg->getName());
      }
      if ($consumerorg->getOwnerUrl() === NULL) {
        $consumerorg->setOwnerUrl($user->getUrl());
      }
      if ($consumerorg->getMembers() === NULL) {
        $consumerorg->setMembers([]);
      }

      $this->orgService->createNode($consumerorg);

      $theOrg = $this->orgService->get($consumerorg->getUrl());
    }

    // regardless of whether we just created the org or not, we may need to update membership
    // if this user is not listed already as a member of the org, add them
    if ($theOrg !== NULL && $theOrg->isMember($user->getUrl()) === FALSE) {

      // get existing members and roles so they can be preserved
      $consumerorg->addMembers($theOrg->getMembers());
      $consumerorg->addRoles($theOrg->getRoles());

      $this->orgService->createOrUpdateNode($consumerorg, 'login-update-members');
    }
    else {
      // while we aren't updating a node, ensure we are returning an accurate representation of the org
      $consumerorg->addMembers($theOrg->getMembers());
      $consumerorg->addRoles($theOrg->getRoles());
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $consumerorg->getUrl());
    }
    return $consumerorg;
  }

}
