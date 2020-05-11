<?php

namespace Drupal\apic_app\Entity;

@trigger_error('The ' . __NAMESPACE__ . '\Credentials is deprecated in 2018.4.1.10 
Instead, use \Drupal\apic_app\Entity\ApplicationCredentials.', E_USER_DEPRECATED);

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\apic_app\CredentialsInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @deprecated in 8.x-2.0.39 (APIC v2018.4.1.10)
 * 
 * Defines a Credentials entity class.
 *
 * @ConfigEntityType(
 *   id = "apic_app_credentials",
 *   label = @Translation("Credentials"),
 *   label_singular = @Translation("Credentials"),
 *   label_plural = @Translation("Credentials"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("credentials"),
 *     plural = @Translation("credentials"),
 *   ),
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "apic_app_credentials",
 *   entity_keys = {
 *     "id" = "id",
 *     "app_url" = "app_url",
 *     "client_id" = "client_id",
 *     "name" = "name",
 *     "title" = "title",
 *     "summary" = "summary",
 *     "consumerorg_url" = "consumerorg_url",
 *     "cred_url" = "cred_url"
 *   }
 * )
 */
class Credentials extends ConfigEntityBase implements CredentialsInterface {

  /**
   * Drupal entity ID
   *
   * @var string
   */
  protected $id;

  /**
   * The application URL.
   *
   * @var string
   */
  protected $app_url;

  /**
   * The Client ID.
   *
   * @var string
   */
  protected $client_id;

  /**
   * The owning consumer organization URL.
   *
   * @var string
   */
  protected $consumerorg_url;

  /**
   * The credentials name.
   *
   * @var string
   */
  protected $name;

  /**
   * The title.
   *
   * @var string
   */
  protected $title;

  /**
   * The summary.
   *
   * @var string
   */
  protected $summary;

  /**
   * The credentials URL
   *
   * @var string
   */
  protected $cred_url;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function app_url() {
    return $this->app_url;
  }

  /**
   * {@inheritdoc}
   */
  public function client_id() {
    return $this->client_id;
  }


  /**
   * {@inheritdoc}
   */
  public function consumerorg_url() {
    return $this->consumerorg_url;
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->summary;
  }

  /**
   * {@inheritdoc}
   */
  public function cred_url() {
    return $this->cred_url;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'cred_url' => $this->cred_url,
      'title' => $this->title,
      'summary' => $this->summary,
      'name' => $this->name,
      'consumerorg_url' => $this->consumerorg_url,
      'client_id' => $this->client_id,
      'app_url' => $this->app_url,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Credentials entity.'))
      ->setReadOnly(TRUE);

    $fields['app_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Application URL'))
      ->setDescription(t('The URL of the Application these credentials belongs to'))
      ->setReadOnly(TRUE);

    $fields['client_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Client ID'))
      ->setDescription(t('The client ID for these credentials'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The credentials name'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The credentials title'))
      ->setReadOnly(TRUE);

    $fields['summary'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Summary'))
      ->setDescription(t('The credentials summary'))
      ->setReadOnly(TRUE);

    $fields['consumerorg_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Consumer Organization URL'))
      ->setDescription(t('The URL of the consumer organization which owns the Application'))
      ->setReadOnly(TRUE);

    $fields['cred_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Credentials URL'))
      ->setDescription(t('The URL of the credentials'))
      ->setReadOnly(TRUE);

    return $fields;
  }
}
