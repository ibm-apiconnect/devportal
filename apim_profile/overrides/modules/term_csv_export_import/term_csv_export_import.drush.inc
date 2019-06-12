<?php

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\term_csv_export_import\Controller\ExportController;
use Drupal\term_csv_export_import\Controller\ImportController;

function term_csv_export_import_drush_command() {

  $items['term_csv_export_import-export'] = [
    'description' => 'Export all taxonomies',
    'aliases' => ['taxonomy-export'],
    'options' => [
      'destination' => 'An arbitrary directory that should receive the exported files.',
    ],
  ];

  $items['term_csv_export_import-import'] = [
    'description' => 'Import taxonomies',
    'aliases' => ['taxonomy-import'],
    'options' => [
      'source' => 'An arbitrary directory that holds the taxonomy files.',
    ],
  ];
  return $items;
}

/**
 * Export all taxonomies as CSV files
 */
function drush_term_csv_export_import_export() {
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('taxonomy')) {
    if ($target = drush_get_option('destination')) {
      $destination_dir = $target;
      // It is important to be able to specify a destination directory that
      // does not exist yet, for exporting on remote systems
      drush_mkdir($destination_dir);
      $include_ids = FALSE;
      $include_headers = FALSE;
      $include_additional_fields = FALSE;

      // do export
      $taxonomies = taxonomy_vocabulary_get_names();
      foreach ($taxonomies as $taxonomy) {
        $export = new ExportController(\Drupal::service('entity_type.manager')->getStorage('taxonomy_term'), $taxonomy);
        $data = $export->execute($include_ids, $include_headers, $include_additional_fields);
        file_put_contents($destination_dir . '/' . $taxonomy . '.csv', $data);
      }
    }
    else {
      drush_set_error('DRUSH_TAXONOMY_EXPORT_FAILURE', dt('No destination directory provided.'));
    }
  }
}

/**
 * Import all CSV files in a given directory as taxonomies
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_term_csv_export_import_import() {
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('taxonomy')) {
    if ($target = drush_get_option('source')) {
      $source_dir = $target;

      // do import
      foreach (glob($source_dir . '/*.csv') as $file) {
        $path_parts = pathinfo($file);
        $taxonomy = $path_parts['filename'];
        $data = file_get_contents($file);

        $vid = strtolower($taxonomy);
        $vid = preg_replace('/[^a-z0-9_]+/', '_', $vid);
        $vid = preg_replace('/_+/', '_', $vid);

        // check vocabulary exists
        $vocab = Vocabulary::load($vid);
        if ($vocab === NULL) {
          $vocabulary = Vocabulary::create([
            'vid' => $vid,
            'machine_name' => $vid,
            'name' => $taxonomy,
          ]);
          $vocabulary->save();
        }

        $import = new ImportController($data, $taxonomy);
        $preserve_vocabularies = TRUE;

        $import->execute($preserve_vocabularies);
      }

    }
    else {
      drush_set_error('DRUSH_TAXONOMY_IMPORT_FAILURE', dt('No destination directory provided.'));
    }
  }
}