<?php

/**
 * @file
 * Bootstrap file for PHPUnit tests.
 */

// Include the Drupal autoloader.
$autoloader = require_once __DIR__ . '/../../../../vendor/autoload.php';

// Register a simple autoloader for the module's namespace.
$autoloader->addPsr4('Drupal\\ibm_csp_extension\\', __DIR__ . '/../src');
