<?php
require_once '../../vendor/leafo/scssphp/scss.inc.php';

use Leafo\ScssPhp\Compiler;

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