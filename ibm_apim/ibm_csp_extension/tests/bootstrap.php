<?php


/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
/**
 * @file
 * Bootstrap file for PHPUnit tests.
 */

// Include the Drupal autoloader.
$autoloader = require_once __DIR__ . '/../../../../vendor/autoload.php';

// Register a simple autoloader for the module's namespace.
$autoloader->addPsr4('Drupal\\ibm_csp_extension\\', __DIR__ . '/../src');
