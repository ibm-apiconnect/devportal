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

namespace Drupal\apic_api\Controller;

use Drupal\apic_api\Service\ApiUtils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ibm_apim\ApicRest;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WSDLRetrieverController extends ControllerBase {

  /**
   * @var \Drupal\apic_api\Service\ApiUtils
   */
  protected ApiUtils $apiUtils;

  /**
   * WSDLRetrieverController constructor.
   *
   * @param \Drupal\apic_api\Service\ApiUtils $apiUtils
   */
  public function __construct(ApiUtils $apiUtils) {
    $this->apiUtils = $apiUtils;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\apic_api\Controller\WSDLRetrieverController|\Drupal\Core\Controller\ControllerBase
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apic_api.utils')
    );
  }

  // $api is the raw ID of an API, e.g. 53c1e135-fe44-413d-aa10-1f92cdc8a21
  // $endpoint is whatever we want the endpoint to be in the WSDL (presumably a URL)
  // Note that $endpoint must be base64 encoded, and the endpoint value is validated
  // against the endpoints listed in the swagger document

  /**
   * @param string $api
   * @param string $endpoint
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   * @throws \Exception
   */
  public function retrieveWSDL($api = NULL, $endpoint = NULL): ?Response {
    $url = NULL;

    // Check the format of the API provided in the request.  We accept either
    // api name and version number (e.g. mathserverservice:1.0.0), or the actual
    // API id (e.g. 53c1e135-fe44-413d-aa10-1f92cdc8a21b)
    if (strpos($api, ':') !== FALSE) {
      // We have an API name and version number
      $apidata = explode(':', $api);
      [$apiname, $apiver] = $apidata;
      // Check the format of the name ([A-Za-z0-9]+) and version ([A-Za-z0-9\.\-\_]+)
      // Do this before trying to load the node, to prevent attacks directed at drupal
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $apiname)) {
        \Drupal::logger('apic_api')->error('WSDLRetrieverController: invalid APINAME in request', []);
        throw new AccessDeniedHttpException();
      }
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $apiver)) {
        \Drupal::logger('apic_api')->error('WSDLRetrieverController: invalid APIVERSION in request', []);
        throw new AccessDeniedHttpException();
      }

      // Load the Node as an ACL check that we have access to this API
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api');
      $query->condition('apic_ref.value', $apiname . ':' . $apiver);

      $nids = $query->accessCheck()->execute();

      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $url = '/consumer-api/apis/' . $apiname . '/' . $apiver . '/wsdl';
        }
        else {
          \Drupal::logger('apic_api')->error('WSDLRetrieverController: Caller denied access to load API', []);
          throw new AccessDeniedHttpException();
        }
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      // We have an API ID, use it raw

      // Load the Node as an ACL check that we have access to this API
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api');
      $query->condition('api_id.value', $api);
      $nids = $query->accessCheck()->execute();

      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          // Validate the format of the ID ([A-Za-z0-9\-]+)
          if (preg_match('/^([A-Za-z0-9\-])+$/', $api)) {
            $url = '/consumer-api/apis/' . $api . '/wsdl';
          }
          else {
            \Drupal::logger('apic_api')->error('WSDLRetrieverController: invalid APIID in request', []);
            throw new AccessDeniedHttpException();
          }
        }
        else {
          \Drupal::logger('apic_api')->error('WSDLRetrieverController: Caller denied access to load API', []);
          throw new AccessDeniedHttpException();
        }
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }

    // Make sure that the endpoint is a valid string of base64 encoded data -- the following regexp should cover
    //   ^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=|[A-Za-z0-9+\/]{4})$
    if (preg_match('/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=|[A-Za-z0-9+\/]{4})$/',
      $endpoint)) {
      // base64 decode the supplied endpoint for validation
      $ep = base64_decode($endpoint, TRUE);
      if ($url !== NULL && $this->apiUtils->validateApiEndpoint($node, $ep)) {
        // URL encode the endpoint to send it to APIM
        $url .= '?endpoint=' . urlencode($ep);
      }
      else {
        \Drupal::logger('apic_api')->error('WSDLRetrieverController: invalid ENDPOINT supplied for api', []);
        throw new AccessDeniedHttpException();
      }
    }
    else {
      \Drupal::logger('apic_api')->error('WSDLRetrieverController: invalid base64 data supplied for ENDPOINT', []);
      throw new AccessDeniedHttpException();
    }
    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous() || (int) $current_user->id() === 1) {
      // Special access for admin and anon, calling the public API as this works without a token.
      // note this will only work for APIs with public visibility, others will get a 40x.
      $url = str_replace("/apis/", "/public-apis/", $url);
    }

    // Invoke the consumer-api
    $result = (array) ApicRest::get($url);

    // Response should be a response object rather than a render array
    $response = new Response();

    // Deny access for anything other than the expected 200
    if (!isset($result['code']) || $result['code'] !== 200) {
      \Drupal::logger('apic_api')->error('WSDLRetrieverController: back end failure, denying access', []);
      throw new AccessDeniedHttpException();
    }

    // Something serious and unexpected went wrong
    if (!isset($result['data']['content_type'], $result['data']['content'])) {
      \Drupal::logger('apic_api')->error('WSDLRetrieverController: Internal error, APIM response missing data', []);
      throw new BadRequestHttpException();
    }
    // Take a look at the content type that APIM tells us for the data
    // (this is not the same as the actual content type header that it sets)
    $ctype = $result['data']['content_type'];
    // Now set the content-type header on our response to match
    // Also add a new header to provide the filename to download (otherwise the
    // browser defaults to using the base64 string which was the last parameter)
    // We use the request API parameter as the file name, so we will either have
    // apiname:apiver or the API id
    if ($ctype === 'application/zip') {
      // We have a ZIP file full of WSDLs
      $response->headers->set('Content-Type', 'application/zip');
      $response->headers->set('Content-Disposition', 'inline; filename="' . $api . '.zip"');
    }
    else {
      // We have a raw WSDL file
      $response->headers->set('Content-Type', 'application/wsdl');
      $response->headers->set('Content-Disposition', 'inline; filename="' . $api . '.wsdl"');
    }

    // The response from APIM is base64 encoded, so decode it
    $wsdl = base64_decode($result['data']['content']);
    $response->setContent($wsdl);

    // Decision: while we could have dumped all headers from APIM to client using
    //   $response->headers->replace($result['headers']);
    // This is potentially dangerous -- it risks leaking information if in future it's
    // added to what APIM sends back.  So instead we explicitly set any headers we know
    // need to be passed on to the client as follows:
    if (isset($result['headers']['X-Request-ID'])) {
      $response->headers->set('X-Request-ID', $result['headers']['X-Request-ID']);
    }

    return $response;
  }

}