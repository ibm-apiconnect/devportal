<?php


/**
 * @file
 * Provides APIC integration.
 */
namespace Drupal\ibm_apic_getting_started\Hook;

use Drupal\Core\Hook\Attribute\Hook;


class IbmApicGettingStartedHooks {
  /**
   * Add twig templates
   *
   * @param $existing
   * @param $type
   * @param $theme
   * @param $path
   *
   * @return array
   */

  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'getting_started_page' => [
        'variables' => [
          'tutorials' => [],
        ],
      ],
      'tutorial_base' => [
        'variables' => [
          'tutorial' => NULL,
          'images_path' => NULL,
        ],
        'template' => 'tutorial-base',
      ],
      // The off-canvas panel content for customizing portal theme.
      'customizing_portal_theme' => [
        'variables' => [
          'tutorial' => NULL,
          'images_path' => NULL,
        ],
        'template' => 'customizing-portal-theme',
      ],
      'installing_custom_module' => [
        'variables' => [
          'tutorial' => NULL,
          'images_path' => NULL,
        ],
        'template' => 'installing-custom-module',
      ],
      'customizing_hero_image' => [
        'variables' => [
            'tutorial' => NULL,
            'images_path' => NULL,
          ],
      'template' => 'customizing-hero-image',
      ],
      'customizing_front_page' => [
        'variables' => [
          'tutorial' => NULL,
          'images_path' => NULL,
        ],
        'template' => 'customizing-front-page',
      ],
      'taxonomy' => [
        'variables' => [
          'tutorial' => NULL,
          'images_path' => NULL,
        ],
        'template' => 'taxonomy',
      ],
      'email-subscribers' => [
        'variables' => [
          'tutorial' => NULL,
          'images_path' => NULL,
        ],
        'template' => 'email-subscribers',
      ],
      'content_security_policy' => [
        'variables' => [
          'tutorial' => NULL,
          'images_path' => NULL,
        ],
        'template' => 'content-security-policy',
      ]
    ];
  }

  /**
   * Implements hook_page_attachments().
   *
   * Ensure AJAX dialog + off-canvas assets are available on all routes
   * for admin users, except for /user/* paths.
   */
    #[Hook('page_attachments')]
    public function pageAttachments(array &$attachments) {
      // Always attach dialog libraries required for the tutorial panel.
      $attachments['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $attachments['#attached']['library'][] = 'core/drupal.dialog.off_canvas';

      $current_user = \Drupal::currentUser();
      $current_path = \Drupal::service('path.current')->getPath();

      // Attach clear_storage library on login page to reset tutorial state
      if ($current_path === '/user/login') {
        $attachments['#attached']['library'][] = 'ibm_apic_getting_started/clear_storage';
        return;
      }

      // Skip any other path starting with /user (register, account, etc.)
      if (str_starts_with($current_path, '/user')) {
        return;
      }

      // Attach tutorial library for admin users only.
      if ($current_user->hasPermission('access administration pages')) {
        $attachments['#attached']['library'][] = 'ibm_apic_getting_started/getting_started';
      }
    }
 }
 