<?php
namespace Drupal\noopener_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

    /**
     * SettingsForm constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The config factory.
     */
    public function __construct(ConfigFactoryInterface $config_factory) {
      parent::__construct($config_factory);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
      return new static(
        $container->get('config.factory')
      );
    }

    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames()
    {
        return ['noopener_filter.settings'];
    }

    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'noopener_filter_settings_form';
    }

    /**
     * Builds up the form.
     *
     * @param array $form
     *   The form itself.
     * @param FormStateInterface $form_state
     *   The state of the form.
     * @return array
     *   The form itself.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('noopener_filter.settings');

        $form['noopener_filter'] = [
            '#type' => 'fieldset',
            '#title' => 'Noopener tickets'
        ];

        $form['noopener_filter']['filter_links'] = [
            '#type' => 'checkbox',
            '#title' => 'Filter links',
            '#description' => t('Filter link\'s through the hook_link_alter'),
            '#default_value' => $config->get('filter_links'),
            '#required' => FALSE
        ];

        $form['actions']['submit'] = [
            '#type'  => 'submit',
            '#value' => $this->t('Save'),
        ];

        return $form;
    }

    /**
     * Submits the form value.
     *
     * @param array $form
     *   The form itself.
     * @param FormStateInterface $form_state
     *   The state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('noopener_filter.settings')->set('filter_links', $form_state->getValue('filter_links'))->save();
        parent::submitForm($form, $form_state);
    }
}
