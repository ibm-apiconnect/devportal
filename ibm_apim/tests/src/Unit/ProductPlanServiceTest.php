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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\Service\ProductPlan;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Prophecy\Argument;
use Drupal\Tests\ibm_apim\Unit\mocks\MockApiNodeBuilder;

class languageIDMock {

  public function getID(): string {
    return 'en_US';
  }

}

/**
 * @group product
 */
class ProductPlanServiceTest extends UnitTestCase {

  private Prophet $prophet;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  protected $planService;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected $utils;

  /**
   * @var \Drupal\apic_api\Service\ApiTaxonomy|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $taxonomy;

  protected $productapis = [
    'events' => [
      'name' => 'basic-events100'
    ],
    'petstore' => [
      'name' => 'swagger-petstore100'
    ]
  ];

  protected function setup(): void

  {
    $this->prophet = new Prophet();
    $this->languageManager = $this->prophet->prophesize(\Drupal\Core\Language\LanguageManagerInterface::class);
    $this->translationManager = $this->prophet->prophesize(\Drupal\Core\StringTranslation\TranslationManager::class);
    $this->utils = $this->prophet->prophesize(\Drupal\ibm_apim\Service\Utils::class);
    $this->taxonomy = $this->prophet->prophesize(\Drupal\ibm_apim\Service\ApicTaxonomy::class);
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

    $languageIDMock = new languageIDMock();
    $this->languageManager->getCurrentLanguage()->willReturn($languageIDMock);

    $this->planService = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  public function testIndividualUnlimitedRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('unlimited');
    self::assertEquals('unlimited', $result);
  }

  public function testIndividualDayRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('10/2day');
    self::assertEquals('10 calls per 2 days', $result);
  }

  public function testIndividualDayRateLimit2(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('1000/day');
    self::assertEquals('1,000 calls per day', $result);
  }

  public function testIndividualHourRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('1000000/hour');
    self::assertEquals('1,000,000 calls per hour', $result);
  }

  public function testIndividualMinuteRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('10000/minute');
    self::assertEquals('10,000 calls per minute', $result);
  }

  public function testIndividualSecondRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('2/second');
    self::assertEquals('2 calls per second', $result);
  }

  public function testIndividualWeekRateLimit(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimit('2/week');
    self::assertEquals('2 calls per week', $result);
  }

  public function testSingleRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['rate-limits' => ['default' => ['value' => '10/hour']]]);
    self::assertEquals(['planRateLimit' => '10 calls per hour', 'tooltip' => NULL], $result);
  }

  public function testMultipleRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['rate-limits' => ['default' => ['value' => '10/hour'], 'gold' => ['value' => '100/hour']]]);

    self::assertEquals([
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
    self::assertEquals([
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
    self::assertEquals([
      'planRateLimit' => '10 calls per hour',
      'tooltip' => NULL,
    ], $result);
  }

  public function testOldWithBurstRateLimits(): void {
    $service = new ProductPlan($this->languageManager->reveal(), $this->translationManager->reveal(), $this->utils->reveal());
    $result = $service->parseRateLimits(['rate-limit' => ['value' => '10/hour'], 'burst-limits' => ['default' => ['value' => '2/minute']]]);
    self::assertEquals([
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
    self::assertEquals([
      'planRateLimit' => '2 rate limits *',
      'tooltip' => [
        '#rates' => [0 => 'unlimited'],
        '#bursts' => [0 => '2 calls per minute'],
        '#rateLabel' => 'Rate limits',
        '#burstLabel' => 'Burst limits',
      ],
    ], $result);
  }

  // Test process where plan has same apis as product, the apis are open api, and there is a single rate limit

  /**
   * @throws \JsonException
   */
  public function testProcessOpenApiAllProductApisSingleRateLimit(): void {
    $planId = 1;
    $plan = [
      'rate-limits' => [
        'default' => [
          'value' => '100/1hour',
        ],
      ],
    ];

    $petstore = file_get_contents(__DIR__ . "/resources/petstoreSlim.json");
    $decodedPetstore = json_decode($petstore, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedPetstore)->build();
    $apiNodes = [
      [
        'node' => $node,
      ],
    ];

    // we don't care what the rate limit function does but can't mock it at the moment so just pull the value out
    $expectedRateLimit = $this->planService->parseRateLimits($plan);
    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    self::assertEquals([
      'nid' => 1,
      'title' => 'Swagger Petstore',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value'=>'swagger-petstore'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        '/pets' => [
          'GET' => [
            // rate limit should be the same as the plan rate limit
            'rateLimit' => $expectedRateLimit['planRateLimit'],
            'op' => $decodedPetstore['paths']['/pets']['get'],
          ],
        ],
      ],
    ], $result['nodes']['swagger-petstore100']);
  }

  // Test process where plan has same apis as product, the apis are open api, and there are multiple rate limits

  /**
   * @throws \JsonException
   */
  public function testProcessOpenApiAllProductApisMultiRateLimit(): void {
    $planId = 1;
    $plan = [
      'rate-limits' => [
        'Bronze' => [
          'value' => '100/1hour',
        ],
        'Silver' => [
          'value' => '1000/1hour',
        ],
      ],
    ];

    $petstore = file_get_contents(__DIR__ . "/resources/petstoreSlim.json");
    $decodedPetstore = json_decode($petstore, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedPetstore)->build();

    $apiNodes = [
      [
        'node' => $node,
      ]
    ];

    // we don't care what the rate limit function does but can't mock it at the moment so just pull the value out
    $expectedRateLimit = $this->planService->parseRateLimits($plan);
    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    self::assertEquals([
      'nid' => 1,
      'title' => 'Swagger Petstore',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value'=>'swagger-petstore'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        '/pets' => [
          'GET' => [
            // rate limit should be the same as the plan rate limit
            'rateLimit' => $expectedRateLimit['planRateLimit'],
            'rateData' => json_encode($expectedRateLimit['tooltip'], JSON_THROW_ON_ERROR),
            'op' => $decodedPetstore['paths']['/pets']['get'],
          ],
        ],
      ],
    ], $result['nodes']['swagger-petstore100']);
  }

  // Test process where plan has same apis as product, the apis are async api, and there is a single rate limit

  /**
   * @throws \JsonException
   */
  public function testProcessAsyncApiAllProductApisSingleRateLimit(): void {
    $planId = 1;
    $plan = [
      'rate-limits' => [
        'default' => [
          'value' => '100/1hour',
        ],
      ],
    ];

    $asyncapi = file_get_contents(__DIR__ . "/resources/basic_asyncapi.json");
    $decodedApi = json_decode($asyncapi, TRUE, 512, JSON_THROW_ON_ERROR);
    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedApi)->build();
    $apiNodes = [
      [
        'node' => $node,
      ],
    ];

    // we don't care what the rate limit function does but can't mock it at the moment so just pull the value out
    $expectedRateLimit = $this->planService->parseRateLimits($plan);
    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    self::assertEquals([
      'nid' => 1,
      'title' => 'Basic events',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value' => 'basic-events'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        'channelA' => [
          'subscribe' => [
            'rateLimit' => $expectedRateLimit['planRateLimit'],
          ],
        ],
      ],
    ], $result['nodes']['basic-events100']);
  }

  // Test process where plan has same apis as product, the apis are async api, and there are multiple rate limits

  /**
   * @throws \JsonException
   */
  public function testProcessAsyncApiAllProductApisMultiRateLimit(): void {
    $planId = 1;
    $plan = [
      'rate-limits' => [
        'Bronze' => [
          'value' => '100/1hour',
        ],
        'Silver' => [
          'value' => '1000/1hour',
        ],
      ],
    ];

    $asyncapi = file_get_contents(__DIR__ . "/resources/basic_asyncapi.json");
    $decodedApi = json_decode($asyncapi, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedApi)->build();
    $apiNodes = [
      [
        'node' => $node,
      ],
    ];

    // we don't care what the rate limit function does but can't mock it at the moment so just pull the value out
    $expectedRateLimit = $this->planService->parseRateLimits($plan);
    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    self::assertEquals([
      'nid' => 1,
      'title' => 'Basic events',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value' => 'basic-events'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        'channelA' => [
          'subscribe' => [
            'rateLimit' => $expectedRateLimit['planRateLimit'],
            'rateData' => json_encode($expectedRateLimit['tooltip'], JSON_THROW_ON_ERROR),
          ],
        ],
      ],
    ], $result['nodes']['basic-events100']);
  }

  /**
   * @throws \JsonException
   */
  public function testProcessOpenApiPlanApisNoDefinedRateLimit(): void {
    $planId = 1;
    $plan = [
      'apis' => [
        'petstore' => [],
      ],
      'rate-limits' => [
        'default' => [
          'value' => '100/1hour',
        ],
      ],
    ];

    $petstore = file_get_contents(__DIR__ . "/resources/petstoreSlim.json");
    $decodedPetstore = json_decode($petstore, TRUE, 512, JSON_THROW_ON_ERROR);

    $asyncapi = file_get_contents(__DIR__ . "/resources/basic_asyncapi.json");
    $decodedAsyncApi = json_decode($asyncapi, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedPetstore)->build();
    $anotherNode = new MockApiNodeBuilder($this);
    $anotherNode = $anotherNode->setDocument($decodedAsyncApi)->build();

    $apiNodes = [
      [
        'node' => $node,
      ],
      // load in another api to prove it doesn't show up
      [
        'node' => $anotherNode,
      ],
    ];

    // we don't care what the rate limit function does but can't mock it at the moment so just pull the value out
    $expectedRateLimit = $this->planService->parseRateLimits($plan);
    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    // verify that only one node was copied
    self::assertEquals(1, sizeof($result['nodes']));
    self::assertEquals([
      'nid' => 1,
      'title' => 'Swagger Petstore',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value'=>'swagger-petstore'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        '/pets' => [
          'GET' => [
            // rate limit should be the same as the plan rate limit
            'rateLimit' => $expectedRateLimit['planRateLimit'],
            'op' => $decodedPetstore['paths']['/pets']['get'],
          ],
        ],
      ],
    ], $result['nodes']['swagger-petstore100']);
  }

  /**
   * @throws \JsonException
   */
  public function testProcessAsyncApiPlanApisNoDefinedRateLimit(): void {
    $planId = 1;
    $plan = [
      'apis' => [
        'events' => [],
      ],
      'rate-limits' => [
        'default' => [
          'value' => '100/1hour',
        ],
      ],
    ];

    $petstore = file_get_contents(__DIR__ . "/resources/petstoreSlim.json");
    $decodedPetstore = json_decode($petstore, TRUE, 512, JSON_THROW_ON_ERROR);

    $asyncapi = file_get_contents(__DIR__ . "/resources/basic_asyncapi.json");
    $decodedAsyncApi = json_decode($asyncapi, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedPetstore)->build();
    $anotherNode = new MockApiNodeBuilder($this);
    $anotherNode = $anotherNode->setDocument($decodedAsyncApi)->build();

    $apiNodes = [
      [
        'node' => $node,
      ],
      // load in another api to prove it doesn't show up
      [
        'node' => $anotherNode,
      ],
    ];

    // we don't care what the rate limit function does but can't mock it at the moment so just pull the value out
    $expectedRateLimit = $this->planService->parseRateLimits($plan);
    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    // verify that only one node was copied
    self::assertEquals(1, sizeof($result['nodes']));
    self::assertEquals([
      'nid' => 1,
      'title' => 'Basic events',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value'=>'basic-events'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        'channelA' => [
          'subscribe' => [
            'rateLimit' => $expectedRateLimit['planRateLimit'],
          ],
        ],
      ],
    ], $result['nodes']['basic-events100']);
  }

  /**
   * @throws \JsonException
   */
  public function testProcessOpenApiPlanApisCustomOperations(): void {
    $planId = 1;
    $plan = [
      'apis' => [
        'petstore' => [
          'operations' => [
            [
              'path' => '/pets',
              'operation' => 'get',
              'rate-limits' => [
                'default' => [
                  'value' => '20/1hour',
                ],
              ],
            ],
          ],
        ],
      ],
      'rate-limits' => [
        'default' => [
          'value' => '100/1hour',
        ],
      ],
    ];


    // get larger document to verify operations are filtered out
    $petstore = file_get_contents(__DIR__ . "/resources/petstoreFull.json");
    $decodedPetstore = json_decode($petstore, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedPetstore)->build();

    $apiNodes = [
      [
        'node' => $node,
      ]
    ];

    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    self::assertEquals([
      'nid' => 1,
      'title' => 'Swagger Petstore',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value'=>'swagger-petstore'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        '/pets' => [
          'GET' => [
            'rateLimit' => '20 calls per hour',
            'op' => $plan['apis']['petstore']['operations'][0],
          ],
        ],
      ],
    ], $result['nodes']['swagger-petstore100']);
  }

  /**
   * @throws \JsonException
   */
  public function testProcessOpenApiPlanApisCustomOperationsMultiRateLimit(): void {
    $planId = 1;
    $plan = [
      'apis' => [
        'petstore' => [
          'operations' => [
            [
              'path' => '/pets',
              'operation' => 'get',
              'rate-limits' => [
                'Bronze' => [
                  'value' => '20/1hour',
                ],
                'Silver' => [
                  'value' => '50/1hour',
                ],
              ],
            ],
          ],
        ],
      ],
      'rate-limits' => [
        'default' => [
          'value' => '100/1hour',
        ],
      ],
    ];

    // get larger document to verify operations are filtered out
    $petstore = file_get_contents(__DIR__ . "/resources/petstoreSlim.json");
    $decodedPetstore = json_decode($petstore, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedPetstore)->build();

    $apiNodes = [
      [
        'node' => $node,
      ]
    ];

    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    self::assertEquals([
      'nid' => 1,
      'title' => 'Swagger Petstore',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value'=>'swagger-petstore'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        '/pets' => [
          'GET' => [
            'rateLimit' => '2 rate limits *',
            'rateData' => '{"#rates":["20 calls per hour","50 calls per hour"],"#rateLabel":"Rate limits","#burstLabel":"Burst limits"}',
            'op' => $plan['apis']['petstore']['operations'][0],
          ],
        ],
      ],
    ], $result['nodes']['swagger-petstore100']);
  }

  /**
   * @throws \JsonException
   */
  public function testProcessOpenApiPlanApisCustomOperationsWithMultiPlanRateLimits(): void {
    $planId = 1;
    $plan = [
      'apis' => [
        'petstore' => [
          'operations' => [
            [
              'path' => '/pets',
              'operation' => 'get',
              'rate-limits' => [
                'default' => [
                  'value' => '20/1hour',
                ],
              ],
            ],
            [
              'path' => '/pets',
              'operation' => 'post',
            ],
          ],
        ],
      ],
      'rate-limits' => [
        'Bronze' => [
          'value' => '100/1hour',
        ],
        'Silver' => [
          'value' => '500/1hour',
        ],
      ],
    ];

    // get larger document to verify operations are filtered out
    $petstore = file_get_contents(__DIR__ . "/resources/petstoreFull.json");
    $decodedPetstore = json_decode($petstore, TRUE, 512, JSON_THROW_ON_ERROR);

    $node = new MockApiNodeBuilder($this);
    $node = $node->setDocument($decodedPetstore)->build();

    $apiNodes = [
      [
        'node' => $node,
      ]
    ];

    $result = $this->planService->process($planId, $plan, $apiNodes, $this->productapis);

    self::assertEquals([
      'nid' => 1,
      'title' => 'Swagger Petstore',
      'protocol' => 'protocol',
      'url' => ['value'=>'mock url'],
      'id' => ['value'=>'swagger-petstore'],
      'version' => ['value'=>'1.0.0'],
      'resources' => [
        '/pets' => [
          'GET' => [
            'rateLimit' => '20 calls per hour',
            'op' => $plan['apis']['petstore']['operations'][0],
          ],
          'POST' => [
            'rateLimit' => '2 rate limits *',
            'rateData' => '{"#rates":["100 calls per hour","500 calls per hour"],"#rateLabel":"Rate limits","#burstLabel":"Burst limits"}',
            'op' => $plan['apis']['petstore']['operations'][1],
          ],
        ],
      ],
    ], $result['nodes']['swagger-petstore100']);
  }

  /**
   * @throws \JsonException
   */
  public function testProcessTranslatePlanNameAndDescription(): void {
    $planId = 1;
    $plan = [
      'x-ibm-languages' => [
        'title' => [
          'en_US' => 'some translated title',
        ],
        'description' => [
          'en_US' => 'some translated description',
        ],
      ],
    ];

    $result = $this->planService->process($planId, $plan, [], $this->productapis);

    self::assertEquals('some translated title', $result['data']['title']);
    self::assertEquals('some translated description', $result['data']['description']);
  }

}
