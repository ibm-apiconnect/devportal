<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * SocialBlock bean plugin.
 */

/**
 * Class SocialBlockBean
 */
class SocialBlockBean extends BeanPlugin {
  private $_bean;
  private $forum_vocabs;
  private $forum_tids;

  /**
   * Declares default block settings.
   * @return array
   */
  public function values() {
    return array(
      'tiles' => 8,
      'forums' => array(),
      'display_all_forums' => 1,
      'twitter_api' => 0,
      'twitter_param' => 'ibmapiconnect',
      'tweet_filter' => 0
    );
  }

  /**
   * Builds extra settings for the block edit form.
   * @param $bean
   * @param $form
   * @param $form_state
   * @return array
   */
  public function form($bean, $form, &$form_state) {
    $header = array(
      'forum' => array(
        'data' => t('Forum'),
        'field' => 's.forum',
        'sort' => 'asc'
      ),
      'description' => array(
        'data' => t('Description'),
        'field' => 's.description'
      ),
      'topics' => array(
        'data' => t('Number of Topics'),
        'field' => 's.topics'
      ),
      'posts' => array(
        'data' => t('Number of Posts'),
        'field' => 's.posts'
      )
    );
    $this->forum_vocabs = socialblock_get_forum_vocabs();
    $this->forum_tids = socialblock_get_forum_tids();
    $options = array();
    if (isset($this->forum_vocabs)) {
      foreach ($this->forum_vocabs as $forum_vocab) {
        $options[$forum_vocab->tid] = array(
          'forum' => $forum_vocab->name,
          'description' => $forum_vocab->description,
          'topics' => forum_forum_load()->forums[$forum_vocab->tid]->num_topics,
          'posts' => forum_forum_load()->forums[$forum_vocab->tid]->num_posts
        );
      }
    }
    $form['tiles'] = array(
      '#title' => t('Number of tiles to display'),
      '#type' => 'textfield',
      '#default_value' => isset($bean->tiles) ? $bean->tiles : 8,
      '#required' => TRUE
    );
    $form['display_all_forums'] = array(
      '#title' => t('Display topics from all forums'),
      '#type' => 'checkbox',
      '#default_value' => isset($bean->display_all_forums) ? $bean->display_all_forums : 1
    );
    $bean->forums = $bean->display_all_forums == 1 ? $this->forum_tids : (isset($bean->forums) ? $bean->forums : array());
    $form['cron_info'] = array(
      '#markup' => '<p>' . t('NOTE: These settings will take effect when cron next runs.') . '&nbsp;' . t('Navigate to <a href="@url">Administration >> Reports >> Status report</a> to run cron manually.', array('@url' => url('admin/reports/status'))) . '</p>'
    );
    $form['forums'] = array(
      '#title' => t('Forums'),
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $bean->forums,
      '#empty' => t('No forums available.'),
      '#multiple' => TRUE
    );
    $form['twitter_api_config'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline')
      )
    );
    $form['twitter_api_config']['twitter_api'] = array(
      '#title' => t('Get tweets from'),
      '#type' => 'select',
      '#options' => array(
        0 => t('User'),
        1 => t('Search term')
      ),
      '#default_value' => isset($bean->twitter_api) ? $bean->twitter_api : 0,
      '#required' => TRUE
    );
    $form['twitter_api_config']['twitter_param'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($bean->twitter_param) ? $bean->twitter_param : '',
      '#required' => TRUE
    );
    $form['tweet_filter'] = array(
      '#title' => 'Type of tweets to display',
      '#type' => 'select',
      '#options' => array(
        0 => t('Tweets'),
        1 => t('Tweets & replies')
      ),
      '#default_value' => isset($bean->tweet_filter) ? $bean->tweet_filter : 0,
      '#required' => TRUE
    );
    $form['#attached']['js'] = array(
      drupal_get_path('module', 'socialblock') . '/js/socialblock_edit.js'
    );
    return $form;
  }

  /**
   * Displays the bean.
   * @param $bean
   * @param $content
   * @param string $view_mode
   * @param null $langcode
   * @return mixed
   */
  public function view($bean, $content, $view_mode = 'default', $langcode = 'en') {
    if (!isset($langcode) || empty($langcode)) {
      global $language;
      $langcode = $language->language;
    }
    $this->_bean = $bean;
    $markup = '';
    if (isset($bean->socialblocktitle[$langcode][0]['value'])) {
      $markup .= '<h2 class="pane-title block-title">' . $bean->socialblocktitle[$langcode][0]['value'] . '</h2>';
    }
    else if (isset($bean->socialblocktitle['und'][0]['value'])) {
      $markup .= '<h2 class="pane-title block-title">' . $bean->socialblocktitle['und'][0]['value'] . '</h2>';
    }
    $markup .= '<div class="socialblock container">';

    $cronoutput = unserialize(variable_get('socialblock_tweets_' . $bean->bid));
    if (isset($cronoutput) && !empty($cronoutput) && !is_array($cronoutput)) {
      $markup .= $cronoutput;
    }
    else {
      $markup .= '<div class="socialblock no_topics">' . t('There are no posts to display.') . '</div>';
    }

    $markup .= '</div>';
    // Hide the raw field since we're handling the rendering
    $id = $bean->delta;
    unset($content['bean'][$id]['tiles']);
    unset($content['bean'][$id]['forums']);
    unset($content['bean'][$id]['display_all_forums']);
    unset($content['bean'][$id]['handle']);
    // Add the markup to the render array along with our CSS file
    $content[] = array(
      '#markup' => $markup,
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'socialblock') . '/css/socialblock.css',
        ),
        'js' => array(
          libraries_get_path('masonry') . '/masonry.pkgd.min.js',
          libraries_get_path('eq.js') . '/eq.min.js',
          drupal_get_path('module', 'socialblock') . '/js/socialblock.js'
        )
      )
    );
    return $content;
  }
}

