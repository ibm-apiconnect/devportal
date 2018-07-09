<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\ibm_apim\Service\SiteConfig;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\Service\ApimUtils;

/**
 * Utility functions to smooth our interaction with the apim consumer apis.
 */
class MockApimUtils extends ApimUtils {

  /**
   * ApimUtils constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   */
  public function __construct(LoggerInterface $logger,
                              SiteConfig $site_config) {
    parent::__construct($logger,$site_config);
  }


  /**
   * For tests skip sanitization to enable easier test data
   * @param $url
   *   registry url
   *
   * @return int
   *   0 = not valid, 1 = valid
   */
  public function sanitizeRegistryUrl($url) {
    return 1;
  }

}
