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

class MockApplicationNodeBuilder extends AbstractMockNodeBuilder {
  private ?string $apic_url = NULL;

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
