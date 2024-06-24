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

namespace Drupal\ibm_apim\Translation;

class ProjectInfo {

  private $name;

  private $version;

  private $pot_file;

  /**
   * @var array
   * Associative array:
   *      * key - langcode
   *      * value - po_file location.
   */
  private $po_files;

  private $po_header;

  private $output_dir;

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name): void {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * @param mixed $version
   */
  public function setVersion($version): void {
    $this->version = $version;
  }

  /**
   * @return mixed
   */
  public function getPotFile() {
    return $this->pot_file;
  }

  /**
   * @param mixed $pot_file
   */
  public function setPotFile($pot_file): void {
    $this->pot_file = $pot_file;
  }

  /**
   * @return array
   *   key=language
   *   value=path to language.po file
   */
  public function getPoFiles(): ?array {
    return $this->po_files;
  }

  /**
   * @param array
   *   key=language
   *   value=path to language.po file
   */
  public function setPoFiles($po_files): void {
    $this->po_files = $po_files;
  }

  /**
   * @return mixed
   */
  public function getPoHeader() {
    return $this->po_header;
  }

  /**
   * @param mixed $po_header
   */
  public function setPoHeader($po_header): void {
    $this->po_header = $po_header;
  }

  /**
   * @return mixed
   */
  public function getOutputDir() {
    return $this->output_dir;
  }

  /**
   * @param mixed $output_dir
   */
  public function setOutputDir($output_dir): void {
    $this->output_dir = $output_dir;
  }


}