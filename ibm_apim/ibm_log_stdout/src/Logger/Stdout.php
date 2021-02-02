<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_log_stdout\Logger;

use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Psr\Log\LoggerInterface;

class Stdout implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a Stdout object.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(LogMessageParserInterface $parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    $output = fopen('php://stderr', 'w');
    $severity = strtoupper(RfcLogLevel::getLevels()[$level]);
    $username = '';
    if (isset($context['user']) && !empty($context['user'])) {
      $username = $context['user']->getAccountName();
    }
    if (empty($username)) {
      $username = 'anonymous';
    }

    $request_uri = $context['request_uri'];
    $referrer_uri = $context['referer'];
    $variables = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($variables) ? $message : strtr($message, $variables);
    $input_message = strip_tags($message);

    $message = t('WATCHDOG: [@severity] [@type] @message | user: @user | uri: @request_uri | referer: @referer_uri', [
      '@severity' => $severity,
      '@type' => $context['channel'],
      '@message' => $input_message,
      '@user' => $username,
      '@request_uri' => $request_uri,
      '@referer_uri' => $referrer_uri,
    ]);

    fwrite($output, $message . "\r\n");
    fclose($output);
  }

}
