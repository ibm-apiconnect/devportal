<?php


namespace Drupal\apic_app;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class ApplicationSubscriptionSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    // Add an index to the relevant column
    switch ($field_name) {
      case 'uuid':
      case 'consumerorg_url':
      case 'app_url':
      case 'product_url':
      case 'plan_title':
      case 'product_title':
      case 'plan_title_missing':
      case 'product_title_missing':
        $this->addSharedTableFieldIndex($storage_definition, $schema);
        break;
    }

    return $schema;
  }

}
