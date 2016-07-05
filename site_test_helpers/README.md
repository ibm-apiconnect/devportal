# Site Test Helpers
### A set of Drupal site testing helpers to speedup authoring of tests.
[![Circle CI](https://circleci.com/gh/alexdesignworks/site_test_helpers.svg?style=svg)](https://circleci.com/gh/alexdesignworks/site_test_helpers)

## Usage:
```php
class MyModuleWebTestCase extends SiteWebTestCase {
  use SiteTestHelpers {
    SiteTestHelpers::__construct as private __sthConstruct;
  }
  use SiteRestTestCase;

  /**
   * {@inheritdoc}
   */
  public function __construct($test_id = NULL) {
    $this->__sthConstruct($test_id);
  }
  
  public function testMyFeature() {
    // Test code for your feature.
  }
}
```
