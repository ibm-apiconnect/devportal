<?php

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
