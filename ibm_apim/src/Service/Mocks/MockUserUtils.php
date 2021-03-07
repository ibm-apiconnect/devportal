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

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\ibm_apim\Service\UserUtils;

/**
 * User related functions.
 */
class MockUserUtils extends UserUtils {

  /**
   * Override parent implementation so that we don't have to call a script on the appliance.
   *
   * @param $data
   *
   * @return bool|string
   */
  public function decryptData($data) {

    // As we are not calling out to the script on the file system, we can only handle known values.
    // Extend this list as required for additional tests.
    if ($data === '!BASE64_SIV_ENC!_ASN7IjhQ94cI5qQwJgl27GWEERAL+RGm+3zePOGItY1NAAAAEVfMy7SiIHeh3bFlYrndn8LRFPJZ7oo+1l9YL3aaSbwn') {
      return 'valid@example.com';
    }
    if ($data === '!BASE64_SIV_ENC!_ASN0stOAF9aRqvWvtwiznIfwqFiU6CWJUaSW9dcoa0JJAAAAGXqTh0a4a0Kepv4BDPurLKNApqD+ISLoq0dfUpMYEm+F') {
      return 'missingFields@example.com';
    }
    if ($data === '!BASE64_SIV_ENC!_AWtlJMmO2L285jmrO/FU82STYYQ/j1WIc84tNXIY6XLtAAAAGcI1pFNzJwwt5ibLCP7ktxD7wSDwat4J0Mk/qI+sE0b1') {
      return 'alreadyActive@example.com';
    }
    if ($data === '!BASE64_SIV_ENC!_AebI4e5L+ws45CuqiodINDRlMc8OVaVhQfUXy75Dyn07AAAAE/ujLm0W+vb0guTqRgn6DDG7R2H6Re7gaSkAJeNg7+8v') {
      return 'invalid@example.com';
    }

    return 'invalid@example.com';

  }

}
