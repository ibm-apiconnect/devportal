<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\case_study\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Provides a 'Case studies' Block.
 */
#[Block(
  id: "casestudy_block",
  admin_label: new TranslatableMarkup("Case studies"),
  category: new TranslatableMarkup("IBM API Developer Portal (Case study)")
)]

class CaseStudyBlock extends BlockBase {
  /**
   * Class constant for case study node type.
   */
  protected const TYPE_CASE_STUDY = 'case_study';

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
      'numberOfTiles' => 4,
      'selectionType' => static::CONST_CREATED,
      'customNodes' => [],
      'label_display' => FALSE,
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $form = parent::blockForm($form, $form_state);

    // Start laying out the form
    $form['intro'] = [
      '#markup' => $this->t('<p>Configure the node selection settings for this Case study block. For each node the block will display its image, title and summary. If no summary is present then a truncated form of the description field will be used instead.</p>
<p>To modify the content shown for a tile edit that Case study in the portal.</p>'),
      '#weight' => -10,
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

    $nids = [];
    foreach ($this->configuration['customNodes'] as $customNode) {
      $nids[] = $customNode['target_id'];
    }

    $form['customNodes'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Custom nodes'),
      '#description' => $this->t('Manually specify the nodes to feature. \',\' separated. (This field is only used if using \'Custom\' node selection.)'),
      '#default_value' => Node::loadMultiple($nids),
      '#tags' => TRUE,
      '#selection_settings' => [
        'target_bundles' => ['case_study'],
      ],
      '#weight' => 60,
    ];

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state): void {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    parent::blockValidate($form, $form_state);

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

  public function blockSubmit($form, FormStateInterface $form_state): void {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    parent::blockSubmit($form, $form_state);

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
  public function build() {
    $caseStudies = [];

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
      $count = 4;
    }

    if (strtoupper($this->configuration['selectionType']) !== static::CONST_CUSTOM) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'case_study');

      switch (strtoupper($this->configuration['selectionType'])) {
        case static::CONST_UPDATED:
          $query->sort('changed', 'DESC');
          break;
        case static::CONST_CREATED:
          $query->sort('created', 'DESC');
          break;
        case static::CONST_OLDEST:
          $query->sort('created', 'ASC');
          break;
        case static::CONST_STALEST:
          $query->sort('changed', 'ASC');
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
          $query->sort('changed', 'DESC');
          break;
      }

      $nids = $query->accessCheck()->execute();
    } else {
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

        $title = isset($rawNode->title) ? $rawNode->getTitle() : '';
        $summary = isset($rawNode->body) && isset($rawNode->body->summary) ? $rawNode->body->summary : '';

        if ($summary === '') {
          $rawBody = isset($rawNode->body) && isset($rawNode->body->value) ? strip_tags($rawNode->body->value) : '';
          if ($rawBody !== '') {
            $ellipses = strlen($rawBody) > 100 ? '...' : '';
            $summary = substr($rawBody, 0, 100) . $ellipses;
          }
        } else {
          $ellipses = strlen($summary) > 100 ? '...' : '';
          $summary = isset($rawNode->body) && isset($rawNode->body->summary) ? substr(strip_tags($rawNode->body->summary), 0, 100) . $ellipses : '';
        }

        $data = [
          'title' => $title,
          'nid' => $rawNode->id(),
          'summary' => $summary
        ];

        if (isset($rawNode->field_case_image)) {
          $fid = $rawNode->field_case_image->getValue();
          if (isset($fid[0]['target_id'])) {
            $file = File::load($fid[0]['target_id']);
            if (isset($file)) {
              $data['image'] = $file->createFileUrl();
            }
          }
        }
        $caseStudies[] = $data;
      }

    }

    return [
      '#theme' => 'casestudy_block',
      '#caseStudies' => $caseStudies,
      '#attached' => [
        'library' => [
            'masonry/masonry.layout',
            'case_study/basic'
        ]
      ],
      '#cache' => [
        'max-age' => 0
      ],
    ];
  }

}