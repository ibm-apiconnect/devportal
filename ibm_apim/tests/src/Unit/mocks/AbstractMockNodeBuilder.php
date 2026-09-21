<?php

namespace Drupal\Tests\ibm_apim\Unit\mocks;

use Drupal\ibm_apim\External\SerializationUtility;
use PHPUnit\Framework\MockObject\MockBuilder;

abstract class AbstractMockNodeBuilder {
  private MockBuilder $fieldBuilder;
  protected $node;
  protected $unitScope;

  public function __construct($phpUnitScope, $nodeMock, $fieldBuilderMock) {
    $this->node = $nodeMock;
    $this->fieldBuilder = $fieldBuilderMock;
    $this->unitScope = $phpUnitScope;
  }

  protected function setMagicMocks($mockValues): void {
    $mapGet = function($property, $value) {
      return [$property, $this->withValue($value)];
    };

    $createValueMap = function($mappedValues) {
      return $this->returnValueMap($mappedValues);
    };
    $boundValueMap = $createValueMap->bindTo($this->unitScope, $this->unitScope);

    $this->node->method('__get')
      ->will($boundValueMap(
        array_map($mapGet, array_keys($mockValues), $mockValues)
      ));

    $mapIsset = function($property, $value) {
      return [$property, isset($value)];
    };
    $this->node->method('__isset')
      ->will($boundValueMap(
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