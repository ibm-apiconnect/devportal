# Drupal Helpers

A library of Drupal-related PHP helpers for Drupal 7 core and contrib modules.

[![Circle CI](https://circleci.com/gh/nicksantamaria/drupal_helpers.svg?style=svg)](https://circleci.com/gh/nicksantamaria/drupal_helpers)

## Functionality Provided

* Bean
  * Create or load a bean.
* Block
  * Render a block.
  * Place or remove a block in/from a region using core block module.
  * Set the block visibility.
* Entity
  * Get label for entity bundle.
* Feature
  * Revert a feature.
* Form
  * Get default values from the form.
* General
  * Print CLI and web messages.
* Menu
  * Add, update, delete and find menu items in specified menu.
* Module
	* Enable, disable or uninstall a module.
* Random
 	* Generate random: string, name, IP address, phone number, email, date of birth, path.
	* Get random array items.
* System
	* Get or set the weight of the module, theme or profile.
	* Check the status of the module, theme or profile.
* Taxonomy
	* Create form element options from terms in provided vocabulary.
* Theme
	* Set a theme as the default or admin theme.
	* Enable or disable a theme.
* User
	* Create user with specified fields and roles.
* Utility
	* Recursively remove empty elements from array.
	* Retrieve array column.

## Examples

**Enable a module**
```php
\Drupal\drupal_helpers\Module::enable('views');
```

**Revert a feature**
```php
\Drupal\drupal_helpers\Feature::revert('mysite_feature');
```

**Set message**
```php
\Drupal\drupal_helpers\General::messageSet('My message');
```

## Dependencies

- [X Autoload ](https://www.drupal.org/project/xautoload)
