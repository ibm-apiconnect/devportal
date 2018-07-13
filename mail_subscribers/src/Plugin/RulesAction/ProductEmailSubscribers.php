<?php

namespace Drupal\mail_subscribers\Plugin\RulesAction;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mail_subscribers\Service\MailService;

/**
 * Provides "Email product subscribers" rules action.
 *
 * @RulesAction(
 *   id = "rules_email_product_subscribers",
 *   label = @Translation("Email product subscribers"),
 *   category = @Translation("Subscribers"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("The Product to email subscribers of")
 *     ),
 *     "plan" = @ContextDefinition("string",
 *       label = @Translation("Plan name"),
 *       description = @Translation("The plan name, leave blank for all plans in this product")
 *     ),
 *     "members" = @ContextDefinition("boolean",
 *       label = @Translation("Send to all developer organization members"),
 *       description = @Translation("If disabled then the mail will only be sent to the owner of the developer organization."),
 *       default_value = FALSE,
 *       required = FALSE
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Subject"),
 *       description = @Translation("The email's subject.")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("The email's message body.")
 *     ),
 *     "reply" = @ContextDefinition("email",
 *       label = @Translation("Reply to"),
 *       description = @Translation("The mail's reply-to address. Leave it empty to use the site-wide configured address."),
 *       default_value = NULL,
 *       allow_null = TRUE,
 *       required = FALSE
 *     ),
 *     "language" = @ContextDefinition("language",
 *       label = @Translation("Language"),
 *       description = @Translation("If specified, the language used for getting the mail message and subject."),
 *       default_value = NULL,
 *       required = FALSE
 *     ),
 *   }
 * )
 *
 * @todo: Define that message Context should be textarea comparing with textfield Subject
 * @todo: Add access callback information from Drupal 7.
 */
class ProductEmailSubscribers extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The logger channel the action will write log messages to.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * @var \Drupal\mail_subscribers\Service\MailService
   */
  protected $subscriberMailService;

  /**
   * Constructs a SendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The alias storage service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\mail_subscribers\Service\MailService $subscriberMailService
   *   The subscriber mail service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, MailManagerInterface $mail_manager, MailService $subscriberMailService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
    $this->subscriberMailService = $subscriberMailService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('rules'),
      $container->get('plugin.manager.mail'),
      $container->get('mail_subscribers.mail_service')
    );
  }

  /**
   * Email the subscribers of a given product or plan.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The product to check for a type.
   * @param string|null $plan
   *   Plan name, null for all plans
   * @param boolean $members
   *   Email all members or just devorg owners.
   * @param string $subject
   *   Subject of the email.
   * @param string $message
   *   Email message text.
   * @param string|null $reply
   *   (optional) Reply to email address.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   (optional) Language code.
   */
  protected function doExecute($node, $plan = NULL, $members = FALSE, $subject, $message, $reply = NULL, LanguageInterface $language = NULL) {
    $langcode = isset($language) ? $language->getId() : LanguageInterface::LANGCODE_SITE_DEFAULT;
    $mailParams = [
      'subject' => $subject,
      'message' => $message,
    ];
    if ($node->getType() == 'product') {
      $plan_ref = '';
      if (isset($plan_name)) {
        $prodPlans = array();
        foreach ($node->product_plans->getValue() as $arrayValue) {
          $prodPlans[] = unserialize($arrayValue['value']);
        }
        foreach ($prodPlans as $prodPlan) {
          if ($plan == $prodPlan['title']) {
            $plan_ref = ':' . $prodPlan['title'];
          }
        }
      }
      if ($members == TRUE) {
        $to_list = $this->subscriberMailService->getProductSubscribingMembers($node->apic_ref->value . $plan_ref);
      }
      else {
        $to_list = $this->subscriberMailService->getProductSubscribingOwners($node->apic_ref->value . $plan_ref);
      }

      $mailParams['langcode'] = $langcode;

      $this->subscriberMailService->sendEmail($mailParams, $to_list, $reply);
      if ($members == TRUE) {
        $this->logger->notice('Sent email to members subscribing to product %product', array(
          '%product' => $node->getTitle()
        ));
      }
      else {
        $this->logger->notice('Sent email to owners subscribing to product %product', array(
          '%product' => $node->getTitle()
        ));
      }
    }
    else {
      $this->logger->error('Node %product is not a product', array(
        '%product' => $node->getTitle()
      ));
    }

  }

}
