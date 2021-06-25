<?php

namespace Drupal\rules_http_client\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides "Rules Http client" rules action.
 *
 * @RulesAction(
 *   id = "rules_http_client",
 *   label = @Translation("Request HTTP data"),
 *   category = @Translation("Data"),
 *   context = {
 *     "url" = @ContextDefinition("string",
 *       label = @Translation("URL"),
 *       description = @Translation("Url address where to post, get and delete request send."),
 *       required = TRUE,
 *       multiple = TRUE,
 *     ),
 *     "headers" = @ContextDefinition("string",
 *       label = @Translation("Headers"),
 *       description = @Translation("Request headers to send as 'name: value' pairs, one per line (e.g., Accept: text/plain). See <a href='https://www.wikipedia.org/wiki/List_of_HTTP_header_fields'>wikipedia.org/wiki/List_of_HTTP_header_fields</a> for more information."),
 *       required = FALSE,
 *      ),
 *     "method" = @ContextDefinition("string",
 *       label = @Translation("Method"),
 *       description = @Translation("The HTTP request methods like'HEAD','POST','PUT','DELETE','TRACE','OPTIONS','CONNECT','PATCH' etc."),
 *       required = FALSE,
 *     ),
 *     "data" = @ContextDefinition("string",
 *       label = @Translation("Data"),
 *       description = @Translation("The request body, formatter as 'param=value&param=value&...' or one 'param=value' per line.."),
 *       required = FALSE,
 *       multiple = TRUE,
 *       assignment_restriction = "data",
 *     ),
 *     "max_redirects" = @ContextDefinition("integer",
 *       label = @Translation("Max Redirect"),
 *       description = @Translation("How many times a redirect may be followed."),
 *       default_value = 3,
 *       required = FALSE,
 *       assignment_restriction = "input",
 *     ),
 *     "timeout" = @ContextDefinition("float",
 *       label = @Translation("Timeout"),
 *       description = @Translation("The maximum number of seconds the request may take.."),
 *       default_value = 30,
 *       required = FALSE,
 *     ),
 *   },
 *   provides = {
 *     "http_response" = @ContextDefinition("string",
 *       label = @Translation("HTTP data")
 *     )
 *   }
 * )
 *
 * @todo: Define that message Context should be textarea comparing with textfield Subject
 * @todo: Add access callback information from Drupal 7.
 */
class RulesHttpClient extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The logger for the rules channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a httpClient object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger_factory->get('rules_http_client');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')
    );
  }

  /**
   * Send a system email.
   *
   * @param string[] $url
   *   Url addresses HTTP request.
   * @param string|null $headers
   *   (optional) Header information of HTTP Request.
   * @param string $method
   *   (optional) Method of HTTP request.
   * @param string|null $data
   *   (optional) Raw data of HTTP request.
   * @param int|null $maxRedirect
   *   (optional) Max redirect for HTTP request.
   * @param int|null $timeOut
   *   (optional) Time Out for HTTP request.
   */
  protected function doExecute(array $url, $headers, $method, $data = NULL, $maxRedirect = 3, $timeOut = 30) {
    // Headers section.
    $headers = explode("\r\n", $headers);
    if (is_array($headers)) {
      foreach ($headers as $header) {
        if (!empty($header) && strpos($header, ':') !== FALSE) {
          list($name, $value) = explode(':', $header, 2);
          if (!empty($name)) {
            $options['headers'][$name] = ltrim($value);
          }
        }
      }
    }
    $finalArray = [];
    if (is_array($data)) {
      // Data section.
      foreach ($data as $singleArray) {
        $finalSingleArray = explode('=', $singleArray);
        $finalArray[$finalSingleArray[0]] = $finalSingleArray[1];
      }

      // Json decode array.
      $finalArray = json_encode($finalArray);
    }

    $options['data'] = $finalArray;

    // Max redirects.
    $options['max_redirects'] = empty($maxRedirect) ? 3 : $maxRedirect;

    // Timeout.
    $options['timeout'] = empty($timeOut) ? 30 : $timeOut;

    $postUrl = $url[0];

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_POSTFIELDS => $options['data'],
      CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-CSRF-Token: yJeocNuElZq6shl7veWUMA8lSof0nKxWWf1UKPL5IrE'],
      CURLOPT_URL => $postUrl,
      CURLOPT_SSL_VERIFYPEER => FALSE,
    ]);
    $response = curl_exec($curl);
    $output = json_decode($response);

    if (isset($output->error)) {
      $this->logger->warning('Error while requsting url as Error code: %code and Message: %message.', ['%code' => $output->error->code, '%message' => $output->error->message]);
      return;
    }
    else {
      $this->setProvidedValue('http_response', $response->data);
    }
  }

}
