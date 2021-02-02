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

namespace Drupal\apictest\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\ibm_apim\ApicUser;

class UserRegistryContext extends RawDrupalContext {

  /**
   * @Given userregistries:
   */
  public function userregistries(TableNode $table): void {

    // user registries are not stored in the db, they are in state. Therefore we will create these for the tests even if
    // running with mocks elsewhere.
    // TODO: we should capture the current state and reinstate it after tests.
    $service = \Drupal::service('ibm_apim.user_registry');
    $all = [];

    foreach ($table as $row) {

      $reg = [];
      $reg['registry_type'] = $row['type'];
      $reg['title'] = $row['title'];
      $reg['name'] = $row['title'];
      $url = $row['url'];
      $reg['url'] = $url;
      $reg['id'] = $url;
      $reg['summary'] = $url;
      if ($row['user_managed'] === 'yes') {
        $reg['user_managed'] = TRUE;
      }
      else {
        $reg['user_managed'] = FALSE;
      }
      $reg['user_registry_managed'] = FALSE;
      $reg['onboarding'] = FALSE; // irrelevant - onboarding is set on a catalog?!
      $reg['case_sensitive'] = FALSE;
      $reg['identity_providers'] = [];

      $all[$url] = $reg;
      if ($row['default'] === 'yes') {
        $service->setDefaultRegistry($url);
      }

    }

    $service->updateAll($all);

    // flush caches so registries are available
    drupal_flush_all_caches();

  }


}
