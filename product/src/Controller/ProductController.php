<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\product\Controller;

use Drupal\apic_api\Api;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\product\Product;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller routines for product routes.
 */
class ProductController extends ControllerBase {

  /**
   * Request stack.
   *
   * @var RequestStack
   */
  public $request;

  /**
   * Class constructor.
   *
   * @param RequestStack $request
   *   Request stack.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(// Load the service required to construct this class.
      $container->get('request_stack'));
  }

  /**
   * This method simply redirects to the node/x page, with the node having been loaded via a ParamConverter
   * @param \Drupal\node\NodeInterface|NULL $prodNode
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function productView(NodeInterface $prodNode = NULL) {
    if ($prodNode->bundle() == 'product') {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $build = $view_builder->view($prodNode, 'full');
      return $build;
    }
    else {
      \Drupal::logger('product')->error('productView: not a valid product.', array());
      drupal_set_message(t('The specified arguments were not correct.'), 'warning');
      $url = Url::fromRoute('<front>')->toString();
      return new RedirectResponse($url);
    }
  }

  /**
   * @param \Drupal\node\NodeInterface|NULL $prodNode
   * @return string
   */
  public function productTitle(NodeInterface $prodNode = NULL) {
    if ($prodNode->bundle() == 'product') {
      return $prodNode->getTitle() . ' - ' . \Drupal::config('system.site')->get('name');
    }
    else {
      \Drupal::logger('product')->error('productView: not a valid product.', array());
      drupal_set_message(t('The specified arguments were not correct.'), 'warning');
      return 'ERROR';
    }
  }

  /**
   * This method loads the product telling it which API to embed
   *
   * @param \Drupal\node\NodeInterface|NULL $prodNode
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function productApi(NodeInterface $prodNode = NULL, NodeInterface $apiNode = NULL) {
    if ($prodNode->id() && $apiNode->id()) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array(
        'prodNode' => $prodNode->id(),
        'apiNode' => $apiNode->id()
      ));
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $found = FALSE;
    $config = \Drupal::config('ibm_apim.settings');
    $ibm_apim_show_versions = $config->get('show_versions');
    if ($ibm_apim_show_versions === NULL) {
      $ibm_apim_show_versions = 1;
    }
    $ibm_apim_show_placeholder_images = $config->get('show_placeholder_images');
    if ($ibm_apim_show_placeholder_images === NULL) {
      $ibm_apim_show_placeholder_images = 1;
    }

    if ($prodNode->bundle() == 'product') {
      if ($apiNode->bundle() == 'api') {
        // check this product actually includes the specified API
        $prodrefs = array();
        foreach ($prodNode->product_apis->getValue() as $arrayValue) {
          $prodrefs[] = unserialize($arrayValue['value']);
        }
        foreach ($prodrefs as $prodref) {
          if ($prodref['name'] == $apiNode->apic_ref->value) {
            $found = TRUE;
            $product = array(
              'nid' => $prodNode->id(),
              'id' => $prodNode->product_id->value,
              'title' => $prodNode->getTitle(),
              'version' => $prodNode->apic_version->value,
              'pathalias' => $prodNode->apic_pathalias->value
            );
          }
        }

        $fid = $apiNode->apic_image->getValue();
        $api_image_url = NULL;
        if (isset($fid) && !empty($fid) && isset($fid[0]['target_id'])) {
          $file = File::load($fid[0]['target_id']);
          if (isset($file)) {
            $api_image_url = $file->toUrl()->toUriString();
          }
        }
        else {
          if ($ibm_apim_show_placeholder_images) {
            $rawImage = Api::getRandomImageName($apiNode->getTitle());
            $api_image_url = base_path() . drupal_get_path('module', 'apic_api') . '/images/' . $rawImage;
          }
        }

        $api = array(
          'nid' => $apiNode->id(),
          'id' => $apiNode->api_id->value,
          'title' => $apiNode->getTitle(),
          'version' => $apiNode->apic_version->value,
          'pathalias' => $apiNode->apic_pathalias->value,
          'image_url' => $api_image_url
        );

        if (!$found) {
          \Drupal::logger('product')->error('productApi: product does not contain the specified API.', array());
          drupal_set_message(t('The specified arguments were not correct.'), 'warning');
        }
      }
      else {
        \Drupal::logger('product')->error('productApi: not a valid API.', array());
        drupal_set_message(t('The specified arguments were not correct.'), 'warning');
      }
    }
    else {
      \Drupal::logger('product')->error('productApi: not a valid product.', array());
      drupal_set_message(t('The specified arguments were not correct.'), 'warning');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $found);
    if ($found) {
      $attached = array('library' => array());
      $attached['library'][] = 'product/basic';
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('votingapi_widgets')) {
        $attached['library'][] = 'votingapi_widgets/fivestar';
      }

      return array(
        '#theme' => 'product_wrapper',
        '#api' => $api,
        '#product' => $product,
        '#attached' => $attached,
        '#showPlaceholders' => $ibm_apim_show_placeholder_images,
        '#showVersions' => $ibm_apim_show_versions
      );
    }
    else {
      \Drupal::logger('product')
        ->error('productApi: api %api is not in product %product.', array(
          '%api' => $apiNode->id(),
          '%product' => $prodNode->id()
        ));
      drupal_set_message(t('The specified arguments were not correct.'), 'warning');
      $url = Url::fromRoute('<front>')->toString();
      return new RedirectResponse($url);
    }
  }

  /**
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   * @return string
   */
  public function productApiTitle(NodeInterface $apiNode = NULL) {
    if ($apiNode->bundle() == 'api') {
      return $apiNode->getTitle() . ' - ' . \Drupal::config('system.site')->get('name');
    }
    else {
      \Drupal::logger('product')->error('productApi: not a valid api.', array());
      drupal_set_message(t('The specified arguments were not correct.'), 'warning');
      return 'ERROR';
    }
  }

  /**
   * @param \Drupal\node\NodeInterface|NULL $apiNode
   * @return array
   */
  public function select(NodeInterface $apiNode = NULL) {
    if ($apiNode->id()) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array(
        'apiNode' => $apiNode->id()
      ));
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $products = array();
    $product_nids = Product::getProductsContainingAPI($apiNode->apic_ref->value);
    if (isset($product_nids) && !empty($product_nids)) {
      $nodes = Node::loadMultiple($product_nids);
      // if only one product found then just load it and redirect
      if (count($nodes) == 1) {
        $firstNode = reset($nodes);
        $path = Url::fromRoute('product.api', array('prodNode' => $firstNode, 'apiNode' => $apiNode->id()))
          ->setAbsolute()
          ->toString();
        $response = new RedirectResponse($path);
        $response->send();
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return NULL;
      }
      else {
        foreach ($nodes as $prodNode) {
          $products[] = array(
            'nid' => $prodNode->id(),
            'id' => $prodNode->product_id->value,
            'title' => $prodNode->getTitle(),
            'version' => $prodNode->apic_version->value
          );
        }
      }
    }

    $attached = array('library' => array());
    $attached['library'][] = 'product/basic';
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $products);
    return array(
      '#theme' => 'product_select',
      '#apiNid' => $apiNode->id(),
      '#products' => $products,
      '#attached' => $attached
    );
  }
}
