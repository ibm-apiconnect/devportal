<?php


namespace Drupal\consumerorg;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class PaymentMethodSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    // Add an index to the relevant column
    switch ($field_name) {
      case 'consumerorg_url':
        $this->addSharedTableFieldIndex($storage_definition, $schema);
        break;
    }

    return $schema;
  }
}