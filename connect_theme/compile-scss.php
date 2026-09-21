<?php

require_once '../../vendor/scssphp/scssphp/scss.inc.php';

use ScssPhp\ScssPhp\Compiler;

$scss = new Compiler();
$scss->addImportPath('bootstrap/assets/stylesheets');
$scss->addImportPath('bootstrap/assets/stylesheets/bootstrap');
$scss->addImportPath('scss/');
$scss->addImportPath('scss/component');
$scss->addImportPath('scss/jquery-ui');

$cssOut = $scss->compileString('@import "style.scss";')->getCss();

if (!file_exists(__DIR__ . '/css') && !mkdir($concurrentDirectory = __DIR__ . '/css', 0755, TRUE) && !is_dir($concurrentDirectory)) {
  throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
}
file_put_contents(__DIR__ . '/css/style.css', $cssOut);

$scss = new Compiler();

$scss->addImportPath('scss/');

$cssOut = $scss->compileString('@import "mail.scss";')->getCss();

if (!file_exists(__DIR__ . '/css') && !mkdir($concurrentDirectory = __DIR__ . '/css', 0755, TRUE) && !is_dir($concurrentDirectory)) {
  throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
}
file_put_contents(__DIR__ . '/css/mail.css', $cssOut);