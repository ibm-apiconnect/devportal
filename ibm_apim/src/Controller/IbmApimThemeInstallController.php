<?php

namespace Drupal\ibm_apim\Controller;

use Drupal\system\Controller\ThemeController;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\UnmetDependenciesException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Leafo\ScssPhp\Compiler;

/**
 * Controller for theme handling.
 */
class IbmApimThemeInstallController extends ThemeController {


  /**
   * Uninstalls a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme or token is set in the request or when
   *   the token is invalid.
   */
  public function uninstall(Request $request) {
    $theme = $request->query->get('theme');
    $config = $this->config('system.theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      if (!empty($themes[$theme])) {
        // Do not uninstall the default or admin theme.
        if ($theme === $config->get('default') || $theme === $config->get('admin')) {
          drupal_set_message($this->t('%theme is the default theme and cannot be disabled.', ['%theme' => $themes[$theme]->info['name']]), 'error');
        }
        else {
          $this->themeHandler->uninstall([$theme]);
          drupal_set_message($this->t('The %theme theme has been disabled.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        drupal_set_message($this->t('The %theme theme was not found.', ['%theme' => $theme]), 'error');
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Installs a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme or token is set in the request or when
   *   the token is invalid.
   */
  public function install(Request $request) {
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      try {
        if ($this->themeHandler->install([$theme])) {
          $themes = $this->themeHandler->listInfo();
          drupal_set_message($this->t('The %theme theme has been enabled.', ['%theme' => $themes[$theme]->info['name']]));

          $auto_build_scss = $themes[$theme]->info['auto_build_scss'];
          if(isset($auto_build_scss) && $auto_build_scss) {
            $this->compile_scss($theme);
          }
        }
        else {
          drupal_set_message($this->t('The %theme theme was not found.', ['%theme' => $theme]), 'error');
        }
      }
      catch (PreExistingConfigException $e) {
        $config_objects = $e->flattenConfigObjects($e->getConfigObjects());
        drupal_set_message(
          $this->formatPlural(
            count($config_objects),
            'Unable to enable @extension, %config_names already exists in active configuration.',
            'Unable to enable @extension, %config_names already exist in active configuration.',
            [
              '%config_names' => implode(', ', $config_objects),
              '@extension' => $theme,
            ]),
          'error'
        );
      }
      catch (UnmetDependenciesException $e) {
        drupal_set_message($e->getTranslatedMessage($this->getStringTranslation(), $theme), 'error');
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Set the default theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme is set in the request.
   */
  public function setDefaultTheme(Request $request) {
    $config = $this->configFactory->getEditable('system.theme');
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      // Or try to install the theme.
      if (isset($themes[$theme]) || $this->themeHandler->install([$theme])) {
        $themes = $this->themeHandler->listInfo();

        $auto_build_scss = $themes[$theme]->info['auto_build_scss'];
        if(isset($auto_build_scss) && $auto_build_scss) {
          $this->compile_scss($theme);
        }

        // Set the default theme.
        $config->set('default', $theme)->save();

        // The status message depends on whether an admin theme is currently in
        // use: a value of 0 means the admin theme is set to be the default
        // theme.
        $admin_theme = $config->get('admin');
        if ($admin_theme != 0 && $admin_theme != $theme) {
          drupal_set_message($this->t('Please note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', [
            '%admin_theme' => $themes[$admin_theme]->info['name'],
            '%selected_theme' => $themes[$theme]->info['name'],
          ]));
        }
        else {
          drupal_set_message($this->t('%theme is now the default theme.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        drupal_set_message($this->t('The %theme theme was not found.', ['%theme' => $theme]), 'error');
      }

      return $this->redirect('system.themes_page');

    }
    throw new AccessDeniedHttpException();
  }

  /**
   * Deletes a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme or token is set in the request or when
   *   the token is invalid.
   */
  public function delete(Request $request) {
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();
      // Check if the specified theme is disabled
      if (!in_array($theme, $themes)) {
        $item_path = drupal_get_path('theme', $theme);
        if (isset($item_path)) {
          $this->file_delete_recursive($item_path);
          drupal_set_message($this->t('The %theme theme has been uninstalled.', ['%theme' => $theme]));
        }
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Private utility function to recursively delete a directory
   *
   * @param $path
   */
  private function file_delete_recursive($path) {
    if (isset($path)) {
      if (is_dir($path)) { // Path is directory
        $files = scandir($path);
        foreach ($files as $file) {
          if ($file != '.' && $file != '..') {
            $this->file_delete_recursive($path . '/' . $file); // Recursive call
          }
        }
        rmdir($path);
      }
      else {
        unlink($path); // Delete the file
      }
    }
  }

  /**
   * Private utility method to compile the SCSS for those subthemes
   *
   * @param $theme
   */
  private function compile_scss($theme) {
    if (isset($theme)) {
      $themes = $this->themeHandler->listInfo();
      // this theme is using scss and may need it build into css
      $theme_path = drupal_get_path('theme', $themes[$theme]->info['name']);
      $filename = $theme_path . '/compile-scss.yml';
      if (file_exists($filename)) {
        $scss_compile_settings = yaml_parse_file($filename);

        require_once DRUPAL_ROOT . '/vendor/leafo/scssphp/scss.inc.php';

        $scss = new Compiler();
        // add the base_theme scss paths
        $scss->addImportPath(drupal_get_path('theme', 'connect_theme') . '/bootstrap/stylesheets');
        $scss->addImportPath(drupal_get_path('theme', 'connect_theme') . '/bootstrap/stylesheets/bootstrap');
        $scss->addImportPath(drupal_get_path('theme', 'connect_theme') . '/scss');

        // add specified theme paths
        $scss->addImportPath($theme_path);

        $import_paths = $scss_compile_settings["import-paths"];

        foreach ($import_paths as &$import_path) {
          if (preg_match('/^[a-zA-Z0-9_\-\/.]+$/', $import_path)) {
            $scss->addImportPath(preg_replace('#/+#', '/', join('/', array($theme_path, $import_path))));
          }
        }

        $input_scss = $scss_compile_settings["input-scss"];
        if (!preg_match('/^[a-z0-9_\-.]+$/', $input_scss)) {
          $input_scss = "scss/overrides.scss";
        }

        $scssIn = file_get_contents(preg_replace('#/+#', '/', join('/', array($theme_path, $input_scss))));
        $cssOut = $scss->compile($scssIn);

        $output_css = $scss_compile_settings["output-css"];
        if (!preg_match('/^[a-z0-9_\-.]+$/', $output_css)) {
          $output_css = "css/style.css";
        }

        $output_css_dir = preg_replace('#/+#', '/', join('/', array($theme_path, dirname($output_css))));
        $output_css_file = basename($output_css);

        if (!is_dir($output_css_dir)) {
          mkdir($output_css_dir, 0755, TRUE);
        }

        $output_file = preg_replace('#/+#', '/', join('/', array($output_css_dir, $output_css_file)));

        file_put_contents($output_file, $cssOut);

      }
    }
  }
}
