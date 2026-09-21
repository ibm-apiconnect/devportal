<?php

namespace Drupal\Tests\ibm_apim\Unit\mocks;

use Drupal\ibm_apim\External\SerializationUtility;

class MockProductNodeBuilder extends AbstractMockNodeBuilder {
  private ?array $product_apis = NULL;
  private ?array $product_plans = NULL;
  private ?string $apic_url = NULL;
  private ?string $product_data = NULL;

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

  public function setApis($apis) {
    $this->product_apis = array_map([SerializationUtility::class, 'encodeData'], $apis);
    return $this;
  }

  public function setPlans($plans) {
    $this->product_plans = array_map([SerializationUtility::class, 'encodeData'], $plans);
    return $this;
  }

  public function setApicUrl($apic_url) {
    $this->apic_url = $apic_url;
    return $this;
  }

  public function setData($product_data) {
    $this->product_data = $product_data;
    return $this;
  }

  public function build() {
    $mockValues = array(
      'product_apis' => $this->product_apis,
      'product_plans' => $this->product_plans,
      'apic_url' => $this->apic_url,
      'product_data' => $this->product_data
    );

    $this->setMagicMocks($mockValues);

    return $this->node;
  }
}

