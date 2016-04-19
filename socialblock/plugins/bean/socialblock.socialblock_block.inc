<?php

/**
 * @file
 * SocialBlock bean plugin.
 */

require_once(libraries_get_path('twitteroauth') . '/autoload.php');

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
      'twitter_param' => 'ibmapiconnect'
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
    $this->forum_vocabs = $this->get_forum_vocabs();
    $this->forum_tids = $this->get_forum_tids();
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
    $form['forums'] = array(
      '#title' => t('Forums'),
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $bean->forums,
      '#empty' => t('No forums available.'),
      '#multiple' => TRUE
    );
    $form['twitter_api'] = array(
      '#title' => t('Get tweets from'),
      '#type' => 'select',
      '#options' => array(
        0 => t('User'),
        1 => t('Search term')
      ),
      '#default_value' => isset($bean->twitter_api) ? $bean->twitter_api : 0,
      '#required' => TRUE
    );
    $form['twitter_param'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($bean->twitter_param) ? $bean->twitter_param : '',
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
  public function view($bean, $content, $view_mode = 'default', $langcode = NULL) {
    $this->_bean = $bean;
    $tiles = $bean->tiles;
    $forums = $bean->forums;
    switch ($bean->twitter_api) {
      case 0:
        $tweets = $this->get_tweets($bean);
        break;
      case 1:
        $tweets = $this->get_tweets($bean)->statuses;
        break;
      default:
        $tweets = array();
    }
    $posts = array();
    $markup = '<div class="socialblock background">
                 <div class="socialblock container">';
    if (isset($bean->display_all_forums) && $bean->display_all_forums == 1) {
      $forums = $this->get_forum_tids();
      $bean->forums = $forums;
      $bean->data['forums'] = $forums;
    }
    // Any forums?
    if (isset($forums) && !empty($forums)) {
      foreach ($forums as $forum_tid) {
        if ($forum_tid != 0 && !empty(forum_get_topics($forum_tid, 1, $tiles))) {
          foreach (forum_get_topics($forum_tid, 1, $tiles) as $topic) {
            $topic->tweet = FALSE;
            array_push($posts, $topic);
          }
        }
      }
    }
    // Any tweets?
    if (isset($tweets) && !empty($tweets)) {
      foreach ($tweets as $tweet) {
        $tweet->tweet = TRUE;
        array_push($posts, $tweet);
      }
    }
    // Any forum posts or tweets?
    if (isset($posts) && !empty($posts)) {
      uasort($posts, array('SocialBlockBean', 'sort_by_created'));
      $posts = array_slice($posts, 0, $tiles);
      foreach ($posts as $post) {
        // This is a tweet
        if ($post->tweet) {
          // This is a retweet
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
            $raw_content = str_replace($hashtag_replace, '<a href="https://twitter.com/hashtag/' . ltrim($hashtag_replace, '#') . '" target="_blank" class="socialblock hashtag">' . $hashtag_replace . '</a>', $raw_content);
          }
          foreach ($user_mentions_replace as $user_mention_replace) {
            $raw_content = str_replace($user_mention_replace, '<a href="https://twitter.com/' . ltrim($user_mention_replace, '@') . '" target="_blank" class="socialblock user_mention">' . $user_mention_replace . '</a>', $raw_content);
          }
          $media = isset($post->entities->media) ? $post->entities->media : array();
          $photos = array();
          $urls = isset($post->entities->urls) ? $post->entities->urls : array();
          $video_ids = array();
          foreach ($media as $medium) {
            if ($medium->type == 'photo') {
              array_push($photos, $medium);
            }
          }
          foreach ($urls as $url) {
            $short_url = $url->url;
            $expanded_url = $url->expanded_url;
            $true_url = get_headers($expanded_url, 1);
            $true_url = isset($true_url['Location']) ? $true_url['Location'][0] : '';
            if (preg_match('/^(https?:\/\/)?(www\.)?youtube\.com\/.*/i', $true_url)) { //A wild YouTube video url appears!
              array_push($video_ids, preg_replace('/^(https?:\/\/)?(www\.)?youtube\.com\/watch\?v=/i', '', $true_url));
            }
            else if (preg_match('/^(https?:\/\/)?(youtu\.be)\//i', $true_url)) {
              array_push($video_ids, preg_replace('/^(https?:\/\/)?(youtu\.be)\//i', '', $true_url));
            }
            if (strpos($raw_content, $short_url) != FALSE) {
              $raw_content = str_replace($short_url, '<a href="' . $expanded_url . '" target="_blank" class="socialblock url">' . $expanded_url . '</a>', $raw_content);
            }
            else {
              $raw_content = str_replace($short_url, '', $raw_content);
            }
          }
          $markup .= '<div class="socialblock card tweet">
                        <div class="socialblock tweet_info">
                          <img class="socialblock profile_img" src="' . $profile_img . '">
                          <div class="socialblock user_info">
                            <a href="https://twitter.com/' . $handle . '" target="_blank" class="socialblock twitter_user">' . $name . '</a>
                            <div class="socialblock twitter_handle">@' . $handle . '</div>
                          </div>
                          <div class="socialblock misc_info">
                            <a href="https://twitter.com/statuses/' . $tweet_id . '" target="_blank" class="socialblock twitter_bird"></a>
                            <div class="socialblock tweet_timestamp">' . $this->get_tweet_timediff($timestamp) . '</div>
                          </div>
                        </div>';
          if (!empty($photos)) {
            foreach ($photos as $photo) {
              $markup .= '<img class="socialblock tweet_photo" src="' . $photo->media_url_https . '">';
            }
          }
          $markup .= !empty($video_ids) ? '<iframe class="socialblock iframe" src="https://www.youtube-nocookie.com/embed/' . $video_ids[0] . '?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>' : '';
          $markup .= '<div class="socialblock tweet_content">' . $raw_content . '</div>
                      </div>';
        }
        else { //This is a forum topic
          $topic_node = node_load($post->nid);
          $timestamp = date_diff(date_create('@' . $topic_node->created), date_create('@' . time()));
          $op = user_load_by_name($topic_node->name);
          $op_name = isset($op->field_first_name['und'][0]['value']) || isset($op->field_last_name['und'][0]['value']) ? $op->field_first_name['und'][0]['value'] . ' ' . $op->field_last_name['und'][0]['value'] : 'Admin';
          $markup .= '<div class="socialblock card forum_topic">
                          <a href="' . url('node/' . $post->nid) . '" class="socialblock title">' . $topic_node->title . '</a>
                          <div class="socialblock info">
                            <div class="socialblock op">' . t('Posted by') . ' <span class="socialblock op_name">' . $op_name . '</span></div>
                            <div class="socialblock timestamp">' . $this->get_timediff($timestamp) . '</div>
                          </div>
                        </div>';
        }
      }
    }
    else {
      $markup .= '<div class="socialblock no_topics">There are no posts to display.</div>';
    }

    $markup .= '</div>
              </div>';
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
        'js' => empty($posts) ? array() : array(
          libraries_get_path('masonry') . '/masonry.pkgd.min.js',
          drupal_get_path('module', 'socialblock') . '/js/socialblock.js'
        )
      )
    );
    return $content;
  }

  /**
   * @return array
   */
  private function get_forum_vocabs() {
    $vocabs = array();
    $systemvocabs = taxonomy_get_vocabularies();
    if (isset($systemvocabs)) {
      foreach ($systemvocabs as $vocab) {
        if ($vocab->machine_name == 'forums') {
          $vocabs = taxonomy_get_tree($vocab->vid);
        }
      }
    }
    return $vocabs;
  }

  /**
   * @return array
   */
  private function get_forum_tids() {
    $vocabs = $this->get_forum_vocabs();
    $tids = array();
    if (isset($vocabs)) {
      foreach ($vocabs as $vocab) {
        $tids[$vocab->tid] = intval($vocab->tid);
      }
    }
    return $tids;
  }

  /**
   * @param $a
   * @param $b
   * @return int
   */
  private function sort_by_created($a, $b) {
    $at = intval($a->tweet ? strtotime($a->created_at) : $a->created);
    $bt = intval($b->tweet ? strtotime($b->created_at) : $b->created);
    if ($at == $bt) {
      return 0;
    }
    return $at < $bt ? 1 : -1;
  }

  /**
   * @param DateInterval $di
   * @return string
   */
  private function get_timediff(DateInterval $di) {
    $years = $di->y;
    $months = $di->m;
    $days = $di->d;
    $hours = $di->h;
    $minutes = $di->i;
    $seconds = $di->s;
    $diff = $years == 0 ? ($months == 0 ? ($days == 0 ? ($hours == 0 ? ($minutes == 0 ? ($seconds == 0 ? t('Just now') : format_plural($seconds, '1 second ago', '@count seconds ago')) : format_plural($minutes, '1 minute ago', '@count minutes ago')) : format_plural($hours, '1 hour ago', '@count hours ago')) : format_plural($days, '1 day ago', '@count days ago')) : format_plural($months, '1 month ago', '@count months ago')) : format_plural($years, '1 year ago', '@count years ago');
    return $diff;
  }

  /**
   * @param \DateInterval $di
   * @return null|string
   */
  private function get_tweet_timediff(DateInterval $di) {
    $years = $di->y;
    $months = $di->m;
    $days = $di->d;
    $hours = $di->h;
    $minutes = $di->i;
    $seconds = $di->s;
    $diff = $years == 0 ? ($months == 0 ? ($days == 0 ? ($hours == 0 ? ($minutes == 0 ? ($seconds == 0 ? t('now') : $seconds . t('s')) : $minutes . t('m')) : $hours . t('h')) : $days . t('d')) : $months . t('M')) : $years . t('y');
    return $diff;
  }

  /**
   * @param $bean
   * @return mixed
   */
  private function get_tweets(&$bean) {
    $tweets = unserialize(variable_get('socialblock_tweets_' . $bean->bid));
    if (isset($tweets) && !empty($tweets)) {
      return $tweets;
    }
    else {
      return NULL;
    }
  }
}