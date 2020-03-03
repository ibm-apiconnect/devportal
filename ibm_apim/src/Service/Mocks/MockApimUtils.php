<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\ibm_apim\Service\ApimUtils;

/**
 * Utility functions to smooth our interaction with the apim consumer apis.
 */
class MockApimUtils extends ApimUtils {

  /**
   * For tests skip sanitization to enable easier test data
   *
   * @param $url
   *   registry url
   *
   * @return int
   *   0 = not valid, 1 = valid
   */
  public function sanitizeRegistryUrl($url): int {
    \Drupal::logger('mock_apim_utils')->debug('in MockApimUtils::sanitizeRegistryUrl() with %url', ['%url' => $url]);
    return parent::sanitizeRegistryUrl($url);
  }

}
