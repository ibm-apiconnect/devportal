<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2021, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit\mocks;

class MockProductNodeBuilder extends AbstractMockNodeBuilder {
  private ?array $product_apis = NULL;
  private ?array $product_plans = NULL;
  private ?string $apic_url = NULL;
  private ?string $product_data = NULL;

  public function setApis($apis) {
    $this->product_apis = array_map('serialize', $apis);
    return $this;
  }
  
  public function setPlans($plans) {
    $this->product_plans = array_map('serialize', $plans);
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
