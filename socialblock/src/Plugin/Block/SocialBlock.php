<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
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
  public function defaultConfiguration(): array {

    $default = [
      'numberOfTiles' => 3,
      'forumsList' => [],
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

  public function blockForm($form, FormStateInterface $form_state): array {

    // For the forums display, we need to do some processing
    // The 'tableselect' form type, needs a header element
    $header = [
      'forum' => [
        'data' => $this->t('Forum'),
        'field' => 's.forum',
        'sort' => 'asc',
      ],
      'description' => [
        'data' => $this->t('Description'),
        'field' => 's.description',
      ],
      'topics' => [
        'data' => $this->t('Number of Topics'),
        'field' => 's.topics',
      ],
      'posts' => [
        'data' => $this->t('Number of Posts'),
        'field' => 's.posts',
      ],
    ];

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('forum')) {
      // We need to get a list of the forums and forum topic ids
      // These functions live in socialblock.module in the root directory
      $this->forumVocabularies = socialblock_get_forum_vocabularies();
      $this->forumTermIds = socialblock_get_forum_term_ids();
    }

    $container = \Drupal::getContainer();

    // Build up an array of forums and topics to display in the form (needed later)
    $options = [];
    $selectedForumDefaults = [];
    if ($this->forumVocabularies !== NULL && $container !== NULL) {

      foreach ($this->forumVocabularies as $forumVocabulary) {

        $forumManager = $container->get('forum_manager');
        $forumTopics = $forumManager->getTopics($forumVocabulary->tid, $container->get('current_user'));

        $options[$forumVocabulary->tid] = [
          'forum' => $forumVocabulary->name,
          'description' => strip_tags($forumVocabulary->description__value),
          'topics' => sizeof($forumTopics['topics']),
          'posts' => -1,
        ];

        // if this forum id is in the forumsList array, it was previously enabled when this block was configured
        // so the form needs to render with it selected now.
        $selectedForumDefaults[$forumVocabulary->tid] = \in_array($forumVocabulary->tid, $this->configuration['forumsList'], FALSE);
      }
    }

    // Start laying out the form
    $form['numberOfTiles'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of tiles to display'),
      '#default_value' => $this->configuration['numberOfTiles'],
      '#required' => TRUE,
    ];

    if ($moduleHandler->moduleExists('forum')) {
      $form['forumsList'] = [
        '#type' => 'tableselect',
        '#title' => $this->t('Forums to include in display'),
        '#header' => $header,
        '#options' => $options,
        '#default_value' => $selectedForumDefaults,
        '#empty' => $this->t('No forums available'),
        '#multiple' => TRUE,
      ];
    }

    $form['twitterConfig'] = [
      '#type' => 'container',
      '#attributes' => [// 'class' => array('container-inline')
      ],
    ];

    $form['twitterConfig']['twitterSearchBy'] = [
      '#type' => 'select',
      '#title' => $this->t('Get tweets from'),
      '#options' => [
        0 => $this->t('User'),
        1 => $this->t('Search term'),
      ],
      '#default_value' => $this->configuration['twitterSearchBy'],
      '#required' => TRUE,
    ];

    $form['twitterConfig']['twitterSearchParameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter search parameter'),
      '#default_value' => $this->configuration['twitterSearchParameter'],
      '#required' => TRUE,
    ];

    $form['twitterConfig']['twitterTweetTypes'] = [
      '#type' => 'select',
      '#title' => $this->t('Types of tweets to display'),
      '#options' => [
        0 => $this->t('Tweets'),
        1 => $this->t('Tweets & replies'),
      ],
      '#default_value' => $this->configuration['twitterTweetTypes'],
      '#required' => TRUE,
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   * This function runs when the config / edit form is submitted
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['numberOfTiles'] = $form_state->getValue('numberOfTiles');
    $this->configuration['forumsList'] = $form_state->getValue('forumsList') ?? NULL;
    $this->configuration['twitterSearchBy'] = $form_state->getValue(['twitterConfig', 'twitterSearchBy']);
    $this->configuration['twitterSearchParameter'] = $form_state->getValue(['twitterConfig', 'twitterSearchParameter']);
    $this->configuration['twitterTweetTypes'] = $form_state->getValue(['twitterConfig', 'twitterTweetTypes']);
    $uuid = $this->getConfiguration()['uuid'];
    $configInstances = \Drupal::state()->get('socialblock.config');
    if ($configInstances === NULL) {
      $configInstances = [];
    }
    $configInstances[$uuid] = [
      'numberOfTiles' => $this->configuration['numberOfTiles'],
      'forumsList' => $this->configuration['forumsList'],
      'twitterSearchBy' => $this->configuration['twitterSearchBy'],
      'twitterSearchParameter' => $this->configuration['twitterSearchParameter'],
      'twitterTweetTypes' => $this->configuration['twitterTweetTypes'],
    ];
    \Drupal::state()->set('socialblock.config', $configInstances);

    // run cron to re-populate the cache
    socialblock_cron();
  }

  /**
   * {@inheritdoc}
   * This is where the "view" portion of the code lives. Here we render the social block
   */
  public function build(): array {
    $uuid = $this->getConfiguration()['uuid'];
    // check our config is saved, might not happen if the config form hasn't been saved and this block was created programmatically
    $configInstances = \Drupal::state()->get('socialblock.config');
    if ($configInstances === NULL) {
      $configInstances = [];
    }
    if (!array_key_exists($uuid, $configInstances) || $configInstances[$uuid] === NULL) {
      $configInstances[$uuid] = [
        'numberOfTiles' => $this->configuration['numberOfTiles'],
        'forumsList' => $this->configuration['forumsList'],
        'twitterSearchBy' => $this->configuration['twitterSearchBy'],
        'twitterSearchParameter' => $this->configuration['twitterSearchParameter'],
        'twitterTweetTypes' => $this->configuration['twitterTweetTypes'],
      ];
      \Drupal::state()->set('socialblock.config', $configInstances);
    }

    $posts = [];
    $tweets = NULL;
    $container = \Drupal::getContainer();
    if ($container !== NULL) {
      // First sort out tweets
      $dataInstances = \Drupal::state()->get('socialblock.data');
      if ($dataInstances !== NULL) {
        $tweets = $dataInstances[$uuid];
      }

      if (\is_array($tweets)) {
        foreach ($tweets as $tweet) {
          $tweet->tweet = TRUE;
          $posts[] = $tweet;
        }
      }

      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('forum')) {
        // Next up - forums
        $enabledForums = $this->configuration['forumsList'];

        if ($enabledForums !== NULL && sizeof($enabledForums) !== 0) {
          $forumManager = $container->get('forum_manager');
          foreach ($enabledForums as $enabledForum) {

            $forumTopics = $forumManager->getTopics($enabledForum, $container->get('current_user'));
            $topics = $forumTopics['topics'];

            foreach ($topics as $id => $forumTopic) {
              $forumTopic->tweet = FALSE;
              $forumTopic->id = $id;
              $posts[] = $forumTopic;
            }
          }
        }
      }
    }

    $blockPosts = [];
    if ($posts !== NULL && !empty($posts)) {
      // Sort array by time of forum topic or tweet and then chop of the top X
      // where X is the number of tiles to be displayed in this block
      uasort($posts, 'socialblock_sort_by_created');
      $posts = \array_slice($posts, 0, $this->configuration['numberOfTiles']);

      foreach ($posts as $post) {

        if ($post->tweet) {

          if (isset($post->retweeted_status)) {
            $post = $post->retweeted_status;
          }
          $tweetId = $post->id_str;
          $profileImg = $post->user->profile_image_url_https;
          $name = $post->user->name;
          $handle = $post->user->screen_name;
          $timestamp = date_diff(date_create('@' . strtotime($post->created_at)), date_create('@' . time()));
          $rawContent = $post->text;
          $hashtags = $post->entities->hashtags;
          $userMentions = $post->entities->user_mentions;

          $hashtagsReplace = [];
          foreach ($hashtags as $hashtag) {
            $hashtagReplace = mb_substr($rawContent, $hashtag->indices[0], $hashtag->indices[1] - $hashtag->indices[0]);
            $hashtagsReplace[] = $hashtagReplace;
          }

          $userMentionsReplace = [];
          foreach ($userMentions as $userMention) {
            $userMentionReplace = mb_substr($rawContent, $userMention->indices[0], $userMention->indices[1] - $userMention->indices[0]);
            $userMentionsReplace[] = $userMentionReplace;
          }

          foreach ($hashtagsReplace as $hashtagReplace) {
            $rawContent = str_replace($hashtagReplace, '<a href="https://twitter.com/hashtag/' . ltrim($hashtagReplace, '#') . '" target="_blank" rel="noopener" title="' . $hashtagReplace . '" class="hashtag">' . $hashtagReplace . '</a>', $rawContent);
          }

          foreach ($userMentionsReplace as $userMentionReplace) {
            $rawContent = str_replace($userMentionReplace, '<a href="https://twitter.com/' . ltrim($userMentionReplace, '@') . '" target="_blank" rel="noopener" title="' . $userMentionReplace . '" class="user_mention">' . $userMentionReplace . '</a>', $rawContent);
          }

          $media = $post->entities->media ?? [];
          $extendedMedia = $post->extended_entities->media ?? [];
          $photos = [];
          $gifs = [];

          $urls = $post->entities->urls ?? [];

          $videoIds = [];
          foreach ($media as $medium) {
            if ($medium->type === 'photo') {
              $photos[] = $medium;
              // remove photo urls from the content if we're displaying the image
              if (strpos($rawContent, $medium->url) !== FALSE) {
                $rawContent = str_replace($medium->url, '', $rawContent);
              }
            }
          }
          foreach ($extendedMedia as $extMedium) {
            if ($extMedium->type === 'animated_gif') {
              $gifs[] = $extMedium;
              // remove gif urls from the content if we're displaying the image
              if (strpos($rawContent, $extMedium->url) !== FALSE) {
                $rawContent = str_replace($extMedium->url, '', $rawContent);
              }
            }
          }

          foreach ($urls as $url) {
            $shortUrl = $url->url;
            $expandedUrl = $url->expanded_url;
            $trueUrl = socialblock_expand_url($expandedUrl);
            if ($trueUrl !== NULL) {
              if (preg_match('/^(https?:\/\/)?(www\.)?youtube\.com\//i', $trueUrl)) {
                $videoIds[] = preg_replace('/^(https?:\/\/)?(www\.)?youtube\.com\/watch\?v=/i', '', $trueUrl);
              }
              elseif (preg_match('/^(https?:\/\/)?(youtu\.be)\//i', $trueUrl)) {
                $videoIds[] = preg_replace('/^(https?:\/\/)?(youtu\.be)\//i', '', $trueUrl);
              }
            }


            if ($shortUrl !== NULL) {
              if (strpos($rawContent, $shortUrl) !== FALSE) {
                $rawContent = str_replace($shortUrl, '<a href="' . $expandedUrl . '" target="_blank" rel="noopener" class="url">' . $shortUrl . '</a>', $rawContent);
              }
              else {
                $rawContent = str_replace($shortUrl, '', $rawContent);
              }
            }

          }
          $blockPost = [
            'type' => 'tweet',
            'profile_img' => $profileImg,
            'handle' => $handle,
            'tweet_id' => $tweetId,
            'timestamp' => socialblock_get_tweet_timediff($timestamp),
            'name' => $name,
          ];
          // sanity check existing content
          $rawContent = Xss::filter($rawContent);

          // extended media, embedded gifs etc
          if (!empty($gifs)) {
            foreach ($gifs as $gif) {
              if (isset($gif->video_info->variants[0]->url)) {
                $rawContent = '<div class="centerContainer"><video class="tweet_video" autoplay="true" loop="true" preload="none"><source src="' . $gif->video_info->variants[0]->url . '" type="video/mp4"/></video></div>' . $rawContent;
              }
            }
          }
          // only display photos that aren't also in the extended_media listing
          if (!empty($photos)) {
            $photo_out = [];
            foreach ($photos as $photo) {
              $found = FALSE;
              if (!empty($gifs)) {
                foreach ($gifs as $gif) {
                  if ($photo->id === $gif->id) {
                    $found = TRUE;
                  }
                }
              }
              if ($found !== TRUE) {
                $photo_out[] = ['url' => $photo->media_url_https];
              }
            }
            $blockPost['photos'] = $photo_out;
          }

          if (!empty($videoIds)) {
            $rawContent = $rawContent . '<iframe class="yt_embed" title="' . t('Watch on YouTube') . '" src="https://www.youtube.com/embed/' . $videoIds[0] . '?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>';
          }
          $blockPost['content'] = $rawContent;
          $blockPosts[] = $blockPost;
        }
        else {
          $moduleHandler = \Drupal::service('module_handler');
          if ($moduleHandler->moduleExists('forum')) {
            $topicNode = Node::load($post->id);
            $forumTid = $post->forum_tid;
            $termData = \Drupal\taxonomy\Entity\Term::load($forumTid);
            $timestamp = '';

            if ($topicNode !== NULL) {
              $topicCreatedTime = DateTimePlus::createFromTimestamp($topicNode->created->value);
              $now = DateTimePlus::createFromTimestamp(time());
              $timestamp = $now->diff($topicCreatedTime);
              $timestamp = socialblock_get_tweet_timediff($timestamp);
            }

            $op = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $post->name]);
            $user = array_shift($op);
            $forumName = '';
            if ($termData !== NULL) {
              $forumName = $termData->name->value;
            }

            $blockPost = [
              'type' => 'forum_topic',
              'url' => Url::fromUserInput('/node/' . $post->id)->toString(),
              'handle' => $user->getDisplayName(),
              'title' => $post->title->value,
              'forum' => $forumName,
              'forum_url' => Url::fromUserInput('/forum/' . $forumTid)->toString(),
              'timestamp' => $timestamp,
            ];
            $blockPosts[] = $blockPost;
          }
        }
      }
    }
    return [
      '#theme' => 'socialblock_block',
      '#posts' => $blockPosts,
      '#allowed_tags' => ['h3', 'div', 'img', 'span', 'i', 'ul', 'li', 'a', 'iframe', 'video'],
      '#attached' => [
        'library' => 'socialblock/socialblock',
      ],
    ];
  }

}
