<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\product\Controller;

use Drupal\apic_api\Api;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\product\Product;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Drupal\ibm_apim\External\SerializationUtility; 
/**
 * Controller routines for product routes.
 */
class ProductController extends ControllerBase {

  /**
   * Request stack.
   *
   * @var RequestStack
   */
  public RequestStack $request;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ProductController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(RequestStack $request, Messenger $messenger) {
    $this->request = $request;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ProductController {
    // Instantiates this form class.
    return new static(// Load the service required to construct this class.
      $container->get('request_stack'), $container->get('messenger'));
  }

  /**
   * This method simply redirects to the node/x page, with the node having been loaded via a ParamConverter
   *
   * @param \Drupal\node\NodeInterface $prodNode
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   */

  function productView(NodeInterface $prodNode) {
    if ($prodNode !== NULL && $prodNode->bundle() === 'product') {
      $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $returnValue = $viewBuilder->view($prodNode, 'full');
      
    }
    else {
      \Drupal::logger('product')->error('productView: not a valid product.', []);
      $this->messenger->addWarning(t('The specified arguments were not correct.'));
      $url = Url::fromRoute('<front>')->toString();
      $returnValue = new RedirectResponse($url);
    }
    return $returnValue;
  }

  /**
   * @param \Drupal\node\NodeInterface $prodNode
   *
   * @return string
   */
  public function productTitle(NodeInterface $prodNode): string {
    if ($prodNode !== NULL && $prodNode->bundle() === 'product') {
      $returnValue = $prodNode->getTitle();
    }
    else {
      \Drupal::logger('product')->error('productView: not a valid product.', []);
      $this->messenger->addWarning(t('The specified arguments were not correct.'));
      $returnValue = 'ERROR';
    }
    return $returnValue;
  }

  /**
   * This method loads the product telling it which API to embed
   *
   * @param \Drupal\node\NodeInterface $prodNode
   * @param \Drupal\node\NodeInterface $apiNode
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function productApi(NodeInterface $prodNode, NodeInterface $apiNode) {
    if ($prodNode !== NULL && $apiNode !== NULL && $prodNode->id() && $apiNode->id()) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [
        'prodNode' => $prodNode->id(),
        'apiNode' => $apiNode->id(),
      ]);
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $found = FALSE;
    $config = \Drupal::config('ibm_apim.settings');
    $ibmApimShowVersions = (boolean) $config->get('show_versions');
    if ($ibmApimShowVersions === NULL) {
      $ibmApimShowVersions = TRUE;
    }
    $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
    if ($ibmApimShowPlaceholderImages === NULL) {
      $ibmApimShowPlaceholderImages = TRUE;
    }
    $api = '';
    $product = '';

    if ($prodNode !== NULL && $prodNode->bundle() === 'product') {
      $haveAccess = Product::checkAccess($prodNode);
      if (!$haveAccess) {
        throw new AccessDeniedHttpException();
      }

      if ($apiNode !== NULL && $apiNode->bundle() === 'api') {
        // check this product actually includes the specified API
        $prodRefs = [];
        foreach ($prodNode->product_apis->getValue() as $arrayValue) {
          $prodRefs[] = SerializationUtility::decodeData($arrayValue['value'], TRUE, FALSE);
        }
        foreach ($prodRefs as $prodRef) {
          if ($prodRef['name'] === $apiNode->apic_ref->value) {
            $found = TRUE;
            $product = [
              'nid' => $prodNode->id(),
              'id' => $prodNode->product_id->value,
              'title' => $prodNode->getTitle(),
              'version' => $prodNode->apic_version->value,
              'pathalias' => $prodNode->apic_pathalias->value,
              'subscribable' => product_check_product_subscribe($prodNode)
            ];
          }
        }

        $fid = $apiNode->apic_image->getValue();
        $apiImageUrl = NULL;
        if (isset($fid[0]['target_id'])) {
          $file = File::load($fid[0]['target_id']);
          if (isset($file)) {
            $apiImageUrl = $file->createFileUrl();
          }
        }
        elseif ($ibmApimShowPlaceholderImages === TRUE) {
          $rawImage = Api::getRandomImageName($apiNode->getTitle());
          $apiImageUrl = base_path() . \Drupal::service('extension.list.module')->getPath('apic_api') . '/images/' . $rawImage;
        }
        $enforced = TRUE;
        $collection_id = '';
        $workspace_id = '';
        if (isset($apiNode->api_swagger->value)) {
          $swagger = SerializationUtility::decodeData($apiNode->api_swagger->value, TRUE, FALSE);
          if (!isset($swagger['x-ibm-configuration']) || !is_array($swagger['x-ibm-configuration']) || empty($swagger['x-ibm-configuration']) || $swagger['x-ibm-configuration']['enforced'] === FALSE) {
            $enforced = FALSE;
          }
          if(isset($swagger['info']['x-postman-collection-id']) && isset($swagger['info']['x-postman-workspace-id'])){
              $collection_id=$swagger['info']['x-postman-collection-id'];
              $workspace_id=$swagger['info']['x-postman-workspace-id'];
          }
        }
         
        $api = [
          'nid' => $apiNode->id(),
          'id' => $apiNode->api_id->value,
          'title' => $apiNode->getTitle(),
          'version' => $apiNode->apic_version->value,
          'pathalias' => $apiNode->apic_pathalias->value,
          'image_url' => $apiImageUrl,
          'enforced' => $enforced,
          'state' => $apiNode->api_state->value,
          'collection_id' => $collection_id,
          'workspace_id'  => $workspace_id
        ];

        if (!$found) {
          \Drupal::logger('product')->error('productApi: product does not contain the specified API.', []);
          $this->messenger->addWarning(t('The specified arguments were not correct.'));
        }
      }
      else {
        \Drupal::logger('product')->error('productApi: not a valid API.', []);
        $this->messenger->addWarning(t('The specified arguments were not correct.'));
      }
    }
    else {
      \Drupal::logger('product')->error('productApi: not a valid product.', []);
      $this->messenger->addWarning(t('The specified arguments were not correct.'));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $found);
    if ($found) {
      if (\Drupal::currentUser()->isAnonymous() && !((bool) \Drupal::config('ibm_apim.settings')->get('show_anonymous_apis'))) {
        $url =  Url::fromRoute('system.401', ['redirectto' => Url::fromRoute('<current>')->getInternalPath()])->toString();
        return new RedirectResponse($url);
      }
      $attached = ['library' => []];
      $attached['library'][] = 'product/basic';
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('votingapi_widgets')) {
        $attached['library'][] = 'votingapi_widgets/fivestar';
      }

      $siteConfig = \Drupal::service('ibm_apim.site_config');
      $postmanEnabled = $siteConfig->isPostmanEnabled();

      return [
        '#theme' => 'product_wrapper',
        '#api' => $api,
        '#product' => $product,
        '#attached' => $attached,
        '#showPlaceholders' => $ibmApimShowPlaceholderImages,
        '#showVersions' => $ibmApimShowVersions,
        '#postmanEnabled' => $postmanEnabled,
      ];
    }
    else {
      \Drupal::logger('product')
        ->error('productApi: api %api is not in product %product.', [
          '%api' => $apiNode->id(),
          '%product' => $prodNode->id(),
        ]);
      $this->messenger->addWarning(t('The specified arguments were not correct.'));
      $url = Url::fromRoute('<front>')->toString();
      return new RedirectResponse($url);
    }
  }

  /**
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   *
   * @return string
   */
  public function productApiTitle(?NodeInterface $apiNode = NULL): string {
    if ($apiNode !== NULL && $apiNode->bundle() === 'api') {
      $returnValue = $apiNode->getTitle();
    }
    else {
      \Drupal::logger('product')->error('productApi: not a valid api.', []);
      $this->messenger->addWarning(t('The specified arguments were not correct.'));
      $returnValue = 'ERROR';
    }
    return $returnValue;
  }

  /**
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   *
   * @return NULL|array|RedirectResponse
   */
  public function select(?NodeInterface $apiNode = NULL) {
    $products = [];

    if ($apiNode !== NULL && $apiNode->id()) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [
        'apiNode' => $apiNode->id(),
      ]);
      $apiNid = $apiNode->id();

      $productNids = Product::getProductsContainingAPI($apiNid);
      if (isset($productNids) && !empty($productNids)) {
        // if only one product found then just load it and redirect
        if (count($productNids) === 1) {
          $firstNid = reset($productNids);
          $firstNode = Node::load($firstNid);
          $path = Url::fromRoute('product.api', ['prodNode' => $firstNode->id(), 'apiNode' => $apiNode->id()])
            ->setAbsolute()
            ->toString();
          $response = new RedirectResponse($path);
          \Drupal::service('http_middleware.ibm_apim_redirect')->setRedirectResponse($response);
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
          return $response;
        }

        foreach (array_chunk($productNids, 50) as $chunk) {
          $nodes = Node::loadMultiple($chunk);
          foreach ($nodes as $prodNode) {
            $products[] = [
              'nid' => $prodNode->id(),
              'id' => $prodNode->product_id->value,
              'title' => $prodNode->getTitle(),
              'version' => $prodNode->apic_version->value,
            ];
          }
        }
      } else {
        ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        throw new NotFoundHttpException();
      }
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      $apiNid = NULL;
    }

    $attached = ['library' => []];
    $attached['library'][] = 'product/basic';
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $products);
    return [
      '#theme' => 'product_select',
      '#apiNid' => $apiNid,
      '#products' => $products,
      '#attached' => $attached,
    ];
  }
  protected function checkAccessToProduct(?\Drupal\node\NodeInterface $product): bool {
    if (!$product || $product->bundle() !== 'product') {
      \Drupal::messenger()->addWarning(t('Product not found or invalid.'));
      return false;
    }

    if (!$product->access('view')) {
      \Drupal::messenger()->addWarning(t('You do not have permission to access this product.'));
      return false;
    }

    return true;
  }


  /**
   * Downloads a ZIP file containing all API specifications associated with a given product
   *
   * @param int $product_id
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function downloadSpecs($product_id) {
    // load the product entity
    $product = \Drupal::entityTypeManager()->getStorage('node')->load($product_id);
    if (!$this->checkAccessToProduct($product)) {
      return $this->redirect('<front>');
    }

    // create a streamed response that builds the zip in memory
    $response = new StreamedResponse(function () use ($product) {
      // remove x-ibm fields
      $removeSensitiveKeys = function (array $data) use (&$removeSensitiveKeys) {
        foreach ($data as $key => &$value) {
          if (is_array($value)) {
            $value = $removeSensitiveKeys($value);
          }
          if (is_string($key) && preg_match('/^x-(ibm|key-type|postman)/i', $key)) {
            unset($data[$key]);
          }
        }
        return $data;
      };

      $zip = new \ZipArchive();
      $tmpFile = tempnam(sys_get_temp_dir(), 'specs_');

      if ($zip->open($tmpFile, \ZipArchive::CREATE) === TRUE) {

        $api_ids = array_map(fn($item) => $item['value'], $product->get('product_api_nids')->getValue());

        $apis = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($api_ids);

        foreach ($apis as $api) {
          $api_title = $api->label();
          $api_id = $api->id();
        
          // determine protocol
          $protocol = strtolower($api->get('api_protocol')->value ?? '');

          if ($protocol === 'soap' || $protocol === 'wsdl') {
            if ($api->hasField('apic_attachments') && !$api->get('apic_attachments')->isEmpty()) {
              $attachments = $api->get('apic_attachments')->referencedEntities();
          
              foreach ($attachments as $attachment) {
                if ($attachment instanceof \Drupal\file\FileInterface) {
                  $uri = $attachment->getFileUri();
                  $filename = $attachment->getFilename();
                  $contents = file_get_contents($uri);
          
                    $zip->addFromString($filename, $contents);
                  
                }
              }
            }
          }
          else {
            $encoded = $api->get('api_encodedswagger')->value ?? null;
          
            if ($encoded && in_array($protocol, ['rest', 'graphql'])) {
              $decoded = base64_decode($encoded);
              $spec = json_decode($decoded, true);
          
              if (json_last_error() === JSON_ERROR_NONE) {
                $cleaned = $removeSensitiveKeys($spec);
                $sanitized = json_encode($cleaned, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $filename = str_replace(':', '_', $api->get('apic_ref')->value) . '.json';
                $zip->addFromString($filename, $sanitized);
              }
            }
          }
        }
        
        $zip->close();

        // send zip contents to browser
        readfile($tmpFile);
        unlink($tmpFile);
      }
    });

    $filename = $product->label() . '.zip';

    $response->headers->set('Content-Type', 'application/zip');
    $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="' . $filename . '"');

    return $response;
  }

  /**
   * Get API details for a specific API in a product.
   *
   * @param int $product_id
   *   The product node ID.
   * @param string $api_name
   *   The API reference name.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   HTML response with rendered API details table.
   */
  public function apiDetails($product_id, $api_name) {
    
    $plan_index = $this->request->getCurrentRequest()->query->get('planIndex', '1');

    $product = \Drupal::entityTypeManager()->getStorage('node')->load($product_id);
    
    if (!$product || $product->getType() !== 'product') {
      return new Response('<p class="error">' . $this->t('Product not found') . '</p>', 404, ['Content-Type' => 'text/html']);
    }
    
    if (!$this->checkAccessToProduct($product)) {
      return new Response('<p class="error">' . $this->t('Access denied') . '</p>', 403, ['Content-Type' => 'text/html']);
    }
    
    // Find the API in the product
    $productApis = [];
    foreach ($product->product_apis->getValue() as $arrayValue) {
      $productApis[] = SerializationUtility::decodeData($arrayValue['value'], TRUE, FALSE);
    }

    $targetApi = null;
    foreach ($productApis as $api) {
      if ($api['name'] === $api_name) {
        $targetApi = $api;
        break;
      }
    }
    
    if (!$targetApi) {
      $html = '<p class="api-search-results-message-error">API not found in product</p>';
      $encoded = base64_encode($html);
      return new Response($encoded, 404, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
    }
    
    // Load the API node to get full details
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'api')
      ->condition('status', 1)
      ->condition('apic_ref', $api_name)
      ->accessCheck(TRUE);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $html = '<p class="api-search-results-message-error">API node not found</p>';
      $encoded = base64_encode($html);
      return new Response($encoded, 404, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
    }
    
    $apiNode = Node::load(reset($nids));
    
    if (!$apiNode) {
      $html = '<p class="api-search-results-message-error">Failed to load API node</p>';
      $encoded = base64_encode($html);
      return new Response($encoded, 500, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
    }
    
    $productPlanService = \Drupal::service('ibm_apim.product_plan');
    
    // Only parse plan-level rate limits (parseRateLimits doesn't need the apis array)
    $parsedRateLimit = $productPlanService->parseRateLimits(SerializationUtility::decodeData($product->product_plans->getValue()[$plan_index - 1]['value'], TRUE, FALSE));

    $processedPlan = [
      'rateLimit' => $parsedRateLimit['planRateLimit'],
      'rateData' => isset($parsedRateLimit['tooltip']) ? json_encode($parsedRateLimit['tooltip']) : null,
      'nodes' => [],
    ];
    
    $planRateLimit = $parsedRateLimit['planRateLimit'];
    $planRateData = isset($parsedRateLimit['tooltip']) ? $parsedRateLimit['tooltip'] : NULL;

    // Build the API node data with operations from swagger
    // We use plan-level rate limits for all operations (cannot access per-operation limits without loading all APIs)
    $apiNodeData = [
      'title' => $apiNode->getTitle(),
      'version' => [['value' => $apiNode->apic_version->value]],
      'protocol' => strtoupper($apiNode->api_protocol->value ?? 'REST'),
      'resources' => [],
    ];
    
    // Get all operations from the API's swagger document
    if (isset($apiNode->api_swagger->value)) {
      $swagger = SerializationUtility::decodeData($apiNode->api_swagger->value, TRUE, FALSE);
      
      // Check for AsyncAPI
      if (isset($swagger['asyncapi'])) {
        // Handle AsyncAPI channels
        if (isset($swagger['channels'])) {
          foreach (array_keys($swagger['channels']) as $channelName) {
            foreach (['subscribe'] as $operation) {
              $apiNodeData['resources'][$channelName][$operation] = [
                'rateLimit' => $planRateLimit,
              ];
              if ($planRateData !== NULL) {
                $apiNodeData['resources'][$channelName][$operation]['rateData'] = json_encode($planRateData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
              }
            }
          }
        }
      }
      // Handle REST/GraphQL paths
      elseif (isset($swagger['paths']) && is_array($swagger['paths'])) {
        foreach ($swagger['paths'] as $path => $pathItem) {
          // remove any query param portion of the path
          $path = mb_strstr($path, '?', TRUE) ?: $path;
          
          if (is_array($pathItem)) {
            foreach ($pathItem as $method => $operation) {
              // Only process HTTP methods
              if (in_array(strtolower($method), ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'])) {
                $apiNodeData['resources'][$path][mb_strtoupper($method)] = [
                  'rateLimit' => $planRateLimit,
                  'op' => $operation,
                ];
                
                if ($planRateData !== NULL) {
                  $apiNodeData['resources'][$path][mb_strtoupper($method)]['rateData'] = json_encode($planRateData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                }
                
                // Handle SOAP action
                if (isset($operation['x-ibm-soap']['soap-action']) && !empty($operation['x-ibm-soap']['soap-action'])) {
                  $soapAction = $operation['x-ibm-soap']['soap-action'];
                  $pos = strpos($soapAction, ':');
                  if ($pos !== FALSE) {
                    $parts = explode(':', $soapAction);
                    $apiNodeData['resources'][$path][mb_strtoupper($method)]['soap-action'] = $parts[1] ?? $soapAction;
                  }
                  else {
                    $apiNodeData['resources'][$path][mb_strtoupper($method)]['soap-action'] = $soapAction;
                  }
                }
              }
            }
          }
        }
      }
    }
    
    $processedPlan['nodes'] = [$apiNodeData];
    
    
    // Render template and return as Response
    $twigService = \Drupal::service('twig');
    $template = $twigService->load(\Drupal::service('extension.list.module')->getPath('product') . '/templates/api-details-table.html.twig');
    
    $html = $template->render([
      'plan' => $processedPlan,
    ]);
    
    // Encode response to obfuscate content
    $encoded = base64_encode($html);
    
    return new Response($encoded, 200, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
  }

  /**
   * Search APIs in a product.
   *
   * @param int $product_id
   *   The product node ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   HTML response with search results.
   */
  public function searchApis($product_id) {
    $search_term = $this->request->getCurrentRequest()->query->get('title', '');
    $search_term = strtolower($search_term);

    // Load template first so it's available for all return paths
    $twigService = \Drupal::service('twig');
    $template = $twigService->load(\Drupal::service('extension.list.module')->getPath('product') . '/templates/api-search-results.html.twig');

    if ($search_term === '') {
      $html = $template->render([
        'results' => [],
        'result_message' => $this->t('Please enter search term.'),
      ]);
      $encoded = base64_encode($html);
      return new Response($encoded, 200, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
    }

    $product = \Drupal::entityTypeManager()->getStorage('node')->load($product_id);
    
    if (!$product || $product->getType() !== 'product') {
      $html = '<p class="error">' . $this->t('Product not found') . '</p>';
      $encoded = base64_encode($html);
      return new Response($encoded, 404, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
    }
    
    if (!$this->checkAccessToProduct($product)) {
      $html = '<p class="error">' . $this->t('Access denied') . '</p>';
      $encoded = base64_encode($html);
      return new Response($encoded, 403, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
    }
    
    $results = [];
    $productApis = [];
    
    // Get all APIs from the product
    foreach ($product->product_apis->getValue() as $arrayValue) {
      $productApis[] = SerializationUtility::decodeData($arrayValue['value'], TRUE, FALSE);
    }
    
    // Extract API names for database query
    $apiNames = array_map(fn($api) => $api['name'], $productApis);
    
    // Query database for API titles efficiently using apic_ref field
    // This avoids loading full nodes and can handle 6K+ APIs
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->join('node__apic_ref', 'ref', 'n.nid = ref.entity_id');
    $query->fields('ref', ['apic_ref_value']);
    $query->condition('n.type', 'api');
    $query->condition('n.status', 1);
    $query->condition('ref.apic_ref_value', $apiNames, 'IN');
    
    $apiTitles = [];
    $apiResults = $query->execute()->fetchAll();
    
    // Create a map of API name => title
    foreach ($apiResults as $row) {
      $apiTitles[$row->apic_ref_value] = $row->title;
    }

    // Search through API titles and names
    foreach ($productApis as $productApi) {
      $apiName = $productApi['name'];
      // Get title from database query result, fallback to stored title or name
      $apiTitle = $apiTitles[$apiName] ?? $productApi['title'] ?? $apiName;
      $apiTitleLower = strtolower($apiTitle);
      
      if (!empty($search_term) && str_contains($apiTitleLower, $search_term)) {
        $results[] = [
          'name' => $apiName,
          'title' => $apiTitle,
        ];
      }
    }
    
    // Check if no results were found
    if (empty($results)) {
      $html = $template->render([
        'results' => [],
        'result_message' => $this->t('No APIs found matching your search.'),
      ]);
      $encoded = base64_encode($html);
      return new Response($encoded, 200, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
    }
    
    // Render template and return as Response
    $html = $template->render([
      'results' => $results,
      'result_message' => '',
    ]);
    
    // Encode response to obfuscate content
    $encoded = base64_encode($html);
    
    return new Response($encoded, 200, ['Content-Type' => 'text/plain', 'X-Content-Encoding' => 'base64']);
  }
}