<?php

namespace Drupal\select_translation\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Views select translation filter handler.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("select_translation_filter")
 */
class SelectTranslation extends FilterPluginBase {
  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreLine
  public $no_operator = TRUE;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Select translation filter');
  }

  /**
   * Returns an array of filter option defaults.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['value']['default'] = 'default';
    $options['priorities']['default'] = '';
    $options['default_language_only']['default'] = 0;
    $options['include_content_with_unpublished_translation']['default'] = 0;
    return $options;
  }

  /**
   * Returns admin summary of the filter options.
   */
  public function adminSummary() {
    $options = [
      'original' => t('Original node language fallback'),
      'default' => t('Default site language fallback'),
      'list' => t('Custom language priority'),
    ];
    return $options[$this->value];
  }

  /**
   * Returns a form with configurable options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['mode'] = [
      '#type' => 'fieldset',
      '#title' => t('Select translation selection mode'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['value'] = [
      '#fieldset' => 'mode',
      '#type' => 'radios',
      '#options' => [
        'original' => t('Use the current interface language; if not available use the original node language'),
        'default'  => t('Use the current interface language; if not available use the default site language; if not available use the original node language'),
        'list'     => t('Custom language priorities'),
      ],
      '#default_value' => $this->value,
    ];

    $form['priorities'] = [
      '#fieldset' => 'mode',
      '#type' => 'textfield',
      '#title' => t('Language priorities'),
      '#description' => t('<p>If the selection mode is set to "Custom language priorities",
                           a comma separated list of language codes can be specified.<br/>
                           The filter will then return the node in the first available language
                           in that list; falling back to the original node language if no match was found.</p>
                           Some special values are recognized:
                           <ul>
                             <li>"<em>current</em>" will be replaced with the current interface language;</li>
                             <li>"<em>default</em>" will be replaced with the default site language;</li>
                             <li>"<em>original</em>" will be replaced with the original node language.</li>
                            </ul>
                           Example:<br/><em>en,fr,current,default,original</em><br/>
                           This will return:
                           <ol>
                             <li>the version in English, if available;</li>
                             <li>if not, then the version in French, if available;</li>
                             <li>if not, then the version in the current interface language, if available;</li>
                             <li>if not, then the version in the default site language, if available;</li>
                             <li>if none are available it will return the original node version.</li>
                          </ol>'),
      '#default_value' => !empty($this->options['priorities']) ? $this->options['priorities'] : '',
      '#states' => [
        'enabled' => [
          ':input[name="options[value]"]' => ['value' => 'list'],
        ],
      ],
    ];

    $form['default_language_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Display default language content *only*, if the currently selected user language is the default site language.'),
      '#description' => t("When you check this option, the order chosen above will be ignored when current language = site default language,
        instead it will only show translations for the default language. "),
      '#default_value' => !empty($this->options['default_language_only']) ? $this->options['default_language_only'] : 0,
    ];

    $form['include_content_with_unpublished_translation'] = [
      '#type' => 'checkbox',
      '#title' => t('Return content in the site default language when a translation for the current language *does* exist, but it is unpublished.'),
      '#description' => t('When you check this option, in addition to the order chosen above, content will be shown in the default site language in the event that the translation in the current language is unpublished.<br/>
      <strong>NOTE</strong>: This option assumes that the view is already filtering out unpublished content with the <code>Published (=Yes)</code> criterion, otherwise both the published and unpublished node will be displayed.'),
      '#default_value' => !empty($this->options['include_content_with_unpublished_translation']) ? $this->options['include_content_with_unpublished_translation'] : 0,
    ];
  }

  /**
   * Executes the query.
   *
   * Use a query that doesn't use correlated sub-queries.
   * Thus executing faster for larger data sets.
   */
  public function query() {
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $current_language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId();

    // Limit the language list to default only, if the option was selected and
    // default_language == current_language.
    if ($this->options['default_language_only'] && $default_language == $current_language) {
      $list = [$default_language];
    }
    // Otherwise prepare the language order list.
    else {
      if ($this->value == 'list') {
        $mode = $this->options['priorities'];
      }
      else {
        $mode = $this->value;
      }

      $list = select_translation_parse_mode($mode);
    }

    // Now build the query.
    $query = $this->query;
    $alias = $query->ensureTable('node_field_data');
    $condition_holder = new Condition('OR');

    $i = 0;
    $exclude = [];
    // Now go through each language.
    foreach ($list as $language) {
      $and = new Condition('AND');

      // Before adding the currently processed language exclude the ones
      // already processed in previous iterations.
      foreach ($exclude as $e) {
        $and->isNull("$e.nid");
      }

      if ($language != 'original') {
        // Create a Views join on the node table, and add it as a relationship.
        // This is used to find if there are translations of a certain language.
        $sub_query_alias = 'nt' . $i;
        $exclude[] = $sub_query_alias;
        ++$i;

        // Because domain module uses node_access, and rewrites the query to add
        // exists clauses for each left joined node table (maybe specific to all
        // node access modules), thus breaking the listing, we wrap the table in
        // a sub-query, avoiding the exists clause.
        $join = $this->nodeAccessJoin($language, $alias, $sub_query_alias);

        // Add the join as a relationship.
        $query->addRelationship($sub_query_alias, $join, 'node_field_data');

        // Include nodes of specified language.
        $and->condition("$alias.langcode", $language);
      }
      else {
        // Include nodes that are the base of a translation (aka original).
        $and->condition("$alias.default_langcode", 1);
      }

      $condition_holder->condition($and);
    }

    // Include site default nodes in place of unpublished translations.
    if ($this->options['include_content_with_unpublished_translation']) {
      $sub_query_alias = 'nt' . $i;

      // Join with the currently selected language.
      $join = $this->nodeAccessJoin($current_language, 'node_field_data', $sub_query_alias);

      // Add the join as a relationship.
      $query->addRelationship($sub_query_alias, $join, 'node_field_data');

      // The default language node will be selected, if the current language
      // (translated) node is unpublished.
      $and = (new Condition('AND'))
        ->condition("$alias.langcode", $default_language)
        ->condition("$sub_query_alias.status", Drupal\node\NodeInterface::NOT_PUBLISHED);

      $condition_holder->condition($and);
    }

    // Add in the conditions.
    $query->addWhere($this->options['group'], $condition_holder);
  }

  /**
   * Join to the node table where the nodes have the given language.
   *
   * @param string $language
   *   The language of the nodes that should be retrieved.
   * @param string $alias
   *   The alias of the main node table.
   * @param string $sub_query_alias
   *   The alias of the sub query node table.
   */
  private function nodeAccessJoin($language, $alias, $sub_query_alias) {
    $sub_query = \Drupal::database()->select('node_field_data', $sub_query_alias);
    $sub_query->addField($sub_query_alias, 'nid');
    $sub_query->addfield($sub_query_alias, 'status');
    $sub_query->addField($sub_query_alias, 'langcode');
    $sub_query->condition("$sub_query_alias.langcode", $language);

    $configuration = [
      'table' => $sub_query,
      'field' => 'nid',
      'left_table' => $alias,
      'left_field' => 'nid',
      'operator' => '=',
    ];
    return Views::pluginManager('join')->createInstance('standard', $configuration);
  }

}
