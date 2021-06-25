<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit\mocks;

class MockApiNodeBuilder extends AbstractMockNodeBuilder {

  public function setDocument($apiDocument) {
    $apiName = $apiDocument['info']['title'];
    $protocol = 'protocol';

    $this->node->method('id')->willReturn(1);
    $this->node->method('getTitle')->willReturn($apiName);

    $mockValues = array(
      'apic_ref' => $apiName . ':' . $apiDocument['info']['version'],
      'apic_url' => 'mock url',
      'api_id' => $apiDocument['info']['x-ibm-name'],
      'apic_version' => '1.0.0',
      'api_swagger' => serialize($apiDocument),
      'api_protocol' => $protocol
    );

    $this->setMagicMocks($mockValues);
    $this->node->api_protocol->method('getSetting')->with('allowed_values')->willReturn(array(
      $protocol => $protocol
    ));

    return $this;
  }
}
