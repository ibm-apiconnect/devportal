<?php

namespace Drupal\drupal_helpers;

/**
 * Class Theme.
 *
 * @package Drupal\drupal_helpers
 */
class Theme extends System {

  /**
   * Sets a theme as the default.
   */
  public static function setDefault($theme) {
    variable_set('theme_default', $theme);
  }

  /**
   * Sets a theme as the admin theme.
   */
  public static function setAdmin($theme) {
    variable_set('admin_theme', $theme);
  }

  /**
   * Enables a theme and performs some error checking.
   *
   * @param string $theme
   *   Theme machine name.
   *
   * @return bool
   *   Returns TRUE if theme was enabled successfully, \DrupalUpdateException
   *   is thrown otherwise.
   *
   * @throws \DrupalUpdateException
   *   Throws exception if theme was not enabled.
   */
  public static function enable($theme) {
    if (self::isEnabled($theme, 'theme')) {
      General::messageSet(format_string('Theme "@theme" already exists - Aborting!', [
        '@theme' => $theme,
      ]));

      return TRUE;
    }
    theme_enable([$theme]);

    // Double check that the theme installed.
    if (self::isEnabled($theme, 'theme')) {
      General::messageSet(format_string('Theme "@theme" was successfully enabled.', [
        '@theme' => $theme,
      ]));

      return TRUE;
    }

    throw new \DrupalUpdateException(format_string('Theme "@theme" could not enabled.', [
      '@theme' => $theme,
    ]));
  }

  /**
   * Disables a theme and performs some error checking.
   *
   * @param string $theme
   *   Theme machine name.
   *
   * @return bool
   *   Returns TRUE if theme was disabled successfully, \DrupalUpdateException
   *   is thrown otherwise.
   *
   * @throws \DrupalUpdateException
   *   Throws exception if theme was not disabled.
   */
  public static function disable($theme) {
    if (self::isDisabled($theme, 'theme')) {
      General::messageSet(format_string('Theme "@theme" is already disabled - Aborting!', [
        '@theme' => $theme,
      ]));

      return TRUE;
    }

    theme_disable([$theme]);

    if (self::isDisabled($theme, 'theme')) {
      General::messageSet(format_string('Theme "@theme" was successfully disabled.', [
        '@theme' => $theme,
      ]));

      return TRUE;
    }

    throw new \DrupalUpdateException(format_string('Theme "@theme" could not disabled.', [
      '@theme' => $theme,
    ]));
  }

}
