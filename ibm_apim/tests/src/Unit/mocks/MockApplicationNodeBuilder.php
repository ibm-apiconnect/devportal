<?php

namespace Drupal\Tests\ibm_apim\Unit\mocks;

class MockApplicationNodeBuilder extends AbstractMockNodeBuilder {
  private ?string $apic_url = NULL;

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

  public function setApicUrl($apic_url) {
    $this->apic_url = $apic_url;
    return $this;
  }

  public function build() {
    $mockValues = array(
      'apic_url' => $this->apic_url
    );

    $this->setMagicMocks($mockValues);

    return $this->node;
  }
}
