<?php

namespace Drupal\Tests\ibm_apim\Unit\utils;

/**
 * Utility class to count translations in Twig files
 */
class TwigTranslationCounter {

  /**
   * Count the number of translations in a Twig file
   *
   * @param string $content The content of the Twig file
   *
   * @return int The number of translations found
   */
  public static function countTranslations(string $content): int {
    // Match {% trans %}...{% endtrans %} pattern
    preg_match_all('/\{%\s*trans\s*%\}.*?\{%\s*endtrans\s*%\}/s', $content, $matches);
    return count($matches[0]);
  }

  /**
   * Count the number of translations in a directory of Twig files
   *
   * @param string $directory The directory containing Twig files
   *
   * @return array An array with the count of translations per file and a total count
   */
  public static function countTranslationsInDirectory(string $directory): array {
    $result = [
      'files' => [],
      'total' => 0
    ];

    if (!is_dir($directory)) {
      return $result;
    }

    $files = glob($directory . '/*.html.twig');
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $count = self::countTranslations($content);
      $filename = basename($file);
      $result['files'][$filename] = $count;
      $result['total'] += $count;
    }

    // Also check subdirectories
    $subdirs = glob($directory . '/*', GLOB_ONLYDIR);
    foreach ($subdirs as $subdir) {
      $subResult = self::countTranslationsInDirectory($subdir);
      foreach ($subResult['files'] as $filename => $count) {
        $result['files'][basename($subdir) . '/' . $filename] = $count;
      }
      $result['total'] += $subResult['total'];
    }

    return $result;
  }
}
