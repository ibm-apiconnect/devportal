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

namespace Drupal\product\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a product is deprecated.
 *
 * @see Product::update()
 */
class ProductDeprecateEvent extends Event {

  public const EVENT_NAME = 'product_deprecate';

  /**
   * The product.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $product;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $product
   *   The product that was deprecated.
   */
  public function __construct(NodeInterface $product) {
    $this->product = $product;
    node_access_rebuild(TRUE);
  }

}
