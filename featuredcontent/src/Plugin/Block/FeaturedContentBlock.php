<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\featuredcontent\Plugin\Block;

use Drupal\apic_api\Api;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\product\Product;

/**
 * Provides a Featured Content Block
 *
 * @Block(
 *   id = "featuredcontent",
 *   admin_label = @Translation("Featured Content block"),
 * )
 */
class FeaturedContentBlock extends BlockBase {

  /**
   * Class constant for API node type.
   */
  protected const TYPE_API = 'api';

  /**
   * Class constant for product node type.
   */
  protected const TYPE_PRODUCT = 'product';

  /**
   * Class constant for most recently updated.
   */
  protected const CONST_UPDATED = 'UPDATED';

  /**
   * Class constant for most recently created.
   */
  protected const CONST_CREATED = 'CREATED';

  /**
   * Class constant for oldest created.
   */
  protected const CONST_OLDEST = 'OLDEST';

  /**
   * Class constant for oldest updated.
   */
  protected const CONST_STALEST = 'STALEST';

  /**
   * Class constant for random.
   */
  protected const CONST_RANDOM = 'RANDOM';

  /**
   * Class constant for alphabetical by title.
   */
  protected const CONST_TITLE = 'TITLE';

  /**
   * Class constant for reverse alphabetical by title.
   */
  protected const CONST_TITLEREV = 'TITLEREV';

  /**
   * Class constant for manually specifying the nodes.
   */
  protected const CONST_CUSTOM = 'CUSTOM';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'nodeType' => static::TYPE_PRODUCT,
      'numberOfTiles' => 6,
      'selectionType' => static::CONST_CREATED,
      'customNodes' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $form = parent::blockForm($form, $form_state);

    // Start laying out the form
    $form['intro'] = [
      '#markup' => $this->t('<p>Configure the node selection settings for this Featured Content block. For each node the block will display its image, title and summary. If no summary is present then a truncated form of the description field will be used instead.</p>
<p>To modify the content shown for a node either Edit that node in the portal or edit the YAML document for that node in the API Manager.</p>
<p>Note that the node selection is per user, and so depending on the visibility settings, different users may see different nodes.</p>'),
      '#weight' => -10,
    ];

    $form['nodeType'] = [
      '#type' => 'select',
      '#options' => [
        static::TYPE_PRODUCT => $this->t('Product'),
        static::TYPE_API => $this->t('API'),
      ],
      '#title' => $this->t('Node type'),
      '#description' => $this->t('Feature APIs or Products?'),
      '#default_value' => $this->configuration['nodeType'],
      '#required' => TRUE,
      '#weight' => 30,
    ];

    $form['numberOfTiles'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of tiles to display'),
      '#default_value' => $this->configuration['numberOfTiles'],
      '#description' => $this->t('How many tiles should be shown?'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 20,
      '#step' => 1,
      '#weight' => 40,
    ];

    $form['selectionType'] = [
      '#type' => 'select',
      '#options' => [
        static::CONST_UPDATED => $this->t('Most recently updated'),
        static::CONST_CREATED => $this->t('Most recently created'),
        static::CONST_STALEST => $this->t('Least recently updated'),
        static::CONST_OLDEST => $this->t('Least recently created'),
        static::CONST_TITLE => $this->t('Alphabetical by name'),
        static::CONST_TITLEREV => $this->t('Reverse alphabetical by name'),
        static::CONST_RANDOM => $this->t('Random'),
        static::CONST_CUSTOM => $this->t('Custom'),
      ],
      '#title' => $this->t('Node selection algorithm'),
      '#description' => $this->t('Select how the featured content should be chosen. For example, based on creation or modification time, name, or random.'),
      '#default_value' => $this->configuration['selectionType'],
      '#required' => TRUE,
      '#weight' => 50,
    ];

    $form['customNodes'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Custom nodes'),
      '#description' => $this->t('Manually specify the nodes to feature. \',\' separated. (This field is only used if using \'Custom\' node selection.)'),
      '#default_value' => $this->configuration['customNodes'],
      '#tags' => TRUE,
      '#selection_settings' => [
        'target_bundles' => ['api', 'product'],
      ],
      '#weight' => 60,
    ];

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    parent::blockValidate($form, $form_state);

    $nodeType = $form_state->getValue('nodeType');
    if ($nodeType === NULL || empty($nodeType) || ($nodeType !== static::TYPE_API && $nodeType !== static::TYPE_PRODUCT)) {
      $form_state->setErrorByName('nodeType', $this->t('Node type must be set to either \'api\' or \'product\'.'));
    }
    $numberOfTiles = $form_state->getValue('numberOfTiles');
    if ($numberOfTiles === NULL || empty($numberOfTiles)) {
      $form_state->setErrorByName('numberOfTiles', $this->t('Number of tiles must be an integer between 1 and 20.'));
    }
    else {
      $numberOfTiles_int = (int) $numberOfTiles;
      if ($numberOfTiles_int === NULL || !\is_int($numberOfTiles_int) || $numberOfTiles_int > 21 || $numberOfTiles_int < 1) {
        $form_state->setErrorByName('numberOfTiles', $this->t('Number of tiles must be an integer between 1 and 20.'));
      }
    }

    $selectionType = $form_state->getValue('selectionType');
    if ($selectionType === NULL || empty($selectionType)) {
      $form_state->setErrorByName('selectionType', $this->t('Node selection algorithm must be set.'));
    }

    $customNodes = $form_state->getValue('customNodes');
    if ($selectionType !== NULL && $selectionType === static::CONST_CUSTOM && ($customNodes === NULL || empty($customNodes))) {
      $form_state->setErrorByName('customNodes', $this->t('If using Custom node selection then \'Custom nodes\' must be specified.'));
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * {@inheritdoc}
   * This function runs when the config / edit form is submitted
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    parent::blockSubmit($form, $form_state);

    $this->configuration['nodeType'] = $form_state->getValue('nodeType');
    $this->configuration['numberOfTiles'] = (int) $form_state->getValue('numberOfTiles');
    $this->configuration['selectionType'] = $form_state->getValue('selectionType');
    $this->configuration['customNodes'] = $form_state->getValue('customNodes');

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $nodes = [];
    $utils = \Drupal::service('ibm_apim.utils');
    if (strtolower($this->configuration['nodeType']) === static::TYPE_API) {
      $nodeType = static::TYPE_API;
    }
    else {
      $nodeType = static::TYPE_PRODUCT;
    }
    // force it to be more than 0 and less than 20
    if (\is_int($this->configuration['numberOfTiles']) && $this->configuration['numberOfTiles'] > 0) {
      if ($this->configuration['numberOfTiles'] < 21) {
        $count = $this->configuration['numberOfTiles'];
      }
      else {
        $count = 20;
      }
    }
    else {
      $count = 3;
    }

    if (!isset($this->configuration['selectionType']) || empty($this->configuration['selectionType'])) {
      $this->configuration['selectionType'] = static::CONST_CREATED;
    }

    $config = \Drupal::config('ibm_apim.settings');
    $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
    if ($ibmApimShowPlaceholderImages === NULL) {
      $ibmApimShowPlaceholderImages = TRUE;
    }
    $ibmApimShowVersions = (boolean) $config->get('show_versions');
    if ($ibmApimShowVersions === NULL) {
      $ibmApimShowVersions = TRUE;
    }
    $moduleHandler = \Drupal::service('module_handler');

    // build up query
    if ($nodeType !== NULL && strtoupper($this->configuration['selectionType']) !== static::CONST_CUSTOM) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', $nodeType);
      $query->condition('status', 1);
      $query->range(0, $count);
      // only include published products
      if ($nodeType === static::TYPE_PRODUCT) {
        $query->condition('product_state.value', 'published');
      }
      switch (strtoupper($this->configuration['selectionType'])) {
        case static::CONST_UPDATED:
          $query->sort('changed', 'ASC');
          break;
        case static::CONST_CREATED:
          $query->sort('created', 'ASC');
          break;
        case static::CONST_OLDEST:
          $query->sort('created', 'DESC');
          break;
        case static::CONST_STALEST:
          $query->sort('changed', 'DESC');
          break;
        case static::CONST_TITLE:
          $query->sort('title', 'ASC');
          break;
        case static::CONST_TITLEREV:
          $query->sort('title', 'DESC');
          break;
        case static::CONST_RANDOM:
          // this tag is implemented by our own method in our .module file
          $query->addTag('random');
          break;
        default:
          // equates to CONST_UPDATED
          $query->sort('changed', 'ASC');
          break;
      }

      $nids = $query->execute();
    }
    else {
      // using custom nodes so make sure there aren't more nodes than specified.
      $nids = [];
      foreach ($this->configuration['customNodes'] as $customNode) {
        $nids[] = $customNode['target_id'];
      }
      if (count($nids) > $count) {
        $nids = \array_slice($nids, 0, $count);
      }
    }

    if ($nids !== NULL && !empty($nids)) {
      $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $rawNodes = Node::loadMultiple($nids);
      foreach ($rawNodes as $rawNode) {
        $hasTranslation = $rawNode->hasTranslation($lang_code);
        if ($hasTranslation === TRUE) {
          $rawNode = $rawNode->getTranslation($lang_code);
        }

        $data = ['title' => $rawNode->getTitle(), 'nid' => $rawNode->id()];
        if ($rawNode->bundle() === static::TYPE_API) {
          $fid = NULL;
          $data['summary'] = $rawNode->apic_summary->value;
          // if ghmarkdown is available then use that
          if ($moduleHandler->moduleExists('ghmarkdown')) {
            $string = $rawNode->apic_description->value ?? '';
            $data['description'] = $utils->truncate_string(strip_tags(\Drupal\ghmarkdown\Plugin\Filter\GHMarkdown::parse($string)), 300);
          }
          else {
            $data['description'] = $utils->truncate_string(strip_tags($rawNode->apic_description->value), 300);
          }
          $data['version'] = $rawNode->apic_version->value;
          $data['id'] = $rawNode->api_id->value;
          $data['apic_pathalias'] = $rawNode->apic_pathalias->value;
          $fid = $rawNode->apic_image->getValue();
          $imageUrl = NULL;
          if (isset($fid[0]['target_id'])) {
            $file = File::load($fid[0]['target_id']);
            if (isset($file)) {
              $imageUrl = $file->createFileUrl();
            }
          }
          elseif ($ibmApimShowPlaceholderImages === TRUE && $moduleHandler->moduleExists('apic_api')) {
            $imageUrl = Api::getPlaceholderImageURL($rawNode->getTitle());
          }
          $data['image'] = $imageUrl;
        }
        elseif ($rawNode->bundle() === static::TYPE_PRODUCT) {
          $data['summary'] = $rawNode->apic_summary->value;
          // if ghmarkdown is available then use that
          if ($moduleHandler->moduleExists('ghmarkdown')) {
            $string = $rawNode->apic_description->value ?? '';
            $data['description'] = $utils->truncate_string(strip_tags(\Drupal\ghmarkdown\Plugin\Filter\GHMarkdown::parse($string)), 300);
          }
          else {
            $data['description'] = $utils->truncate_string(strip_tags($rawNode->apic_description->value), 300);
          }
          $data['id'] = $rawNode->product_id->value;
          $data['version'] = $rawNode->apic_version->value;
          $data['apic_pathalias'] = $rawNode->apic_pathalias->value;
          $fid = $rawNode->apic_image->getValue();
          $imageUrl = NULL;
          if (isset($fid[0]['target_id'])) {
            $file = File::load($fid[0]['target_id']);
            if (isset($file)) {
              $imageUrl = $file->createFileUrl();
            }
          }
          if ($imageUrl === NULL && $ibmApimShowPlaceholderImages === TRUE && $moduleHandler->moduleExists('product')) {
            $imageUrl = Product::getPlaceholderImageURL($rawNode->getTitle());
          }
          $data['image'] = $imageUrl;
        }
        $nodes[] = $data;
      }
    }

    $build['#theme'] = 'featuredcontent_block';
    $build['#nodes'] = $nodes;
    $build['#nodeType'] = $nodeType;
    $build['#algorithm'] = strtolower($this->configuration['selectionType']);
    $build['#showPlaceholders'] = $ibmApimShowPlaceholderImages;
    $build['#showVersions'] = $ibmApimShowVersions;
    $build['#attached']['library'][] = 'masonry/masonry.layout';
    $build['#attached']['library'][] = 'featuredcontent/featuredcontent';
    $build['#cache']['contexts'] = ['session'];
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $build);
    }
    return $build;
  }

}

