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

namespace Drupal\ibm_apim\JsonStreamingParser;

use JsonStreamingParser\Listener\ListenerInterface;

class CollectionListener implements ListenerInterface {

  /**
   * @var array
   */
  protected array $stack = [];

  /**
   * @var string|null
   */
  protected ?string $key = NULL;

  /**
   * @var int
   */
  protected int $level = 0;

  /**
   * @var int
   */
  protected int $objectLevel = 0;

  /**
   * @var array
   */
  protected array $objectKeys = [];

  /**
   * @var callback|callable
   */
  protected $callback;

  /**
   * @var bool
   */
  protected bool $assoc = TRUE;

  protected $UUID;
  protected $data;
  protected $count = 0;

  /**
   * @param callback|callable $callback callback for parsed collection item
   * @param bool $assoc When true, returned objects will be converted into associative arrays
   */
  public function __construct(callable $callback, $UUID, $data) {
    $this->callback = $callback;
    $this->UUID = $UUID;
    $this->data = $data;
  }

  public function startDocument(): void {
    $this->stack = [];
    $this->key = NULL;
    $this->objectLevel = 0;
    $this->level = 0;
    $this->objectKeys = [];
  }

  public function endDocument(): void {
    $this->stack = [];
  }

  public function startObject(): void {
    $this->objectLevel++;

    $this->startCommon();
  }

  public function endObject(): void {
    $this->endCommon();

    $this->objectLevel--;
    if ($this->objectLevel === 0) {
      $obj = array_pop($this->stack);
      $obj = reset($obj);

      call_user_func($this->callback, $obj, $this->UUID, $this->count, $this->data);
      $this->count++;
    }
  }

  public function startArray(): void {
    $this->startCommon();
  }

  public function startCommon(): void {
    $this->level++;
    $this->objectKeys[$this->level] = ($this->key) ?: NULL;
    $this->key = NULL;

    $this->stack[] = [];
  }

  public function endArray(): void {
    $this->endCommon(FALSE);
  }

  /**
   * @param bool $isObject
   */
  public function endCommon($isObject = TRUE): void {
    $obj = array_pop($this->stack);

    if ($isObject && !$this->assoc) {
      $obj = (object) $obj;
    }

    if (!empty($this->stack)) {
      $parentObj = array_pop($this->stack);

      if ($this->objectKeys[$this->level]) {
        $objectKey = $this->objectKeys[$this->level];
        $parentObj[$objectKey] = $obj;
        unset($this->objectKeys[$this->level]);
      }
      else {
        $parentObj[] = $obj;
      }
    }
    else {
      $parentObj = [$obj];
    }

    $this->stack[] = $parentObj;

    $this->level--;
  }

  /**
   * @param string $key
   */
  public function key(string $key): void {
    $this->key = $key;
  }

  /**
   * @param mixed $value
   */
  public function value($value): void {
    $obj = array_pop($this->stack);

    if ($this->key) {
      $obj[$this->key] = $value;
      $this->key = NULL;
    }
    else {
      $obj[] = $value;
    }

    $this->stack[] = $obj;
  }

  /**
   * @param string $whitespace
   */
  public function whitespace(string $whitespace): void {
  }

}
