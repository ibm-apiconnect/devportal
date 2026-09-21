<?php


namespace Drupal\consumerorg;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the consumer organization member/invite schema handler.
 */
class ConsumerOrgMemberInviteSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if (in_array($field_name, ['email', 'user_url', 'user'])) {
      foreach ($schema['fields'] as $column_name => &$column_spec) {
        $column_spec['not null'] = FALSE;
      }
    }

    // Add indexes for commonly queried fields.
    switch ($field_name) {
      case 'type':
      case 'org_url':
      case 'user_url':
      case 'email':
      case 'url':
      case 'consumerorg_nid':
      case 'state':
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
        break;

      default:
        break;
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Add composite indexes for common query patterns.
    if (isset($schema[$entity_type->getBaseTable()])) {
      // Index for filtering by org and type
      $schema[$entity_type->getBaseTable()]['indexes']['consumerorg_type'] = ['consumerorg_nid', 'type'];
      
      // Index for filtering by org and state
      $schema[$entity_type->getBaseTable()]['indexes']['consumerorg_state'] = ['consumerorg_nid', 'state'];
      
      // Index for filtering by org, type, and state (most common query)
      $schema[$entity_type->getBaseTable()]['indexes']['consumerorg_type_state'] = ['consumerorg_nid', 'type', 'state'];
    }

    return $schema;
  }

}