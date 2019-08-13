<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\product\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a product is deleted.
 *
 * @see Product::deleteNode()
 */
class ProductDeleteEvent extends Event {

  public const EVENT_NAME = 'product_delete';

  /**
   * The product.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public $product;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $product
   *   The product that was deleted.
   */
  public function __construct(EntityInterface $product) {
    $this->product = $product;
  }

}
