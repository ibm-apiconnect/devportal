<?php


namespace Drupal\apic_app;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class ApplicationCredentialsSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    // Add an index to the relevant column
    switch ($field_name) {
      case 'uuid':
      case 'app_url':
        $this->addSharedTableFieldIndex($storage_definition, $schema);
        break;
    }

    return $schema;
  }

}