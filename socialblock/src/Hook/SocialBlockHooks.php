<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Provides a Social block.
 */
namespace Drupal\socialblock\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class SocialBlockHooks {
  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron() {

    $configInstances = \Drupal::state()->get('socialblock.config');
    if ($configInstances !== NULL && !empty($configInstances)) {
      foreach ($configInstances as $blockUuid => $settings) {

        $numberOfTiles = (int) $settings['numberOfTiles'];
        $twitterSearchBy = (int) $settings['twitterSearchBy'];      // In the form of 0 = userid, 1 = search;
        $twitterSearchParameter = $settings['twitterSearchParameter'];
        $twitterTweetTypes = (int) $settings['twitterTweetTypes'];  // In the form of 0 = tweets, 1 = tweets and replies

        // These are the settings for the twitter oauth
        $config = \Drupal::config('socialblock.settings');
        $data = $config->get('credentials');
        if ($data !== NULL && !empty($data)) {
          $encryptionProfile = EncryptionProfile::load('socialblock');
          if ($encryptionProfile !== NULL) {
            $settings = unserialize(\Drupal::service('encryption')->decrypt($data, $encryptionProfile), ['allowed_classes' => FALSE]);
          }
          else {
            \Drupal::logger('socialblock')->info('Social Block : The "socialblock" encryption profile is missing.');
            $settings = [];
          }
        }
        else {
          $settings = [];
        }
        if (!isset($settings['consumerKey'])) {
          $settings['consumerKey'] = '';
        }
        if (!isset($settings['consumerSecret'])) {
          $settings['consumerSecret'] = '';
        }
        if (!isset($settings['accessToken'])) {
          $settings['accessToken'] = '';
        }
        if (!isset($settings['accessTokenSecret'])) {
          $settings['accessTokenSecret'] = '';
        }

        if ($settings['consumerKey'] !== NULL && !empty($settings['consumerKey']) && $settings['consumerSecret'] !== NULL && !empty($settings['consumerSecret']) && $settings['accessToken'] !== NULL && !empty($settings['accessToken']) && $settings['accessTokenSecret'] !== NULL && !empty($settings['accessTokenSecret'])) {
          $api = '';
          $apiParam = '';

          switch ($twitterSearchBy) {

            // Show tweets for a given user id
            case 0;
              $api = 'statuses/user_timeline';
              $apiParam = 'screen_name';
              break;

            // Show tweets matching a search term
            case 1;
              $api = 'search/tweets';
              $apiParam = 'q';
              break;
          }

          $tweets = [];

          switch ($twitterTweetTypes) {

            // Displaying just tweets
            case 0:
              $remainingCalls = socialblock_not_rate_limited($settings);

              if ($remainingCalls > 0) {
                $temp = socialblock_call_twitter_api($settings, $api, [
                  $apiParam => $twitterSearchParameter,
                  'count' => $numberOfTiles,
                ]);
                $temp2 = $temp;
                $remainingCalls--;
                $all_tweets = FALSE;

                while (!$all_tweets) {
                  if ($remainingCalls > 0 && !empty($temp)) {
                    $max_id = end($temp)->id;
                    foreach ($temp as $index => $value) {
                      if ($value->in_reply_to_status_id === NULL) {
                        $tweets[] = $value;
                      }
                    }
                    $all_tweets = sizeof($tweets) === $numberOfTiles;
                    if (!$all_tweets) {
                      $temp = socialblock_call_twitter_api($settings, $api, [
                        $apiParam => $twitterSearchParameter,
                        'count' => $numberOfTiles - sizeof($tweets),
                        'max_id' => $max_id - 1,
                      ]);
                      $remainingCalls--;
                      $temp2 = empty($temp) ? $temp2 : $temp;
                    }
                  }
                  else {
                    $tweets = $temp2;
                    $all_tweets = TRUE;
                  }
                }
              }
              else {
                \Drupal::logger('socialblock')
                  ->info('Social Block : Twitter API rate limit hit!');
              }
              break;

            // Tweets and replies
            case 1:
              $remainingCalls = socialblock_not_rate_limited($settings);
              if ($remainingCalls > 0) {
                $tweets = socialblock_call_twitter_api($settings, $api, [
                  $apiParam => $twitterSearchParameter,
                  'count' => $numberOfTiles,
                ]);
              }
              else {
                \Drupal::logger('socialblock')
                  ->info('Social Block : Twitter API rate limit hit!');
              }
              break;
            default:
              $tweets = [];
          }

          // Store tweets as state config so that we can use it when rendering the socialblock
          $dataInstances = \Drupal::state()->get('socialblock.data');
          if ($dataInstances === NULL) {
            $dataInstances = [];
          }
          $dataInstances[$blockUuid] = $tweets;

          \Drupal::state()->set('socialblock.data', $dataInstances);

          \Drupal::logger('socialblock')
            ->info('Social Block : Retrieved %num_tweets tweets for block with uuid [%uuid]', [
              '%num_tweets' => sizeof($tweets),
              '%uuid' => $blockUuid,
            ]);

        } // we had complete twitter credentials

      } // foreach($blocks as $blockid)
    }
  }

  /**
   * Add twig template
   * Implements hook_theme
   * @param $existing
   * @param $type
   * @param $theme
   * @param $path
   *
   * @return array
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'socialblock_block' => [
        'variables' => [
          'posts' => NULL,
        ],
      ],
    ];
  }
 }
