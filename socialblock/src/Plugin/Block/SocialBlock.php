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

namespace Drupal\socialblock\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\encrypt\Entity\EncryptionProfile;


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

    return [
      'numberOfTiles' => 9,
      'forumsList' => [],
      'twitterSearchBy' => 0,
      'twitterSearchParameter' => 'ibmcloud',
      'blueskySearchParameter' => '',
      'twitterTweetTypes' => 0,
    ];

  }

  /**
   * {@inheritdoc}
   * This is the form that is displayed when placing or editting a social block
   */

  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
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

    // get forum post counts
    $options = ['target' => 'default'];
    $connection = Database::getConnection($options['target']);

    $query = $connection->select('node_field_data', 'n');
    $query->join('comment_entity_statistics', 'ces', "n.nid = ces.entity_id AND ces.field_name = 'comment_forum' AND ces.entity_type = 'node'");
    $query->join('forum', 'f', 'n.vid = f.vid');
    $query->addExpression('COUNT(n.nid)', 'topic_count');
    $query->addExpression('SUM(ces.comment_count)', 'comment_count');
    $forumStatistics = $query
      ->fields('f', ['tid'])
      ->condition('n.status', 1)
      ->condition('n.default_langcode', 1)
      ->groupBy('tid')
      ->addTag('node_access')
      ->execute()
      ->fetchAllAssoc('tid');

    // Build up an array of forums and topics to display in the form (needed later)
    $options = [];
    $selectedForumDefaults = [];
    if ($this->forumVocabularies !== NULL && $container !== NULL) {

      foreach ($this->forumVocabularies as $forumVocabulary) {
        $forumTopics = [];
        $forumManager = $container->get('forum_manager');
        if ($forumManager !== NULL) {
          $forumTopics = $forumManager->getTopics($forumVocabulary->tid, $container->get('current_user'));
        }

        $numPosts = 0;
        if (isset($forumStatistics[$forumVocabulary->tid])) {
          // add number of topics to number of comments to get number of posts
          $numPosts = (int) $forumStatistics[$forumVocabulary->tid]->topic_count + (int) $forumStatistics[$forumVocabulary->tid]->comment_count;
        }
        $options[$forumVocabulary->tid] = [
          'forum' => $forumVocabulary->name,
          'description' => strip_tags($forumVocabulary->description__value ?? ""),
          'topics' => sizeof($forumTopics['topics']),
          'posts' => $numPosts,
        ];

        // if this forum id is in the forumsList array, it was previously enabled when this block was configured
        // so the form needs to render with it selected now.
        $selectedForumDefaults[$forumVocabulary->tid] = \in_array($forumVocabulary->tid, $this->configuration['forumsList'], FALSE);
      }
    }

    // Start laying out the form
    $form['numberOfTiles'] = [
      '#type' => 'number',
      '#min' => 5,
      '#max' => 100,
      '#title' => $this->t('Number of tiles to display'),
      '#default_value' => $this->configuration['numberOfTiles'],
      '#required' => TRUE,
      '#attributes' => [
        'style' => 'width: 200px;',
      ],
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
      '#required' => $this->isTwitterDefined(),
    ];

    $form['twitterConfig']['blueskySearchParameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bluesky search parameter'),
      '#default_value' => $this->configuration['blueskySearchParameter'],
      '#required' => $this->isBlueSkyDefined(),
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
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $uuid_service = new PhpUuid();
    parent::blockSubmit($form, $form_state);
    $this->configuration['numberOfTiles'] = $form_state->getValue('numberOfTiles');
    $this->configuration['forumsList'] = $form_state->getValue('forumsList');
    $this->configuration['twitterSearchBy'] = $form_state->getValue(['twitterConfig', 'twitterSearchBy']);
    $this->configuration['twitterSearchParameter'] = $form_state->getValue(['twitterConfig', 'twitterSearchParameter']);
    $this->configuration['blueskySearchParameter'] = $form_state->getValue(['twitterConfig', 'blueskySearchParameter']);
    $this->configuration['twitterTweetTypes'] = $form_state->getValue(['twitterConfig', 'twitterTweetTypes']);
    $uuid = $uuid_service->generate();
    $this->configuration['uuid'] = $uuid;
    $configInstances = \Drupal::state()->get('socialblock.config');
    if ($configInstances === NULL) {
      $configInstances = [];
    }
    $configInstances[$uuid] = [
      'numberOfTiles' => $this->configuration['numberOfTiles'],
      'forumsList' => $this->configuration['forumsList'],
      'twitterSearchBy' => $this->configuration['twitterSearchBy'],
      'twitterSearchParameter' => $this->configuration['twitterSearchParameter'],
      'blueskySearchParameter' => $this->configuration['blueskySearchParameter'],
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
        'blueskySearchParameter'=> $this->configuration['blueskySearchParameter'],
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
          if ($forumManager !== NULL) {
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
    }

    $blockPosts = [];
    if ($posts !== NULL && !empty($posts)) {
      // Sort array by time of forum topic or tweet and then chop of the top X
      // where X is the number of tiles to be displayed in this block
      foreach ($posts as $post) {
        if ($post->tweet) {
          if (isset($post->retweeted_status)) {
            $post = $post->retweeted_status;
          }
          $tweetId = $post->id_str ?? '';
          if (isset($post?->post?->uri)) {
            $parts = explode('/', $post->post->uri);
            $tweetId = end($parts);
          }
          $profileImg = $post->user->profile_image_url_https ?? $post?->post->author->avatar ?? '';
          $name = $post->user->name ?? $post?->post?->author?->displayName ?? '';
          $handle = $post->user->screen_name ?? $post?->post?->author?->handle ?? '';
          $timestamp = date_diff(date_create('@' . strtotime($post->created_at ?? $post?->post?->record?->createdAt ?? time())), date_create('@' . time()));
          $rawContent = $post->text ?? mb_convert_encoding($post?->post?->record?->text, 'UTF-8', 'UTF-8') ?? '';
          $hashtags = $post->entities->hashtags ?? [];
          $userMentions = $post->entities->user_mentions ?? [];

          if (isset($post->entities->hashtags)) {
            //Modity hashtag for twitter
            foreach ($hashtags as $hashtag) {
              $hashtagReplace = mb_substr($rawContent, $hashtag->indices[0], $hashtag->indices[1] - $hashtag->indices[0]);
              $rawContent = str_replace($hashtagReplace, '<a href="https://twitter.com/hashtag/' . ltrim($hashtagReplace, '#') . '" target="_blank" rel="noopener" title="' . $hashtagReplace . '" class="hashtag">' . $hashtagReplace . '</a>', $rawContent);
            }
          }
          
          if (isset($post->entities->user_mentions)) {
            foreach ($userMentions as $userMention) {
              $userMentionReplace = mb_substr($rawContent, $userMention->indices[0], $userMention->indices[1] - $userMention->indices[0]);
              $rawContent = str_replace($userMentionReplace, '<a href="https://twitter.com/' . ltrim($userMentionReplace, '@') . '" target="_blank" rel="noopener" title="' . $userMentionReplace . '" class="user_mention">' . $userMentionReplace . '</a>', $rawContent);
            }
          }
          $media = $post->entities->media ?? $post?->post?->embed?->images ?? $post?->post?->embed?->media?->images ?? [];
          $extendedMedia = $post->extended_entities->media ?? $post?->post?->embed?->video ?? [];
          $photos = [];
          $gifs = [];

          $urls = $post->entities->urls ?? [];

          $videoIds = [];
          foreach ($media as $medium) {
            if (isset($medium->type) && $medium->type === 'photo') {
              $photos[] = $medium;
              // remove photo urls from the content if we're displaying the image
              if (strpos($rawContent, $medium->url) !== FALSE) {
                $rawContent = str_replace($medium->url, '', $rawContent);
              }
            }
            else {
              $photos[] = $medium;
            }
          }
          foreach ($extendedMedia as $extMedium) {
            if ($extMedium->type === 'animated_gif' || $extMedium->mimeType === 'video/mp4') {
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

          if (isset($post?->post?->record?->facets)) {
            $offset = 0;
            foreach ($post->post->record->facets as $urlIndex) {
              $type = $urlIndex->features[0]->{'$type'};
              $start = $urlIndex->index->byteStart + $offset;
              $end = $urlIndex->index->byteEnd + $offset;
              $needReplace = substr($rawContent, $start, $end - $start);
              $replaced = "";
              switch($type) {
                case 'app.bsky.richtext.facet#link':
                  $url = $urlIndex->features[0]->uri;
                  $replaced = str_replace($needReplace, '<a href="' . $url . '" target="_blank" rel="noopener" class="url">' . $needReplace . '</a>', $rawContent);
                  break;
                case 'app.bsky.richtext.facet#tag':
                  $tag = $urlIndex->features[0]->tag;
                  $replacement = '<a href="https://bsky.app/hashtag/' . ltrim($tag, '#') . '" target="_blank" rel="noopener" title="' . $tag . '" class="hashtag">#' . $tag . '</a>';
                  $replaced = substr_replace($rawContent, $replacement, $start, strlen($tag)+1);
                  break;
                case 'app.bsky.richtext.facet#mention':
                  $mention = $urlIndex->features[0]->did;
                  $replacement = '<a href="https://bsky.app/profile/' . ltrim($mention, '@') . '" target="_blank" rel="noopener" title="' . $needReplace . '" class="user_mention">' . $needReplace . '</a>';
                  $replaced = substr_replace($rawContent, $replacement, $start, strlen($needReplace));
                  break;
                default:
                  break;
              }
              $offset += strlen($replaced) - strlen($rawContent);
              $rawContent = $replaced;
            }
          }

          $blockPost = [
            'type' => 'tweet',
            'profile_img' => $profileImg,
            'handle' => $handle,
            'tweet_id' => $tweetId,
            'timestamp' => socialblock_get_tweet_timediff($timestamp),
            'name' => $name,
            'isTwitter' => !isset($post->post),
          ];
          // sanity check existing content
          $rawContent = Xss::filter($rawContent);

          // extended media, embedded gifs etc Twitter
          if (!empty($gifs)) {
            foreach ($gifs as $gif) {
              if (isset($gif->video_info->variants[0]->url)) {
                $rawContent = '<div class="centerContainer"><video class="tweet_video" autoplay="true" loop="true" preload="none"><source src="' . $gif->video_info->variants[0]->url . '" type="video/mp4"/></video></div>' . $rawContent;
              }
              elseif (isset($gif->ref->link)) {
                $rawContent = '<div class="centerContainer"><video class="tweet_video" autoplay="true" loop="true" preload="none"><source src="' . $gif->ref->link . '" type="video/mp4"/></video></div>' . $rawContent;
              }
            }
          }
          
          //Render video and gifs for BlueSky. Goes on same condition. Can render only one element
          if (isset($post?->post?->embed) && $post?->post?->embed->{'$type'} === 'app.bsky.embed.recordWithMedia#view') {
            $playlistUrl = $post->post?->embed?->media?->playlist ?? $post->post?->embed?->media?->external?->uri ?? '';
            $posterUrl = $post->post->embed?->media?->thumbnail ?? $post->post->embed?->media?->external?->thumb ?? '';
            $rawContent = '<div class="centerContainer"><video class="tweet_video" autoplay="true" poster="' . $posterUrl . '" loop="true" preload="none"><source src="' . $playlistUrl . '" type="video/mp4"/></video></div>' . $rawContent;
          }
          elseif (isset($post?->post?->embed) && $post?->post?->embed->{'$type'} === 'app.bsky.embed.video#view') {
            $playlistUrl = $post->post->embed->uri ?? '';
            $posterUrl = $post->post->embed->thumbnail ?? '';
            $rawContent = '<div class="centerContainer"><video class="tweet_video" autoplay="true" poster="' . $posterUrl . '" loop="true" preload="none"><source src="' . $playlistUrl . '" type="video/mp4"/></video></div>' . $rawContent;
          }
          //Render youtube or vide from external link fro Blue Sky
          elseif (isset($post?->post?->embed) && $post?->post?->embed->{'$type'} === 'app.bsky.embed.external#view') {
            $externalUrl = $post->post->embed->external->uri ?? '';
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([0-9A-Za-z_-]{11})/', $externalUrl, $matches)) {
              $videoId = $matches[1];
              $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
              $rawContent .= '<iframe class="yt_embed" title="' . t('Watch on YouTube') . '" src="' . $embedUrl . '?rel=0&amp;controls=1&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>';
            } elseif (isset($post->post->embed->external->thumb)) {
              $thumb = $post->post->embed->external->thumb;
              $rawContent = '<div class="centerContainer"><video class="tweet_video" autoplay="true" poster="' . $thumb . '" loop="true" preload="none"><source src="' . $externalUrl . '" type="video/mp4"/></video></div>' . $rawContent;
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
                $photo_out[] = ['url' => $photo->media_url_https ?? $photo?->fullsize ?? ''];
              }
            }
            $blockPost['photos'] = $photo_out;
          }

          if (!empty($videoIds)) {
            $rawContent .= '<iframe class="yt_embed" title="' . t('Watch on YouTube') . '" src="https://www.youtube.com/embed/' . $videoIds[0] . '?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>';
          }
          $blockPost['content'] = $rawContent;
          $blockPosts[] = $blockPost;
        }
        else {
          $moduleHandler = \Drupal::service('module_handler');
          if ($moduleHandler->moduleExists('forum')) {
            $topicNode = Node::load($post->id);
            $forumTid = $post->forum_tid;
            $termData = Term::load($forumTid);
            $timestamp = '';

            if ($topicNode !== NULL) {
              $topicCreatedTime = DateTimePlus::createFromTimestamp($topicNode->created->value);
              $now = DateTimePlus::createFromTimestamp(time());
              $timestamp = $now->diff($topicCreatedTime);
              $timestamp = socialblock_get_tweet_timediff($timestamp);
            }

            try {
              $op = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $post->name]);
            } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
            }
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

  public function isTwitterDefined(): bool {
    $config = \Drupal::config('socialblock.settings');
    $data = $config->get('credentials');
    if ($data === NULL || empty($data)) {
      return FALSE;
    }
    $encryptionProfile = EncryptionProfile::load('socialblock');
    if ($encryptionProfile === NULL) {
      return FALSE;
    }
    $settings = unserialize(\Drupal::service('encryption')->decrypt($data, $encryptionProfile), ['allowed_classes' => FALSE]);
    if (!isset($settings['consumerKey']) || empty($settings['consumerKey'])
    || !isset($settings['consumerSecret']) || empty($settings['consumerSecret'])
    || !isset($settings['accessToken']) || empty($settings['accessToken'])
    || !isset($settings['accessTokenSecret']) || empty($settings['accessTokenSecret'])) {
      return FALSE;
    }
    return TRUE;
  }

  public function isBlueSkyDefined(): bool {
    $config = \Drupal::config('socialblock.settings');
    $data = $config->get('credentials');
    if ($data === NULL || empty($data)) {
      return FALSE;
    }
    $encryptionProfile = EncryptionProfile::load('socialblock');
    if ($encryptionProfile === NULL) {
      return FALSE;
    }
    $settings = unserialize(\Drupal::service('encryption')->decrypt($data, $encryptionProfile), ['allowed_classes' => FALSE]);
    if (!isset($settings['blueSkyUserName']) || empty($settings['blueSkyUserName'])
    || !isset($settings['blueSkyAppPassword']) || empty($settings['blueSkyAppPassword'])) {
      return FALSE;
    }
    return TRUE;
  }
}
