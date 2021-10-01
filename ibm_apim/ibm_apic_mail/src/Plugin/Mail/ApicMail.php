<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apic_mail\Plugin\Mail;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Site\Settings;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Html2Text\Html2Text;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * Provides an 'ApicMail' plugin to send emails.
 *
 * @Mail(
 *   id = "mailsystem_apicmail",
 *   label = @Translation("ApicMail Mail-Plugin"),
 *   description = @Translation("ApicMail Plugin to use APIC for email formatting and sending.")
 * )
 */
class ApicMail implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * ApicMail constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(LoggerInterface $logger, RendererInterface $renderer, ModuleHandlerInterface $module_handler, MessengerInterface $messenger) {
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('logger.channel.ibm_apic_mail'),
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('messenger')
    );
  }

  /**
   * Formats a message composed by drupal_mail().
   *
   * @param array $message
   *   A message array holding all relevant details for the message.
   *
   * @return array
   *   The message as it should be sent.
   */
  public function format(array $message): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $message = $this->massageMessageBody($message);

    // Get applicable format.
    $applicable_format = 'text/html';

    // Theme message if format is set to be HTML.
    if ($applicable_format === 'text/html') {
      $converter = new Html2Text($message['body']);
      $message['plain'] = $converter->getText();

      if ($message['body'] === NULL) {
        $message['body'] = '';
      }
      else {
        $message['body'] = $message['body']->__toString();
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $message;
  }


  /**
   * @param array $message
   *
   * @return bool
   * @throws \Exception
   */
  public function mail(array $message): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $site_config = \Drupal::service('ibm_apim.site_config');
    $platformApiEndpoint = $site_config->getPlatformApimEndpoint();
    $orgId = $site_config->getOrgId();
    $catId = $site_config->getEnvId();
    if ($orgId === NULL || $orgId === '' || $catId === NULL || $catId === '' || $platformApiEndpoint === NULL || $platformApiEndpoint === '') {
      $this->messenger->addWarning(t('APIC Hostname, catalog ID or organization ID are not set. Aborting'));
      // return true so that site creation doesnt fail when trying to send the admin email
      $returnValue = TRUE;
    }
    else {
      $url = $platformApiEndpoint . '/catalogs/' . $orgId . '/' . $catId . '/send-email';

      $returnValue = $this->call($url, $message);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Massages the message body into the format expected for rendering.
   *
   * @param array $message
   *   The message.
   *
   * @return array
   */
  public function massageMessageBody(array $message): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    // Get default mail line endings and merge all lines in the e-mail body
    // separated by the mail line endings. Keep Markup objects and escape others
    // and then treat the result as safe markup.
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);
    $applicable_format = 'text/html';
    $filter_format = filter_fallback_format();
    $message['body'] = Markup::create(implode($line_endings, array_map(function ($body) use ($applicable_format, $filter_format) {
      // If the body contains no html tags but the applicable format is HTML,
      // we can assume newlines will need be converted to <br>.
      if ($applicable_format === 'text/html' && mb_strlen(strip_tags($body)) === mb_strlen($body)) {
        // The default fallback format is 'plain_text', which escapes markup,
        // converts new lines to <br> and converts URLs to links.
        $build = [
          '#type' => 'processed_text',
          '#text' => $body,
          '#format' => $filter_format,
        ];
        $body = $this->renderer->renderPlain($build);
      }
      // If $item is not marked safe then it will be escaped.
      return $body instanceof MarkupInterface ? $body : Html::escape($body);
    }, $message['body'])));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $message;
  }

  /**
   * Call the actual REST endpoint
   *
   * @param $url
   * @param $message
   *
   * @return bool
   * @throws \Exception
   */
  private function call($url, $message): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = FALSE;

    $to = self::parse_mailboxes($message['to']);

    $toValues = implode(',', array_values($to));
    if (isset($toValues)) {
      $requestBody = [
        'to' => $toValues,
        'subject' => $message['subject'],
        'body' => $message['body'],
        'content_type' => 'html',
      ];
      // do not set a from address - use the sender address configured in the CM

      if (array_key_exists('cc', $message) && !empty($message['cc'])) {
        $requestBody['cc'] = implode(',', self::parse_mailboxes($message['cc']));
      }
      elseif (isset($message['headers']['Cc']) && !empty($message['headers']['Cc'])) {
        $requestBody['cc'] = implode(',', self::parse_mailboxes($message['headers']['Cc']));
      }
      if (array_key_exists('bcc', $message) && !empty($message['bcc'])) {
        $requestBody['bcc'] = implode(',', self::parse_mailboxes($message['bcc']));
      }
      elseif (isset($message['headers']['Bcc']) && !empty($message['headers']['Bcc'])) {
        $requestBody['bcc'] = implode(',', self::parse_mailboxes($message['headers']['Bcc']));
      }

      try {
        $json = json_encode($requestBody, JSON_THROW_ON_ERROR);
        if ($json !== NULL) {
          $result = ApicRest::post($url, $json, 'platform');
          if (isset($result) && $result->code >= 200 && $result->code < 300) {
            $returnValue = TRUE;
          }
        }
      } catch (\JsonException $e) {
        \Drupal::logger('ibm_apic_mail')->info('call: JsonException %code %message', [
          '%message' => $e->getMessage(),
          '%code' => $e->getCode(),
        ]);
        $returnValue = FALSE;
      } catch (Throwable $e) {
        \Drupal::logger('ibm_apic_mail')->info('call: Exception %code %message', [
          '%message' => $e->getMessage(),
          '%code' => $e->getCode(),
        ]);
        $returnValue = FALSE;
      }
    }
    else {
      \Drupal::logger('ibm_apic_mail')->info('call: No recipients provided');
      $returnValue = FALSE;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Converts a string holding one or more mailboxes to an array.
   *
   * This function "borrowed" from the excellent Swiftmailer module
   *
   * @param $value
   *   A string holding one or more mailboxes.
   *
   * @return array
   *   this return array
   */
  private static function parse_mailboxes($value): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $validator = \Drupal::service('email.validator');

    // Split mailboxes by ',' (comma) and ';' (semicolon).
    $mailboxes_raw = [];
    preg_match_all("/((?:^|\s){0,}(?:(?:\".*?\"){0,1}.*?)(?:$|,|;))/", $value, $mailboxes_raw);

    // Define an array which will keep track of mailboxes.
    $mailboxes = [];

    // Iterate through each of the raw mailboxes and process them.
    foreach ($mailboxes_raw[0] as $mailbox_raw) {
      if (empty($mailbox_raw)) {
        continue;
      }

      // Remove leading and trailing whitespace.
      $mailbox_raw = trim($mailbox_raw);

      if (preg_match('/^.*<.*>.*$/', $mailbox_raw)) {
        $mailbox_components = explode('<', $mailbox_raw);
        $mailbox_name = trim(preg_replace("/\"/", '', $mailbox_components[0]));
        $mailbox_address = preg_replace('/>.*/', '', $mailbox_components[1]);
        if ($validator->isValid($mailbox_address)) {
          $mailboxes[$mailbox_address] = $mailbox_name;
        }
      }
      else {
        $mailbox_address = preg_replace("/(,|;)/", '', $mailbox_raw);
        if ($validator->isValid($mailbox_address)) {
          $mailboxes[] = $mailbox_address;
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $mailboxes;
  }

}
