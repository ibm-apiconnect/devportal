<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\socialblock\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides the IBM Social Block
 * @Block(
 *   id = "social_block",
 *   admin_label = @Translation("IBM Social Block"),
 * )
 */
class SocialBlock extends BlockBase {

  private $forumVocabularies;
  private $forumTermIds;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $default = [
      'numberOfTiles' => 3,
      'forumsList' => array(),
      'twitterSearchBy' => 0,
      'twitterSearchParameter' => 'IBMintegration',
      'twitterTweetTypes' => 0,
    ];

    return $default;

  }

  /**
   * {@inheritdoc}
   * This is the form that is displayed when placing or editting a social block
   */

  public function blockForm($form, FormStateInterface $form_state) {

    // For the forums display, we need to do some processing
    // The 'tableselect' form type, needs a header element
    $header = array(
      'forum' => array(
        'data' => $this->t('Forum'),
        'field' => 's.forum',
        'sort' => 'asc'
      ),
      'description' => array(
        'data' => $this->t('Description'),
        'field' => 's.description'
      ),
      'topics' => array(
        'data' => $this->t('Number of Topics'),
        'field' => 's.topics'
      ),
      'posts' => array(
        'data' => $this->t('Number of Posts'),
        'field' => 's.posts'
      )
    );

    // We need to get a list of the forums and forum topic ids
    // These functions live in socialblock.module in the root directory
    $this->forumVocabularies = socialblock_get_forum_vocabularies();
    $this->forumTermIds = socialblock_get_forum_term_ids();

    $container = \Drupal::getContainer();

    // Build up an array of forums and topics to display in the form (needed later)
    $options = array();
    $selectedForumDefaults = array();
    if (isset($this->forumVocabularies)) {

      foreach ($this->forumVocabularies as $forum_vocabulary) {

        $forumManager = $container->get('forum_manager');
        $forumTopics = $forumManager->getTopics($forum_vocabulary->tid, $container->get('current_user'));

        $options[$forum_vocabulary->tid] = array(
          'forum' => $forum_vocabulary->name,
          'description' => strip_tags($forum_vocabulary->description__value),
          'topics' => sizeof($forumTopics['topics']),
          'posts' => -1
        );

        // if this forum id is in the forumsList array, it was previously enabled when this block was configured
        // so the form needs to render with it selected now.
        $selectedForumDefaults[$forum_vocabulary->tid] = in_array($forum_vocabulary->tid, $this->configuration['forumsList']);
      }
    }

    // Start laying out the form
    $form['numberOfTiles'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of tiles to display'),
      '#default_value' => $this->configuration['numberOfTiles'],
      '#required' => TRUE,
    );

    $form['forumsList'] = array(
      '#type' => 'tableselect',
      '#title' => $this->t('Forums to include in display'),
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $selectedForumDefaults,
      '#empty' => $this->t('No forums available'),
      '#multiple' => TRUE,
    );

    $form['twitterConfig'] = array(
      '#type' => 'container',
      '#attributes' => array(//          'class' => array('container-inline')
      ),
    );

    $form['twitterConfig']['twitterSearchBy'] = array(
      '#type' => 'select',
      '#title' => $this->t('Get tweets from'),
      '#options' => array(
        0 => $this->t('User'),
        1 => $this->t('Search term')
      ),
      '#default_value' => $this->configuration['twitterSearchBy'],
      '#required' => TRUE,
    );

    $form['twitterConfig']['twitterSearchParameter'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter search parameter'),
      '#default_value' => $this->configuration['twitterSearchParameter'],
      '#required' => TRUE,
    );

    $form['twitterConfig']['twitterTweetTypes'] = array(
      '#type' => 'select',
      '#title' => $this->t('Types of tweets to display'),
      '#options' => array(
        0 => $this->t('Tweets'),
        1 => $this->t('Tweets & replies'),
      ),
      '#default_value' => $this->configuration['twitterTweetTypes'],
      '#required' => TRUE,
    );

    return $form;

  }

  /**
   * {@inheritdoc}
   * This function runs when the config / edit form is submitted
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['numberOfTiles'] = $form_state->getValue('numberOfTiles');
    $this->configuration['forumsList'] = $form_state->getValue('forumsList');
    $this->configuration['twitterSearchBy'] = $form_state->getValue(['twitterConfig', 'twitterSearchBy']);
    $this->configuration['twitterSearchParameter'] = $form_state->getValue(['twitterConfig', 'twitterSearchParameter']);
    $this->configuration['twitterTweetTypes'] = $form_state->getValue(['twitterConfig', 'twitterTweetTypes']);
    $uuid = $this->getConfiguration()['uuid'];
    $config_instances = \Drupal::state()->get('socialblock.config');
    if (!isset($config_instances)) {
      $config_instances = array();
    }
    $config_instances[$uuid] = array(
      'numberOfTiles' => $this->configuration['numberOfTiles'],
      'forumsList' => $this->configuration['forumsList'],
      'twitterSearchBy' => $this->configuration['twitterSearchBy'],
      'twitterSearchParameter' => $this->configuration['twitterSearchParameter'],
      'twitterTweetTypes' => $this->configuration['twitterTweetTypes']
    );
    \Drupal::state()->set('socialblock.config', $config_instances);
  }

  /**
   * {@inheritdoc}
   * This is where the "view" portion of the code lives. Here we render the social block
   */
  public function build() {
    $uuid = $this->getConfiguration()['uuid'];
    // check our config is saved, might not happen if the config form hasn't been saved and this block was created programmatically
    $config_instances = \Drupal::state()->get('socialblock.config');
    if (!isset($config_instances) && !isset($config_instances[$uuid])) {
      $config_instances[$uuid] = array(
        'numberOfTiles' => $this->configuration['numberOfTiles'],
        'forumsList' => $this->configuration['forumsList'],
        'twitterSearchBy' => $this->configuration['twitterSearchBy'],
        'twitterSearchParameter' => $this->configuration['twitterSearchParameter'],
        'twitterTweetTypes' => $this->configuration['twitterTweetTypes']
      );
      \Drupal::state()->set('socialblock.config', $config_instances);
    }

    $posts = array();
    $tweets = NULL;
    $container = \Drupal::getContainer();
    $forumManager = $container->get('forum_manager');

    // First sort out tweets
    $data_instances = \Drupal::state()->get('socialblock.data');
    if (isset($data_instances)) {
      $tweets = $data_instances[$uuid];
    }

    if (is_array($tweets)) {
      foreach ($tweets as $tweet) {
        $tweet->tweet = TRUE;
        array_push($posts, $tweet);
      }
    }

    // Next up - forums
    $enabledForums = $this->configuration['forumsList'];

    if (sizeof($enabledForums) != 0) {
      foreach ($enabledForums as $enabledForum) {

        $forumTopics = $forumManager->getTopics($enabledForum, $container->get('current_user'));
        $topics = $forumTopics['topics'];

        foreach ($topics as $id => $forumTopic) {
          $forumTopic->tweet = FALSE;
          $forumTopic->id = $id;
          array_push($posts, $forumTopic);
        }
      }
    }

    $block_posts = array();
    if (isset($posts) && !empty($posts)) {
      // Sort array by time of forum topic or tweet and then chop of the top X
      // where X is the number of tiles to be displayed in this block
      uasort($posts, 'socialblock_sort_by_created');
      $posts = array_slice($posts, 0, $this->configuration['numberOfTiles']);

      foreach ($posts as $post) {

        if ($post->tweet) {

          if (isset($post->retweeted_status)) {
            $post = $post->retweeted_status;
          }
          $tweet_id = $post->id_str;
          $profile_img = $post->user->profile_image_url_https;
          $name = $post->user->name;
          $handle = $post->user->screen_name;
          $timestamp = date_diff(date_create('@' . strtotime($post->created_at)), date_create('@' . time()));
          $raw_content = $post->text;
          $hashtags = $post->entities->hashtags;
          $user_mentions = $post->entities->user_mentions;

          $hashtags_replace = array();
          foreach ($hashtags as $hashtag) {
            $hashtag_replace = mb_substr($raw_content, $hashtag->indices[0], $hashtag->indices[1] - $hashtag->indices[0]);
            array_push($hashtags_replace, $hashtag_replace);
          }

          $user_mentions_replace = array();
          foreach ($user_mentions as $user_mention) {
            $user_mention_replace = mb_substr($raw_content, $user_mention->indices[0], $user_mention->indices[1] - $user_mention->indices[0]);
            array_push($user_mentions_replace, $user_mention_replace);
          }

          foreach ($hashtags_replace as $hashtag_replace) {
            $raw_content = str_replace($hashtag_replace, '<a href="https://twitter.com/hashtag/' . ltrim($hashtag_replace, '#') . '" target="_blank" rel="noopener" title="' . $hashtag_replace . '" class="hashtag">' . $hashtag_replace . '</a>', $raw_content);
          }

          foreach ($user_mentions_replace as $user_mention_replace) {
            $raw_content = str_replace($user_mention_replace, '<a href="https://twitter.com/' . ltrim($user_mention_replace, '@') . '" target="_blank" rel="noopener" title="' . $user_mention_replace . '" class="user_mention">' . $user_mention_replace . '</a>', $raw_content);
          }

          $media = isset($post->entities->media) ? $post->entities->media : array();
          $extended_media = isset($post->extended_entities->media) ? $post->extended_entities->media : array();
          $photos = array();
          $gifs = array();

          $urls = isset($post->entities->urls) ? $post->entities->urls : array();

          $video_ids = array();
          foreach ($media as $medium) {
            if ($medium->type == 'photo') {
              array_push($photos, $medium);
              // remove photo urls from the content if we're displaying the image
              if (strpos($raw_content, $medium->url) != FALSE) {
                $raw_content = str_replace($medium->url, '', $raw_content);
              }
            }
          }
          foreach ($extended_media as $ext_medium) {
            if ($ext_medium->type == 'animated_gif') {
              array_push($gifs, $ext_medium);
              // remove gif urls from the content if we're displaying the image
              if (strpos($raw_content, $ext_medium->url) != FALSE) {
                $raw_content = str_replace($ext_medium->url, '', $raw_content);
              }
            }
          }

          foreach ($urls as $url) {
            $short_url = $url->url;
            $expanded_url = $url->expanded_url;
            $true_url = socialblock_expand_url($expanded_url);
            if (isset($true_url)) {
              if (preg_match('/^(https?:\/\/)?(www\.)?youtube\.com\/.*/i', $true_url)) {
                $video_ids[] = preg_replace('/^(https?:\/\/)?(www\.)?youtube\.com\/watch\?v=/i', '', $true_url);
              }
              else {
                if (preg_match('/^(https?:\/\/)?(youtu\.be)\//i', $true_url)) {
                  $video_ids[] = preg_replace('/^(https?:\/\/)?(youtu\.be)\//i', '', $true_url);
                }
              }
            }


            if (isset($short_url)) {
              if (strpos($raw_content, $short_url) != FALSE) {
                $raw_content = str_replace($short_url, '<a href="' . $expanded_url . '" target="_blank" rel="noopener" class="url">' . $short_url . '</a>', $raw_content);
              }
              else {
                $raw_content = str_replace($short_url, '', $raw_content);
              }
            }

          }
          $block_post = array(
            'type' => 'tweet',
            'profile_img' => $profile_img,
            'handle' => $handle,
            'tweet_id' => $tweet_id,
            'timestamp' => socialblock_get_tweet_timediff($timestamp),
            'name' => $name
          );
          // sanity check existing content
          $raw_content = Xss::filter($raw_content);

          // extended media, embedded gifs etc
          if (!empty($gifs)) {
            foreach ($gifs as $gif) {
              if (isset($gif->video_info->variants[0]->url)) {
                $raw_content = '<div class="centerContainer"><video class="tweet_video" autoplay="true" loop="true" preload="none"><source src="' . $gif->video_info->variants[0]->url . '" type="video/mp4"/></video></div>' . $raw_content;
              }
            }
          }
          // only display photos that aren't also in the extended_media listing
          if (!empty($photos)) {
            $photo_out = array();
            foreach ($photos as $photo) {
              $found = FALSE;
              if (!empty($gifs)) {
                foreach ($gifs as $gif) {
                  if ($photo->id == $gif->id) {
                    $found = TRUE;
                  }
                }
              }
              if ($found != TRUE) {
                $photo_out[] = array('url' => $photo->media_url_https);
              }
            }
            $block_post['photos'] = $photo_out;
          }

          if (!empty($video_ids)) {
            $raw_content = $raw_content . '<iframe class="yt_embed" title="' . t('Watch on YouTube') . '" src="https://www.youtube.com/embed/' . $video_ids[0] . '?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>';
          }
          $block_post['content'] = $raw_content;
          $block_posts[] = $block_post;
        }
        else {

          $topic_node = Node::load($post->id);
          $forum_tid = $post->forum_tid;
          $term_data = \Drupal\taxonomy\Entity\Term::load($forum_tid);

          $topicCreatedTime = DateTimePlus::createFromTimestamp($topic_node->created->value);
          $now = DateTimePlus::createFromTimestamp(time());
          $timestamp = $now->diff($topicCreatedTime);

          $op = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $post->name]);
          $user = array_shift($op);

          $block_post = array(
            'type' => 'forum_topic',
            'url' => Url::fromUserInput('/node/' . $post->id)->toString(),
            'handle' => $user->getDisplayName(),
            'title' => $post->title->value,
            'forum' => $term_data->name->value,
            'forum_url' => Url::fromUserInput('/forum/' . $forum_tid)->toString(),
            'timestamp' => socialblock_get_tweet_timediff($timestamp)
          );
          $block_posts[] = $block_post;
        }
      }
    }
    return array(
      '#theme' => 'socialblock_block',
      '#posts' => $block_posts,
      '#allowed_tags' => ['h3', 'div', 'img', 'span', 'i', 'ul', 'li', 'a', 'iframe', 'video'],
      '#attached' => array(
        'library' => 'socialblock/socialblock',
      ),
    );
  }

}

?>
