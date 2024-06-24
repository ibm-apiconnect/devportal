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

abstract class AbstractMockNodeBuilder {
  private $fieldBuilder;
  protected $node;
  protected $unitScope;

  public function __construct($phpUnitScope) {
    $this->node = $phpUnitScope->getMockBuilder('\Drupal\node\Entity\Node')->disableOriginalConstructor()->getMock();
    $this->fieldBuilder = $phpUnitScope->getMockBuilder('\Drupal\Core\Field\FieldItemList')->disableOriginalConstructor();
    $this->unitScope = $phpUnitScope;
  }

  protected function setMagicMocks($mockValues): void {
    $mapGet = function($property, $value) {
      return [$property, $this->withValue($value)];
    };
    $this->node->method('__get')
      ->will($this->unitScope->returnValueMap(
        array_map($mapGet, array_keys($mockValues), $mockValues)
      ));

    $mapIsset = function($property, $value) {
      return [$property, isset($value)];
    };
    $this->node->method('__isset')
      ->will($this->unitScope->returnValueMap(
        array_map($mapIsset, array_keys($mockValues), $mockValues)
      ));
  }

  protected function withValue($value) {
    $newField = $this->fieldBuilder->getMock();
    $createValueArray = function ($value) {
      return array('value' => $value);
    };

    if(is_array($value) && count($value) > 0) {
      $newField->method('getValue')->willReturn(array_map($createValueArray, $value));
      $newField->method('__get')->with('value')->willReturn($value[0]);
    } else {
      $newField->method('getValue')->willReturn($createValueArray($value));
      $newField->method('__get')->with('value')->willReturn($value);
    }

    return $newField;
  }

  public function build() {
    return $this->node;
  }
}