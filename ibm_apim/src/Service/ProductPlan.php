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

namespace Drupal\ibm_apim\Service;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Formatter\CurrencyFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Class ProductPlan
 *
 * @package Drupal\ibm_apim\Service
 */
class ProductPlan {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected TranslationManager $translationManager;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * ProductPlan constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   * @param \Drupal\ibm_apim\Service\Utils $utils
   */
  public function __construct(LanguageManagerInterface $language_manager, TranslationManager $string_translation, Utils $utils) {
    $this->languageManager = $language_manager;
    $this->translationManager = $string_translation;
    $this->utils = $utils;
  }

  /**
   * @param array $plan
   *
   * @return bool
   */
  private function planHasListedApis(array $plan): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    $result = array_key_exists('apis', $plan) && !empty($plan['apis']);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, $result);
    }
    return $result;
  }

  /**
   * @param $channels
   * @param $planRateLimit
   * @param $planRateData
   *
   * @return array
   * @throws \JsonException
   */
  private function generateLimitsForChannels($channels, $planRateLimit, $planRateData): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    $resources = [];

    foreach (array_keys($channels) as $channelName) {
      // use array for easy extension when publish comes along
      foreach (['subscribe'] as $operation) {
        $resources[$channelName][$operation] = [
          // copy plan rate limit as async apis currently don't support any rate limits
          // async api plan rate limit should always be unlimited
          'rateLimit' => $planRateLimit,
        ];

        if ($planRateData !== NULL) {
          $resources[$channelName][$operation]['rateData'] = json_encode($planRateData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $resources;
  }

  /**
   * @param $swaggerPaths
   * @param $planRateLimit
   * @param $planRateData
   *
   * @return array
   * @throws \JsonException
   */
  private function generateLimitsForPaths($swaggerPaths, $planRateLimit, $planRateData): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $resources = [];

    if (isset($swaggerPaths)) {
      foreach ($swaggerPaths as $pathName => $path) {
        foreach ($path as $verb => $op) {
          $resources[$pathName][mb_strtoupper($verb)] = [
            'rateLimit' => $planRateLimit,
            'op' => $op,
          ];

          if ($planRateData !== NULL) {
            $resources[$pathName][mb_strtoupper($verb)]['rateData'] = json_encode($planRateData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
          }
          if (isset($op['soap-action']) && !empty($op['soap-action'])) {
            $pos = mb_strpos($op['soap-action'], ':');
            if ($pos !== FALSE) {
              $parts = explode(':', $op['soap-action']);
              $resources[$pathName][mb_strtoupper($verb)]['soap-action'] = $parts[1] ?? $op['soap-action'];
            }
            else {
              $resources[$pathName][mb_strtoupper($verb)]['soap-action'] = $op['soap-action'];
            }
          }
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $resources;
  }

  /**
   * @param $api
   *
   * @return array
   */
  private function createApiNodeForPlan(&$api): array {
    $apiNode = [];
    if (isset($api['node'])) {
      $apiNode['nid'] = $api['node']->id();
      $apiNode['title'] = $api['node']->getTitle();
      $apiNode['protocol'] = $api['node']->api_protocol->getSetting('allowed_values')[$api['node']->api_protocol->value];
      $apiNode['url'] = $api['node']->apic_url->getValue();
      $apiNode['id'] = $api['node']->api_id->getValue();
      $apiNode['version'] = $api['node']->apic_version->getValue();
    }

    return $apiNode;
  }

  /**
   * @param $apiNode
   * @param $lang_code
   *
   * @return mixed
   */
  private function translateNodeIfPossible(&$apiNode, $lang_code) {
    if ($apiNode !== NULL) {
      $hasTranslation = $apiNode->hasTranslation($lang_code);
      if ($hasTranslation === TRUE) {
        $apiNode = $apiNode->getTranslation($lang_code);
      }
    }

    return $apiNode;
  }

  /**
   * @param $apiNodes
   * @param $lang_code
   * @param $planRateLimit
   * @param $planRateData
   *
   * @return array
   * @throws \JsonException
   */
  private function processAllProductApis(&$apiNodes, $lang_code, $planRateLimit, $planRateData): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $nodes = [];

    foreach ($apiNodes as $api) {
      if (isset($api['node'])) {
        $api['node'] = $this->translateNodeIfPossible($api['node'], $lang_code);

        $apiSafeNodeRef = Html::getClass($api['node']->apic_ref->value);
        $nodes[$apiSafeNodeRef] = $this->createApiNodeForPlan($api);

        $apiDocument = unserialize($api['node']->api_swagger->value, ['allowed_classes' => FALSE]);
        if (isset($apiDocument['asyncapi'])) {
          $nodes[$apiSafeNodeRef]['resources'] = $this->generateLimitsForChannels($apiDocument['channels'], $planRateLimit, $planRateData);
        }
        else {
          $nodes[$apiSafeNodeRef]['resources'] = $this->generateLimitsForPaths($apiDocument['paths'], $planRateLimit, $planRateData);
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $nodes;
  }

  /**
   * @param $plan
   * @param $apiNodes
   * @param $lang_code
   * @param $planRateLimit
   * @param $planRateData
   *
   * @return array
   * @throws \JsonException
   */
  private function processAllPlanApis(&$plan, &$apiNodes, $lang_code, $planRateLimit, $planRateData, $productApis): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $apiNodeMap = [];
    foreach ($apiNodes as $apiNode) {
      if (isset($apiNode['node'])) {
        $apiRef = Html::getClass($apiNode['node']->apic_ref->value);
        $apiNodeMap[$apiRef] = $apiNode;
      }
    }

    $nodes = [];

    foreach ($plan['apis'] as $key => $planApi) {
      $apiSafeName = Html::getClass($productApis[$key]['name']);

      $api = $apiNodeMap[$apiSafeName];
      $api['node'] = $this->translateNodeIfPossible($api['node'], $lang_code);

      $nodes[$apiSafeName] = $this->createApiNodeForPlan($api);

      $apiNode = &$nodes[$apiSafeName];
      $apiNode['resources'] = [];

      //Individual operations selected in APIM UI
      if (array_key_exists('operations', $planApi) && is_array($planApi['operations'])) {
        foreach ($planApi['operations'] as $resource) {
          if (in_array(mb_strtoupper($resource['operation']), [
            'PUT',
            'POST',
            'GET',
            'DELETE',
            'OPTIONS',
            'HEAD',
            'TRACE',
            'PATCH',
          ])) {
            // remove any query param portion of the path
            $path = mb_strstr($resource['path'], '?', TRUE) ?: $resource['path'];

            $apiNode['resources'][$path][mb_strtoupper($resource['operation'])] = [
              'rateLimit' => $planRateLimit,
            ];

            if (isset($op['x-ibm-soap']['soap-action']) && !empty($op['x-ibm-soap']['soap-action'])) {
              $apiNode['resources'][$path][mb_strtoupper($resource['operation'])]['soap-action'] = $op['x-ibm-soap']['soap-action'];
            }

            // include rate limit info
            $rateLimit = $planRateLimit;
            $operationRateData = NULL;
            if ($planRateData !== NULL) {
              $operationRateData = $planRateData;
              unset($operationRateData['#bursts']);
            }
            if (isset($resource['rate-limits'])) {
              if (count($resource['rate-limits']) > 1) {
                $operationRateData = ['#rates' => []];
                $operationRateData['#rateLabel'] = $this->translationManager->translate('Rate limits');
                $operationRateData['#burstLabel'] = $this->translationManager->translate('Burst limits');
                foreach ($resource['rate-limits'] as $rateLimit) {
                  $operationRateData['#rates'][] = $this->parseRateLimit($rateLimit['value']);
                }
                $rateLimit = $this->translationManager->translate('@count rate limits *', ['@count' => count($resource['rate-limits'])]);
              }
              else {
                $rateLimit = $this->parseRateLimit(current($resource['rate-limits'])['value']);
                unset($operationRateData);
              }
            }
            elseif (isset($resource['rate-limit']) && $resource['rate-limit']['value'] !== NULL) {
              $rateLimit = $this->parseRateLimit($resource['rate-limit']['value']);
              unset($operationRateData);
            }
            elseif (isset($plan['rateLimit']['value'])) {
              $rateLimit = $this->parseRateLimit($plan['rateLimit']['value']);
              unset($operationRateData);
            }
            $apiNode['resources'][$path][mb_strtoupper($resource['operation'])]['rateLimit'] = $rateLimit;
            if (isset($operationRateData)) {
              $apiNode['resources'][$path][mb_strtoupper($resource['operation'])]['rateData'] = json_encode($operationRateData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }
            $apiNode['resources'][$path][mb_strtoupper($resource['operation'])]['op'] = $resource;
            if (isset($resource['x-ibm-soap']['soap-action']) && !empty($resource['x-ibm-soap']['soap-action'])) {
              $pos = strpos($resource['x-ibm-soap']['soap-action'], ':');
              if ($pos !== FALSE) {
                $parts = explode(':', $resource['x-ibm-soap']['soap-action']);
                $apiNode['resources'][$path][mb_strtoupper($resource['operation'])]['soap-action'] = $parts[1] ?? $resource['x-ibm-soap']['soap-action'];
              }
              else {
                $apiNode['resources'][$path][mb_strtoupper($resource['operation'])]['soap-action'] = $resource['x-ibm-soap']['soap-action'];
              }
            }
          }
        }
      }
      else {
        $apiDocument = unserialize($api['node']->api_swagger->value, ['allowed_classes' => FALSE]);
        if (isset($apiDocument['asyncapi'])) {
          $apiNode['resources'] = $this->generateLimitsForChannels($apiDocument['channels'], $planRateLimit, $planRateData);
        }
        elseif (isset($apiDocument['paths'])) {
          $apiNode['resources'] = $this->generateLimitsForPaths($apiDocument['paths'], $planRateLimit, $planRateData);
        }
        else {
          $apiNode['resources'] = [];
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $nodes;
  }

  /**
   * @param string $planId The ID of the plan object to process
   * @param array $plan The plan object to process
   * @param array $apiList All drupal API nodes for the product
   * @param array|null $componentRateLimits Contains Rate limit definitions if published to a lightweight gateway
   *
   * @return array Plan array that contains all relevant data for each api within it
   * @throws \JsonException
   */
  public function process(string $planId, array $plan, array $apiList, array $productApis, array|null $componentRateLimits = NULL): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $tooltip = NULL;
    $planArray = [];
    $planArray['nodes'] = [];
    $planArray['data'] = $plan;

    // check for any translated plan names and descriptions
    $lang_code = $this->languageManager->getCurrentLanguage()->getId();
    if (array_key_exists('x-ibm-languages', $planArray['data'])) {
      if (array_key_exists('title', $planArray['data']['x-ibm-languages']) && array_key_exists($lang_code, $planArray['data']['x-ibm-languages']['title'])) {
        $planArray['data']['title'] = $planArray['data']['x-ibm-languages']['title'][$lang_code];
      }

      if (array_key_exists('description', $planArray['data']['x-ibm-languages']) && array_key_exists($lang_code, $planArray['data']['x-ibm-languages']['description'])) {
        $planArray['data']['description'] = $planArray['data']['x-ibm-languages']['description'][$lang_code];
      }
    }

    $parsedRateLimit = $this->parseRateLimits($plan, $componentRateLimits);
    $planArray['rateLimit'] = $parsedRateLimit['planRateLimit'];
    $planArray['requiresApproval'] = $plan['approval'] ?? FALSE;
    $planArray['planId'] = $planId;

    // set default tooltip to be the parsed one
    if (isset($parsedRateLimit['tooltip']) && $parsedRateLimit['tooltip'] !== NULL) {
      $tooltip = $parsedRateLimit['tooltip'];
      $planArray['rateData'] = json_encode($parsedRateLimit['tooltip'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    if (is_array($apiList) && count($apiList) > 0) {
      if ($this->planHasListedApis($plan)) {
        $planArray['nodes'] = $this->processAllPlanApis($plan, $apiList, $lang_code, $parsedRateLimit['planRateLimit'], $tooltip, $productApis);
      }
      else {
        $planArray['nodes'] = $this->processAllProductApis($apiList, $lang_code, $parsedRateLimit['planRateLimit'], $tooltip);
      }
    }
    $planArray['custom'] = $this->parseCustomExtensions($plan);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $planArray;
  }

  /**
   * Include any custom attributes in the plan in the array fed to twig
   *
   * @param $plan
   *
   * @return array
   */
  public function parseCustomExtensions($plan): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    $returnValue = [];
    foreach (array_keys($plan) as $key) {
      if ($key !== 'x-ibm-languages' && strpos($key, 'x-') === 0) {
        $keyName = substr($key, strlen('x-'));
        $returnValue[$keyName] = $plan[$key];
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $returnValue;
  }

  /**
   * @param $plan
   *
   * @return array
   */
  public function parseRateLimits($plan, $componentRateLimits = NULL): array {
    $planRateLimit = $this->parseRateLimit('unlimited');
    $tooltip = NULL;
    if (isset($plan['rate-limits']) || isset($plan['burst-limits'])) {
      $rateLimitCount = 0;
      $burstLimitCount = 0;
      if (array_key_exists('rate-limits', $plan) && $plan['rate-limits'] !== NULL) {
        $rateLimitCount = count($plan['rate-limits']);
      }
      elseif (array_key_exists('rate-limit', $plan) && $plan['rate-limit'] !== NULL) {
        // handle having burst-limits but rate-limit (mix of v5 and v4 schemas)
        $rateLimitCount = 1;
      }
      if (array_key_exists('burst-limits', $plan) && $plan['burst-limits'] !== NULL) {
        $burstLimitCount = count($plan['burst-limits']);
      }
      if ($rateLimitCount === 0) {
        // if no rate limits but there is a burst limit then rate limit assumed to be unlimited
        $plan['rate-limits'][] = ['value' => 'unlimited'];
        $rateLimitCount = count($plan['rate-limits']);
      }
      if (($rateLimitCount + $burstLimitCount) > 1) {
        $tooltip = ['#rates' => [], '#bursts' => []];
        $tooltip['#rateLabel'] = $this->translationManager->translate('Rate limits');
        $tooltip['#burstLabel'] = $this->translationManager->translate('Burst limits');
        if (array_key_exists('rate-limits', $plan) && !empty($plan['rate-limits'])) {
          foreach ($plan['rate-limits'] as $rateLimit) {
            $tooltip['#rates'][] = $this->parseRateLimit($rateLimit['value']);
          }
        }
        elseif ((!isset($plan['rate-limits']) || empty($plan['rate-limits'])) && $plan['rate-limit'] !== NULL) {
          // handle having burst-limits but rate-limit (mix of v5 and v4 schemas)
          $tooltip['#rates'][] = $this->parseRateLimit($plan['rate-limit']['value']);
        }
        if (array_key_exists('burst-limits', $plan) && !empty($plan['burst-limits'])) {
          foreach ($plan['burst-limits'] as $rateLimit) {
            $tooltip['#bursts'][] = $this->parseRateLimit($rateLimit['value']);
          }
        }
        $planRateLimit = $this->translationManager->translate('@count rate limits *', ['@count' => $rateLimitCount + $burstLimitCount]);
      }
      elseif ($rateLimitCount > 0) {
        if (!isset($plan['rate-limits']) && $plan['rate-limit'] !== NULL) {
          // handle having burst-limits but no rate-limit (mix of v5 and v4 schemas)
          $planRateLimit = $this->parseRateLimit($plan['rate-limit']['value']);
        }
        else {
          $planRateLimit = $this->parseRateLimit(current($plan['rate-limits'])['value']);
        }
      }
      else {
        $planRateLimit = $this->parseRateLimit(current($plan['burst-limits'])['value']);
      }
    }
    elseif (isset($plan['rate-limit']) && $plan['rate-limit']['value'] !== NULL) {
      $planRateLimit = $this->parseRateLimit($plan['rate-limit']['value']);
    } elseif (isset($plan['rateLimitMap']['plan-limit']) && isset($componentRateLimits)) {
      $rateLimitName = $plan['rateLimitMap']['plan-limit'];
      $rateLimit = $componentRateLimits[$rateLimitName][0];
      $periodCount = $rateLimit['intervalLen'];
      $quantity = $rateLimit['max'];
      $intervalUnit = $rateLimit['intervalUnit'];
      $planRateLimit = $this->translationManager->formatPlural($periodCount, '@quantity @call per @intervalUnit', '@quantity @call per @count @intervalUnits', [
        '@quantity' => $this->utils->format_number_locale($quantity, 0),
        '@count' => $this->utils->format_number_locale($periodCount, 0),
        '@intervalUnit' => $intervalUnit,
        '@call' => $this->translationManager->formatPlural($quantity, 'call', 'calls'),
      ]);
    }

    return ['planRateLimit' => $planRateLimit, 'tooltip' => $tooltip];
  }

  /**
   * Convert the programmatic rate limit into a translatable nicely formatted form
   *
   * @param $value
   *
   * @return null|string
   */
  public function parseRateLimit($value): ?string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, $value);
    }
    $returnValue = NULL;
    if ($value !== NULL) {
      if (mb_strtolower($value) === 'unlimited') {
        $returnValue = $this->translationManager->translate('unlimited');
      }
      else {
        $parts = explode('/', $value);
        [$quantity, $timePeriod] = $parts;
        $periodCount = NULL;
        preg_match("~^(\d+)~", $timePeriod, $m);
        if (isset($m[1])) {
          $periodCount = $m[1];
        }
        if ($periodCount === NULL) {
          $periodCount = 1;
        }
        $period = trim(str_replace($periodCount, '', $timePeriod, $i));

        switch ($period) {
          case 'second':
            $returnValue = $this->translationManager
              ->formatPlural($periodCount, '@quantity calls per second', '@quantity calls per @count seconds', [
                '@quantity' => $this->utils->format_number_locale($quantity, 0),
                '@count' => $this->utils->format_number_locale($periodCount, 0),
              ]);
            break;
          case 'minute':
            $returnValue = $this->translationManager
              ->formatPlural($periodCount, '@quantity calls per minute', '@quantity calls per @count minutes', [
                '@quantity' => $this->utils->format_number_locale($quantity, 0),
                '@count' => $this->utils->format_number_locale($periodCount, 0),
              ]);
            break;
          case 'hour':
            $returnValue = $this->translationManager
              ->formatPlural($periodCount, '@quantity calls per hour', '@quantity calls per @count hours', [
                '@quantity' => $this->utils->format_number_locale($quantity, 0),
                '@count' => $this->utils->format_number_locale($periodCount, 0),
              ]);
            break;
          case 'day':
            $returnValue = $this->translationManager
              ->formatPlural($periodCount, '@quantity calls per day', '@quantity calls per @count days', [
                '@quantity' => $this->utils->format_number_locale($quantity, 0),
                '@count' => $this->utils->format_number_locale($periodCount, 0),
              ]);
            break;
          case 'week':
            $returnValue = $this->translationManager
              ->formatPlural($periodCount, '@quantity calls per week', '@quantity calls per @count weeks', [
                '@quantity' => $this->utils->format_number_locale($quantity, 0),
                '@count' => $this->utils->format_number_locale($periodCount, 0),
              ]);
            break;
        }
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $returnValue;
  }

  /**
   * Given a billing object it returns a display string for that price plan
   *
   * @param array $billing
   *
   * @return array
   */
  public function parseBilling(array $billing = []): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $billingText = '' . $this->translationManager->translate('Free');
    $trialPeriodText = '';

    if (isset($billing['billing'], $billing['currency'])) {
      $numberFormatRepository = new NumberFormatRepository();
      $currencyRepository = new CurrencyRepository;
      $currencyFormatter = new CurrencyFormatter($numberFormatRepository, $currencyRepository);
      $price = $currencyFormatter->format($billing['price'], $billing['currency']);
      // special case to avoid displaying $0.00
      if (!((float) $billing['price'] > 0)) {
        $billingText = '' . $this->translationManager->translate('Free');
      }
      else {
        $billingText = '' . $this->translationManager->formatPlural($billing['period'], '@price per @period-unit', '@price per @period @period-unit', [
            '@price' => $price,
            '@period' => $billing['period'],
            '@period-unit' => $this->timePeriodLookup($billing['period'], $billing['period-unit']),
          ]);
      }
      if (isset($billing['trial-period'], $billing['trial-period-unit'])) {
        // special case to avoid displaying (0 days trial period)
        if ((int) $billing['trial-period'] === 0) {
          $trialPeriodText .= $this->translationManager->translate('(No trial period)');
        }
        else {
          $trialPeriodText .= $this->translationManager
            ->formatPlural($billing['trial-period'], ' (@length @period-unit trial period)', ' (@length @period-unit trial period)', [
              '@length' => $billing['trial-period'],
              '@period-unit' => $this->timePeriodLookup($billing['trial-period'], $billing['trial-period-unit']),
            ]);
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }

    return [
      'billingText' => $billingText,
      'trialPeriodText' => $trialPeriodText,
    ];
  }

  /**
   * Small lookup function to allow handling plurals of time periods
   *
   * @param $quantity
   * @param $period
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup|string
   */
  protected function timePeriodLookup($quantity, $period) {
    $returnValue = '';
    switch ($period) {
      case 'year':
        $returnValue = $this->translationManager->formatPlural($quantity, 'year', 'yeary');
        break;
      case 'month':
        $returnValue = $this->translationManager->formatPlural($quantity, 'month', 'months');
        break;
      case 'week':
        $returnValue = $this->translationManager->formatPlural($quantity, 'week', 'weeks');
        break;
      case 'day':
        $returnValue = $this->translationManager->formatPlural($quantity, 'day', 'days');
        break;
      case 'hour':
        $returnValue = $this->translationManager->formatPlural($quantity, 'hour', 'hours');
        break;
      case 'minute':
        $returnValue = $this->translationManager->formatPlural($quantity, 'minute', 'minutes');
        break;
      case 'second':
        $returnValue = $this->translationManager->formatPlural($quantity, 'second', 'seconds');
        break;
    }
    return $returnValue;
  }

}
