<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
require_once '../../vendor/scssphp/scssphp/scss.inc.php';

use ScssPhp\ScssPhp\Compiler;

$scss = new Compiler();
$scss->addImportPath('bootstrap/assets/stylesheets');
$scss->addImportPath('bootstrap/assets/stylesheets/bootstrap');
$scss->addImportPath('scss/');
$scss->addImportPath('scss/component');
$scss->addImportPath('scss/jquery-ui');

$cssOut = $scss->compile('@import "style.scss";');

if (!file_exists(__DIR__ . '/css')) {
  mkdir(__DIR__ . '/css', 0755, TRUE);
}
file_put_contents(__DIR__ . '/css/style.css', $cssOut);