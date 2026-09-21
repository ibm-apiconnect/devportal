<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2021, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit\mocks;

use Drupal\ibm_apim\External\SerializationUtility;

class MockApiNodeBuilder extends AbstractMockNodeBuilder {

  public function __construct($phpUnitScope) {
    $createMocks = function() {
      $nodeMock = $this->getMockBuilder('\Drupal\node\Entity\Node')
        ->disableOriginalConstructor()
        ->getMock();
      $fieldBuilderMock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemList')
        ->disableOriginalConstructor();
      return [$nodeMock, $fieldBuilderMock];
    };

    $boundCallback = $createMocks->bindTo($phpUnitScope, $phpUnitScope);
    list($nodeMock, $fieldBuilderMock) = $boundCallback();

    parent::__construct($phpUnitScope, $nodeMock, $fieldBuilderMock);
  }

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
      'api_swagger' => SerializationUtility::encodeData($apiDocument),
      'api_protocol' => $protocol
    );

    $this->setMagicMocks($mockValues);
    $this->node->api_protocol->method('getSetting')->with('allowed_values')->willReturn(array(
      $protocol => $protocol
    ));

    return $this;
  }
}
