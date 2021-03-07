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

namespace Drupal\Tests\product\Unit;

use Drupal\product\Service\ProductPlan;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\product\Service\ProductPlan
 *
 * @group product
 */
class ProductPlanServiceTest extends UnitTestCase {

  private $prophet;

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

  protected function setup() {
    $this->prophet = new Prophet();
    $this->languageManager = $this->prophet->prophesize(\Drupal\Core\Language\LanguageManagerInterface::class);
    $this->translationManager = $this->prophet->prophesize(\Drupal\Core\StringTranslation\TranslationManager::class);
    $this->utils = $this->prophet->prophesize(\Drupal\ibm_apim\Service\Utils::class);
    // need to mock up the translation functions
    // simple string translation
    $this->translationManager->translate(Argument::type('string'))->will(function ($args) {
      return $args[0];
    });
    // string translation with replacements
    $this->translationManager->translate(Argument::type('string'), Argument::type('array'))->will(function ($args) {
      if (is_array($args[1]) && !empty(is_array($args[1]))) {
        // do manual replace
        return strtr($args[0], $args[1]);
      }
      else {
        return $args[0];
      }
    });
    // formatPlural translation
    $this->translationManager->formatPlural(Argument::any(), Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
      if ((int) $args[0] === 1) {
        $string = $args[1];
        if (is_array($args[3]) && !empty(is_array($args[3]))) {
          // do manual replace
          return strtr($string, $args[3]);
        }
        return $string;
      }
      else {
        $string = $args[2];
        if (is_array($args[3]) && !empty(is_array($args[3]))) {
          // do manual replace
          return strtr($string, $args[3]);
        }
        return $string;
      }

    });
    // simple number format
    $this->utils->format_number_locale(Argument::any(), Argument::any())->will(function ($args) {
      return number_format($args[0]);
    });
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  public function testIndividualUnlimitedRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('unlimited');
    $this->assertEquals('unlimited', $result);
  }

  public function testIndividualDayRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('10/2day');
    $this->assertEquals('10 calls per 2 days', $result);
  }

  public function testIndividualDayRateLimit2(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('1000/day');
    $this->assertEquals('1,000 calls per day', $result);
  }

  public function testIndividualHourRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('1000000/hour');
    $this->assertEquals('1,000,000 calls per hour', $result);
  }

  public function testIndividualMinuteRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('10000/minute');
    $this->assertEquals('10,000 calls per minute', $result);
  }

  public function testIndividualSecondRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('2/second');
    $this->assertEquals('2 calls per second', $result);
  }

  public function testIndividualWeekRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('2/week');
    $this->assertEquals('2 calls per week', $result);
  }

  public function testSingleRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['rate-limits' => ['default' => ['value' => '10/hour']]]);
    $this->assertEquals(['planRateLimit' => '10 calls per hour', 'tooltip' => NULL], $result);
  }

  public function testMultipleRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['rate-limits' => ['default' => ['value' => '10/hour'], 'gold' => ['value' => '100/hour']]]);

    $this->assertEquals([
      'planRateLimit' => '2 rate limits *',
      'tooltip' => [
        '#rates' => [0 => '10 calls per hour', 1 => '100 calls per hour'],
        '#bursts' => [],
        '#rateLabel' => 'Rate limits',
        '#burstLabel' => 'Burst limits',
      ],
    ], $result);
  }

  public function testBurstRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits([
      'rate-limits' => ['default' => ['value' => '10/hour']],
      'burst-limits' => ['default' => ['value' => '2/minute']],
    ]);
    $this->assertEquals([
      'planRateLimit' => '2 rate limits *',
      'tooltip' => [
        '#rates' => [0 => '10 calls per hour'],
        '#bursts' => [0 => '2 calls per minute'],
        '#rateLabel' => 'Rate limits',
        '#burstLabel' => 'Burst limits',
      ],
    ], $result);
  }

  public function testOldRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['rate-limit' => ['value' => '10/hour']]);
    $this->assertEquals([
      'planRateLimit' => '10 calls per hour',
      'tooltip' => NULL,
    ], $result);
  }

  public function testOldWithBurstRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['rate-limit' => ['value' => '10/hour'], 'burst-limits' => ['default' => ['value' => '2/minute']]]);
    $this->assertEquals([
      'planRateLimit' => '2 rate limits *',
      'tooltip' => [
        '#rates' => [0 => '10 calls per hour'],
        '#bursts' => [0 => '2 calls per minute'],
        '#rateLabel' => 'Rate limits',
        '#burstLabel' => 'Burst limits',
      ],
    ], $result);
  }

  public function testNoRateLimitsWithBurstLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['burst-limits' => ['default' => ['value' => '2/minute']]]);
    $this->assertEquals([
      'planRateLimit' => '2 rate limits *',
      'tooltip' => [
        '#rates' => [0 => 'unlimited'],
        '#bursts' => [0 => '2 calls per minute'],
        '#rateLabel' => 'Rate limits',
        '#burstLabel' => 'Burst limits',
      ],
    ], $result);
  }
}
