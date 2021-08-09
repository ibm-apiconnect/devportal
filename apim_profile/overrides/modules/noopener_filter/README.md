CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Noopener Filter module adds a filter that enables it to add `rel="noopener"`
to all WYSIWYG added links. This is done in order to prevent `window.opener`
from being exploited.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/noopener_filter

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/noopener_filter


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module
    2. Navigate to Administration > Configuration > Text formats and editors
    3. For each text format Configure and select the 'Add noopener to all links'
       checkbox and save configuration


MAINTAINERS
-----------

Current maintainers:
 * Ricardo Peters (RicardoPeters) - https://www.drupal.org/u/ricardopeters
