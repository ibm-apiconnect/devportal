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

namespace Drupal\product\Service;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Formatter\CurrencyFormatter;
use CommerceGuys\Intl\Formatter\NumberFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\ibm_apim\Service\Utils;

class ProductPlan {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected $utils;

  public function __construct(LanguageManagerInterface $language_manager, TranslationManager $string_translation, Utils $utils) {
    $this->languageManager = $language_manager;
    $this->translationManager = $string_translation;
    $this->utils = $utils;
  }

  /**
   * @param $planId
   * @param $plan
   * @param $apiNodes
   * @param $apiRefs
   *
   * @return array
   */
  public function process($planId, $plan, $apiNodes, $apiRefs): array {
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

    // put a plain API list in the raw plan data so we can index it in the template
    $planArray['data']['apiList'] = [];
    if (isset($planArray['data']['apis']) && !empty($planArray['data']['apis'])) {
      foreach ($planArray['data']['apis'] as $key => $api) {
        $planArray['data']['apiList'][] = $key;
      } // end for
    }
    $parsedRateLimit = $this->parseRateLimits($plan);

    $planArray['rateLimit'] = $parsedRateLimit['planRateLimit'];
    if ($parsedRateLimit['tooltip'] !== NULL) {
      $planArray['rateData'] = json_encode($parsedRateLimit['tooltip'], JSON_UNESCAPED_UNICODE);
    }
    $planArray['requiresApproval'] = $plan['approval'] ?? FALSE;
    $planArray['planId'] = $planId;
    if (is_array($apiNodes) && count($apiNodes) > 0) {
      foreach ($apiNodes as $apiNode) {
        // ensure use the translated version of api nodes
        $hasTranslation = $apiNode['node']->hasTranslation($lang_code);
        if ($hasTranslation === TRUE) {
          $apiNode['node'] = $apiNode['node']->getTranslation($lang_code);
        }

        $apiSafeNodeRef = Html::getClass($apiNode['node']->apic_ref->value);
        $planArray['nodes'][$apiSafeNodeRef] = [];
        $planArray['nodes'][$apiSafeNodeRef]['nid'] = $apiNode['node']->id();
        $planArray['nodes'][$apiSafeNodeRef]['enabled'] = FALSE;
        $planArray['nodes'][$apiSafeNodeRef]['title'] = $apiNode['node']->getTitle();
        $planArray['nodes'][$apiSafeNodeRef]['protocol'] = $apiNode['node']->api_protocol->getValue();
        $planArray['nodes'][$apiSafeNodeRef]['url'] = $apiNode['node']->apic_url->getValue();
        $planArray['nodes'][$apiSafeNodeRef]['id'] = $apiNode['node']->api_id->getValue();
        $planArray['nodes'][$apiSafeNodeRef]['version'] = $apiNode['node']->apic_version->getValue();
        $planArray['nodes'][$apiSafeNodeRef]['resources'] = [];
        $swagger = unserialize($apiNode['node']->api_swagger->value, ['allowed_classes' => FALSE]);
        if ($swagger !== NULL && isset($swagger['paths']) && is_array($swagger['paths'])) {
          // build up list of resources
          foreach ($swagger['paths'] as $pathName => $path) {
            $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName] = [];
            foreach ($path as $verb => $op) {
              if (in_array(mb_strtoupper($verb), [
                'PUT',
                'POST',
                'GET',
                'DELETE',
                'OPTIONS',
                'TRACE',
                'HEAD',
                'PATCH',
              ])) {
                $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)] = ['enabled' => FALSE];
                $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['rateLimit'] = $parsedRateLimit['planRateLimit'];
                if (isset($op['x-ibm-soap']['soap-action']) && !empty($op['x-ibm-soap']['soap-action'])) {
                  $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['soap-action'] = $op['x-ibm-soap']['soap-action'];
                }
              }
            }
          }

        }
        // now mark included apis as enabled
        if (array_key_exists('apis', $plan) && !empty($plan['apis'])) {
          foreach ($plan['apis'] as $apiName => $planApi) {
            // only include apis matching the current API
            if (array_key_exists($apiName, $planArray['data']['apis'])) {
              $apiSafeName = Html::getClass($apiName);
              $planArray['nodes'][$apiSafeName]['enabled'] = TRUE;
              if (!isset($planArray['nodes'][$apiSafeName]['resources']) || !is_array($planArray['nodes'][$apiSafeName]['resources'])) {
                $planArray['nodes'][$apiSafeName]['resources'] = [];
              }
              if (array_key_exists('operations', $planApi) && is_array($planApi['operations'])) {
                foreach ($planApi['operations'] as $resource) {
                  if (in_array(mb_strtoupper($resource['operation']), [
                    'PUT',
                    'POST',
                    'GET',
                    'DELETE',
                    'OPTIONS',
                    'HEAD',
                    'PATCH',
                  ])) {
                    // remove any query param portion of the path
                    $path = mb_strstr($resource['path'], '?', TRUE) ?: $resource['path'];
                    if (!isset($planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])])) {
                      $planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])] = [];
                    }
                    $planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])]['enabled'] = TRUE;
                    // include rate limit info
                    $rateLimit = $parsedRateLimit['planRateLimit'];
                    $opTooltip = NULL;
                    if ($tooltip !== NULL) {
                      $opTooltip = $tooltip;
                      unset($opTooltip['bursts']);
                    }
                    if (isset($resource['rate-limits'])) {
                      if (count($resource['rate-limits']) > 1) {
                        $opTooltip = ['rates' => []];
                        $opTooltip['rateLabel'] = $this->translationManager->translate('Rate limits');
                        $opTooltip['burstLabel'] = $this->translationManager->translate('Burst limits');
                        foreach ($resource['rate-limits'] as $rateName => $rateLimit) {
                          $opTooltip['rates'][] = $this->parseRateLimit($rateLimit['value']);
                        }
                        $rateLimit = $this->translationManager->translate('@count rate limits *', ['@count' => count($resource['rate-limits'])]);
                      }
                      else {
                        $rateLimit = $this->parseRateLimit(array_shift($resource['rate-limits'])['value']);
                      }
                    }
                    elseif (isset($resource['rate-limit']) && $resource['rate-limit']['value'] !== NULL) {
                      $rateLimit = $this->parseRateLimit($resource['rate-limit']['value']);
                    }
                    elseif (isset($plan['rateLimit']['value'])) {
                      $rateLimit = $this->parseRateLimit($plan['rateLimit']['value']);
                    }
                    $planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])]['rateLimit'] = $rateLimit;
                    if ($opTooltip !== NULL) {
                      $planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])]['rateData'] = json_encode($opTooltip, JSON_UNESCAPED_UNICODE);
                    }
                    $planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])]['op'] = $resource;
                    if (isset($resource['x-ibm-soap']['soap-action']) && !empty($resource['x-ibm-soap']['soap-action'])) {
                      $pos = strpos($resource['x-ibm-soap']['soap-action'], ':');
                      if ($pos !== FALSE) {
                        $parts = explode(':', $resource['x-ibm-soap']['soap-action']);
                        $planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])]['soap-action'] = $parts[1] ?? $resource['x-ibm-soap']['soap-action'];
                      }
                      else {
                        $planArray['nodes'][$apiSafeName]['resources'][$path][mb_strtoupper($resource['operation'])]['soap-action'] = $resource['x-ibm-soap']['soap-action'];
                      }
                    }
                  }
                }
              }
              else {
                //assume all operations enabled
                foreach ($planArray['nodes'][$apiSafeName]['resources'] as $pathName => $path) {
                  foreach ($path as $verb => $op) {
                    if (in_array(mb_strtoupper($verb), [
                      'PUT',
                      'POST',
                      'GET',
                      'DELETE',
                      'OPTIONS',
                      'HEAD',
                      'PATCH',
                    ])) {
                      if (!isset($planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)])) {
                        $planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)] = [];
                      }
                      $planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)]['enabled'] = TRUE;
                      $planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)]['rateLimit'] = $parsedRateLimit['planRateLimit'];
                      if ($tooltip !== NULL) {
                        $planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)]['rateData'] = json_encode($tooltip, JSON_UNESCAPED_UNICODE);
                      }
                      $planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)]['op'] = $op;
                      if (isset($op['soap-action']) && !empty($op['soap-action'])) {
                        $pos = mb_strpos($op['soap-action'], ':');
                        if ($pos !== FALSE) {
                          $parts = explode(':', $op['soap-action']);
                          $planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)]['soap-action'] = $parts[1] ?? $op['soap-action'];
                        }
                        else {
                          $planArray['nodes'][$apiSafeName]['resources'][$pathName][mb_strtoupper($verb)]['soap-action'] = $op['soap-action'];
                        }
                      }
                    }
                  }
                }
              }
            }
          } // end for
        }
        else {
          // no $plan['apis'] means all enabled
          $planArray['nodes'][$apiSafeNodeRef]['enabled'] = TRUE;
          foreach ($planArray['nodes'][$apiSafeNodeRef]['resources'] as $pathName => $path) {
            foreach ($path as $verb => $op) {
              if (isset($planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)])) {
                $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)] = [];
              }
              $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['enabled'] = TRUE;
              $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['rateLimit'] = $parsedRateLimit['planRateLimit'];
              if ($tooltip !== NULL) {
                $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['rateData'] = json_encode($tooltip, JSON_UNESCAPED_UNICODE);
              }
              $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['op'] = $op;
              if (isset($op['soap-action']) && !empty($op['soap-action'])) {
                $pos = mb_strpos($op['soap-action'], ':');
                if ($pos !== FALSE) {
                  $parts = explode(':', $op['soap-action']);
                  $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['soap-action'] = $parts[1] ?? $op['soap-action'];
                }
                else {
                  $planArray['nodes'][$apiSafeNodeRef]['resources'][$pathName][mb_strtoupper($verb)]['soap-action'] = $op['soap-action'];
                }
              }
            }
          }
        }
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $planArray;
  }

  /**
   * @param $plan
   *
   * @return array
   */
  public function parseRateLimits($plan): array {
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
          foreach ($plan['rate-limits'] as $rateName => $rateLimit) {
            $tooltip['#rates'][] = $this->parseRateLimit($rateLimit['value']);
          }
        }
        elseif ((!isset($plan['rate-limits']) || empty($plan['rate-limits'])) && $plan['rate-limit'] !== NULL) {
          // handle having burst-limits but rate-limit (mix of v5 and v4 schemas)
          $tooltip['#rates'][] = $this->parseRateLimit($plan['rate-limit']['value']);
        }
        if (array_key_exists('burst-limits', $plan) && !empty($plan['burst-limits'])) {
          foreach ($plan['burst-limits'] as $rateName => $rateLimit) {
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
          $planRateLimit = $this->parseRateLimit(array_shift($plan['rate-limits'])['value']);
        }
      }
      else {
        $planRateLimit = $this->parseRateLimit(array_shift($plan['burst-limits'])['value']);
      }
    }
    elseif (isset($plan['rate-limit']) && $plan['rate-limit']['value'] !== NULL) {
      $planRateLimit = $this->parseRateLimit($plan['rate-limit']['value']);
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
          case 'second' :
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
   * @return string
   */
  public function parseBilling($billing = []): string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    $text = $this->translationManager->translate('Free');

    if (isset($billing['billing'], $billing['currency'])) {
      $lang_code = $this->languageManager->getCurrentLanguage()->getId();
      $numberFormatRepository = new NumberFormatRepository();
      $numberFormatter = new NumberFormatter($numberFormatRepository,);
      $currencyRepository = new CurrencyRepository;
      $currencyFormatter = new CurrencyFormatter($numberFormatRepository, $currencyRepository);
      $price = $currencyFormatter->format($billing['price'], $billing['currency']);
      // special case to avoid displaying $0.00
      if ((int) $billing['price'] === 0) {
        $text = $this->translationManager->translate('Free');
      }
      else {
        $text = $this->translationManager->formatPlural($billing['period'], '@price per @period-unit', '@price per @period @period-unit', [
          '@price' => $price,
          '@period' => $billing['period'],
          '@period-unit' => $this->timePeriodLookup($billing['period'], $billing['period-unit']),
        ]);
      }
      if (isset($billing['trial-period'], $billing['trial-period-unit'])) {
        // special case to avoid displaying (0 days trial period)
        if ((int) $billing['trial-period'] === 0) {
          $text .= $this->translationManager->translate(' (No trial period)');
        }
        else {
          $text .= ' ' . $this->translationManager
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
    return $text;
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