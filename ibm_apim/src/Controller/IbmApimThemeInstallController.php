<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ibm_apim\Service\Utils;
use Drupal\system\Controller\ThemeController;
use ScssPhp\ScssPhp\Compiler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class IbmApimThemeInstallController
 *
 * @package Drupal\ibm_apim\Controller
 */
class IbmApimThemeInstallController extends ThemeController {

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  private Utils $utils;

  /**
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeList;

  /**
   * IbmApimThemeInstallController constructor.
   *
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   * @param \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(Utils                   $utils,
                              ThemeHandlerInterface   $theme_handler,
                              ThemeExtensionList      $theme_list,
                              ThemeInstallerInterface $theme_installer,
                              ConfigFactoryInterface  $config_factory, MessengerInterface $messenger) {
    $this->utils = $utils;
    $this->themeInstaller = $theme_installer;
    $this->themeList = $theme_list;
    $this->messenger = $messenger;
    parent::__construct($theme_handler, $theme_list, $config_factory, $theme_installer);
  }

  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('ibm_apim.utils'),
      $container->get('theme_handler'),
      $container->get('extension.list.theme'),
      $container->get('theme_installer'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

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
  public function uninstall(Request $request): RedirectResponse {
    $theme = $request->query->get('theme');
    $config = $this->config('system.theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      if (!empty($themes[$theme])) {
        // Do not uninstall the default or admin theme.
        if ($theme === $config->get('default') || $theme === $config->get('admin')) {
          $this->messenger->addError($this->t('%theme is the default theme and cannot be disabled.', ['%theme' => $themes[$theme]->info['name']]));
        }
        else {
          $this->themeInstaller->uninstall([$theme]);
          $this->messenger->addMessage($this->t('The %theme theme has been disabled.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        $this->messenger->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Installs a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException|\Drupal\Core\Extension\MissingDependencyException
   */
  public function install(Request $request): RedirectResponse {
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      try {
        if ($this->themeInstaller->install([$theme])) {
          $themes = $this->themeHandler->listInfo();
          $this->messenger->addMessage($this->t('The %theme theme has been enabled.', ['%theme' => $themes[$theme]->info['name']]));

          if (isset($themes[$theme]->info['auto_build_scss']) && $themes[$theme]->info['auto_build_scss']) {
            $this->compile_scss($theme);
          }
        }
        else {
          $this->messenger->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
        }
      } catch (PreExistingConfigException $e) {
        $config_objects = $e::flattenConfigObjects($e->getConfigObjects());
        $this->messenger->addError(
          $this->formatPlural(
            count($config_objects),
            'Unable to enable @extension, %config_names already exists in active configuration.',
            'Unable to enable @extension, %config_names already exist in active configuration.',
            [
              '%config_names' => implode(', ', $config_objects),
              '@extension' => $theme,
            ]));
      } catch (UnmetDependenciesException $e) {
        $this->messenger->addError($e->getTranslatedMessage($this->getStringTranslation(), $theme));
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Set the default theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException|\Drupal\Core\Extension\MissingDependencyException
   */
  public function setDefaultTheme(Request $request): RedirectResponse {
    $config = $this->configFactory->getEditable('system.theme');
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      // Or try to install the theme.
      if (isset($themes[$theme]) || $this->themeInstaller->install([$theme])) {
        $themes = $this->themeHandler->listInfo();

        if (isset($themes[$theme]->info['auto_build_scss']) && $themes[$theme]->info['auto_build_scss']) {
          $this->compile_scss($theme);
        }

        // Set the default theme.
        $config->set('default', $theme)->save();

        // The status message depends on whether an admin theme is currently in
        // use: a value of 0 means the admin theme is set to be the default
        // theme.
        $admin_theme = $config->get('admin');
        if ((int) $admin_theme !== 0 && $admin_theme !== $theme) {
          $this->messenger->addMessage($this->t('Please note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', [
            '%admin_theme' => $themes[$admin_theme]->info['name'],
            '%selected_theme' => $themes[$theme]->info['name'],
          ]));
        }
        else {
          $this->messenger->addMessage($this->t('%theme is now the default theme.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        $this->messenger->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
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
  public function delete(Request $request): RedirectResponse {
    $inputTheme = $request->query->get('theme');

    if (isset($inputTheme) && !empty($inputTheme)) {
      $themes = \Drupal::service('ibm_apim.utils')->getDisabledCustomExtensions('theme');
      // Check if the specified module is a disabled custom module
      if (array_key_exists($inputTheme, $themes)) {
        \Drupal::service('ibm_apim.module')->deleteExtensionOnFileSystem('theme', [$inputTheme], FALSE);
        $this->messenger->addMessage($this->t('The %theme theme has been uninstalled.', ['%theme' => $inputTheme]));
      }
      else {
        $this->messenger->addError($this->t('Only custom themes can be uninstalled. %theme is not a custom theme. If it is a Custom theme then it may still be active', ['%theme' => $inputTheme]));
      }
    } else {
      $this->messenger->addError($this->t('There was an error deleting the theme. Please contact your system administrator.'));
    }

    return $this->redirect('system.themes_page');
  }

  /**
   * Private utility method to compile the SCSS for those subthemes
   *
   * @param $theme
   */
  public function compile_scss($theme): void {
    if (isset($theme)) {
      $themes = $this->themeHandler->listInfo();
      // this theme is using scss and may need it build into css
      $theme_path = \Drupal::service('extension.list.theme')->getPath($themes[$theme]->info['name']);
      $filename = $theme_path . '/compile-scss.yml';
      if (file_exists($filename)) {
        $scss_compile_settings = yaml_parse_file($filename);

        require_once DRUPAL_ROOT . '/vendor/scssphp/scssphp/scss.inc.php';

        $scss = new Compiler();
        // add the base_theme scss paths
        $scss->addImportPath(\Drupal::service('extension.list.theme')->getPath('connect_theme') . '/bootstrap/stylesheets');
        $scss->addImportPath(\Drupal::service('extension.list.theme')->getPath('connect_theme') . '/bootstrap/stylesheets/bootstrap');
        $scss->addImportPath(\Drupal::service('extension.list.theme')->getPath('connect_theme') . '/scss');

        // add specified theme paths
        $scss->addImportPath($theme_path);

        $import_paths = $scss_compile_settings['import-paths'];

        foreach ($import_paths as &$import_path) {
          if (preg_match('/^[a-zA-Z0-9_\-\/.]+$/', $import_path)) {
            $scss->addImportPath(preg_replace('#/+#', '/', implode('/', [$theme_path, $import_path])));
          }
        }
        unset($import_path);

        $input_scss = $scss_compile_settings['input-scss'];
        if (!preg_match('/^[a-z0-9_\-.]+$/', $input_scss)) {
          $input_scss = 'scss/overrides.scss';
        }

        $scssIn = file_get_contents(preg_replace('#/+#', '/', implode('/', [$theme_path, $input_scss])));
        $cssOut = $scss->compileString($scssIn)->getCss();

        $output_css = $scss_compile_settings['output-css'];
        if (!preg_match('/^[a-z0-9_\-.]+$/', $output_css)) {
          $output_css = 'css/style.css';
        }

        $output_css_dir = preg_replace('#/+#', '/', implode('/', [$theme_path, dirname($output_css)]));
        $output_css_file = basename($output_css);

        if (!is_dir($output_css_dir) && !mkdir($output_css_dir, 0755, TRUE) && !is_dir($output_css_dir)) {
          throw new \RuntimeException(sprintf('Directory "%s" was not created', $output_css_dir));
        }

        $output_file = preg_replace('#/+#', '/', implode('/', [$output_css_dir, $output_css_file]));

        file_put_contents($output_file, $cssOut);

        if (array_key_exists('mail-import-paths', $scss_compile_settings) && array_key_exists('mail-input-scss', $scss_compile_settings) && array_key_exists('mail-output-css', $scss_compile_settings)) {
          // email SCSS
          $scss = new Compiler();
          // add the base_theme scss paths
          $scss->addImportPath(\Drupal::service('extension.list.theme')->getPath('connect_theme') . '/scss');

          // add specified theme paths
          $scss->addImportPath($theme_path);

          $mail_import_paths = $scss_compile_settings['mail-import-paths'];

          foreach ($mail_import_paths as &$mail_import_path) {
            if (preg_match('/^[a-zA-Z0-9_\-\/.]+$/', $mail_import_path)) {
              $scss->addImportPath(preg_replace('#/+#', '/', implode('/', [$theme_path, $mail_import_path])));
            }
          }
          unset($mail_import_path);

          $mail_input_scss = $scss_compile_settings['mail-input-scss'];
          if (!preg_match('/^[a-z0-9_\-.]+$/', $mail_input_scss)) {
            $mail_input_scss = 'scss/mail-overrides.scss';
          }

          $mailScssIn = file_get_contents(preg_replace('#/+#', '/', implode('/', [$theme_path, $mail_input_scss])));
          $mailCssOut = $scss->compileString($mailScssIn)->getCss();

          $mail_output_css = $scss_compile_settings['mail-output-css'];
          if (!preg_match('/^[a-z0-9_\-.]+$/', $mail_output_css)) {
            $mail_output_css = 'css/mail.css';
          }

          $mail_output_css_dir = preg_replace('#/+#', '/', implode('/', [$theme_path, dirname($mail_output_css)]));
          $mail_output_css_file = basename($mail_output_css);

          if (!is_dir($mail_output_css_dir) && !mkdir($mail_output_css_dir, 0755, TRUE) && !is_dir($mail_output_css_dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $mail_output_css_dir));
          }

          $mail_output_file = preg_replace('#/+#', '/', implode('/', [$mail_output_css_dir, $mail_output_css_file]));

          file_put_contents($mail_output_file, $mailCssOut);

        }
      }
    }
  }

}
