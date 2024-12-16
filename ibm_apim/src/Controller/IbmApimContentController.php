<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2023, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\apic_type_count\Controller\ApicNodeListController;
use Drupal\Core\File\FileSystemInterface;

class IbmApimContentController extends ControllerBase {

  /**
   * Sets the icon for the given node
   *
   * @param $node the node object to set the icon for
   * @param $iconPath path to the icon to be uploaded
   * @param $iconAltText alternative text for the icon
   *
   * @return string A success message
   */
  public static function setIcon($node, string $iconPath, string $iconAltText): string {
    $responseMessage = '';

    $nodeType = $node->getType();
    $fileRepository = \Drupal::service('file.repository');
    $fileSystem = \Drupal::service('file_system');
    $iconData = file_get_contents($iconPath);

    $iconDir = 'private://content-icons';
    $fileSystem->prepareDirectory($iconDir, FileSystemInterface::CREATE_DIRECTORY);

    $iconDestination = $iconDir . '/' . basename($iconPath);
    $iconFileEntity = $fileRepository->writeData($iconData, $iconDestination, FileSystemInterface::EXISTS_RENAME);

    if ($node !== NULL) {
      if (isset($node->apic_image->entity)) {
        $customIconPath = $node->apic_image->entity->getFileUri();
        \Drupal::logger('node')->debug('deleting existing node icon ' . $customIconPath);
        $node->apic_image->entity->delete();
      }

      \Drupal::logger('node_controller')->debug('Setting icon @icon for @type: @node', [
        '@icon' => $iconFileEntity->getFileUri(),
        '@type' => $nodeType,
        '@node' => $node->apic_ref->value
      ]);
      $node->set('apic_image', [
        'target_id' => $iconFileEntity->id(),
        'alt' => $iconAltText,
      ]);
      $node->save();
      $responseMessage = sprintf("Icon successfully set for %s %s", $nodeType, $node->apic_ref->value);
    }

    return $responseMessage;
  }

  /**
   * @param $attachmentPath The path to the attachment file
   * @param $allowedTypes An array of allowed extension types for that file
   * @param $maxFileSizeBytes The maximum allowed file size in bytes
   *
   * @return bool
   */
  private static function validAttachment(string $attachmentPath, array $allowedTypes, int $maxFileSizeBytes): bool {
    if (!file_exists($attachmentPath)) {
      \Drupal::logger('node_controller')->error('input file not found at ' . $attachmentPath, []);
      return false;
    }

    $iconExtension = pathinfo($attachmentPath);
    if (!in_array($iconExtension['extension'], $allowedTypes)) {
      $allowedTypesString = implode(", ",$allowedTypes);
      \Drupal::logger('node_controller')->error('invalid file extension provided, please use one of ' . $allowedTypesString, []);
      return false;
    }

    if (filesize($attachmentPath) > $maxFileSizeBytes) {
      \Drupal::logger('node_controller')->error('icon exceeded maximum allowed size of ' . sprintf('%d', $maxFileSizeBytes) . ' bytes.', []);
      return false;
    }

    return true;
  }

  /**
   * @param $iconPath The path to the icon image file
   * @param $iconAltText The alternative text for the icon
   *
   * @return bool
   */
  private static function validIconArgs(string $iconPath, string $iconAltText): bool {
    if (strlen($iconAltText) > 500) {
      \Drupal::logger('node_controller')->error('validateIconArgs: provided alt text is too long! It must be within 500 characters', []);
      return false;
    }

    $allowedTypes = ['png', 'gif', 'jpg', 'jpeg'];
    // max size 2 MB
    $maxFileSizeBytes = 2097152;
    return self::validAttachment($iconPath, $allowedTypes, $maxFileSizeBytes);
  }

  /**
   * @param $apiRef The name:version or id of the api
   * @param $iconPath The path to the icon image file
   * @param $iconAltText The alternative text for the icon
   *
   * @return string A success message
   */
  public static function setApiIcon(string $apiRef, string $iconPath, string $iconAltText): string {
    $node = ApicNodeListController::getEntityofType($apiRef, 'api');

    $validIcon = self::validIconArgs($iconPath, $iconAltText);
    if (!$validIcon) {
      return '';
    }

    if ($node !== NULL) {
      return self::setIcon($node, $iconPath, $iconAltText);
    }
    return '';
  }

  /**
   * @param $productRef The name:version or id of the product
   * @param $iconPath The path to the icon image file
   * @param $iconAltText The alternative text for the icon
   *
   * @return string A success message
   */
  public static function setProductIcon(string $productRef, string $iconPath, string $iconAltText): string {
    $node = ApicNodeListController::getEntityofType($productRef, 'product');

    $validIcon = self::validIconArgs($iconPath, $iconAltText);
    if (!$validIcon) {
      return '';
    }

    if ($node !== NULL) {
      return self::setIcon($node, $iconPath, $iconAltText);
    }
    return '';
  }

  /**
   * @param $apiRef The name:version or id of the api
   * @param $taxonomy Taxonomy path e.g. top_level_element/next_level_element
   *
   * @return string A success message
   */
  public static function addApiCategory(string $apiRef, string $category): string {
    $responseMessage = '';
    $node = ApicNodeListController::getEntityofType($apiRef, 'api');

    $api['consumer_api']['x-ibm-configuration']['categories'] = [ $category ];

    $apicTaxonomy = \Drupal::service('ibm_apim.taxonomy');

    \Drupal::logger('node_controller')->debug('Setting taxonomy tag @category for api: @api', [
      '@category' => $category,
      '@api' => $node->apic_ref->value
    ]);

    $apicTaxonomy->process_api_categories($api, $node);
    $node->save();
    $responseMessage = sprintf("Successfully set taxonomy tag %s for api %s", $category, $node->apic_ref->value);

    return $responseMessage;
  }

  /**
   * @param $productRef The name:version or id of the product
   * @param $taxonomy Taxonomy path e.g. top_level_element/next_level_element
   *
   * @return string A success message
   */
  public static function addProductCategory(string $productRef, string $category): string {
    $responseMessage = '';
    $node = ApicNodeListController::getEntityofType($productRef, 'product');

    $product['catalog_product']['info']['categories'] = [ $category ];

    $apicTaxonomy = \Drupal::service('ibm_apim.taxonomy');

    \Drupal::logger('node_controller')->debug('Setting taxonomy tag @category for product: @product', [
      '@category' => $category,
      '@product' => $node->apic_ref->value
    ]);

    $apicTaxonomy->process_product_categories($product, $node);
    $node->save();
    $responseMessage = sprintf("Successfully set taxonomy tag %s for product %s", $category, $node->apic_ref->value);

    return $responseMessage;
  }

  /**
   * @param $node the node object to set the icon for
   * @param $attachmentPath The path to the attachment file
   * @param $description A description of the attachment
   *
   * @return string A success message
   */
  public static function addAttachment($node, string $attachmentPath, string $description = ''): string {
    $responseMessage = '';
    $nodeType = $node->getType();
    $fileRepository = \Drupal::service('file.repository');
    $fileSystem = \Drupal::service('file_system');
    $attachmentData = file_get_contents($attachmentPath);
    $attachmentDir = 'private://content-attachments';
    $fileSystem->prepareDirectory($attachmentDir, FileSystemInterface::CREATE_DIRECTORY);
    $attachmentDestination = $attachmentDir . '/' . basename($attachmentPath);

    if ($node !== NULL) {
      $attachments = $node->apic_attachments->getValue();
      if ($attachments !== NULL) {
        if (count($attachments) >= 10) {
          \Drupal::logger('node_controller')->error('Cannot add attachment. This @type already has the maximum number of attachments!', ['@type' => $nodeType]);
          return '';
        }

        $attachmentFileEntity = $fileRepository->writeData($attachmentData, $attachmentDestination, FileSystemInterface::EXISTS_RENAME);
        \Drupal::logger('node_controller')->debug('Setting attachment @attachmentName for @type: @node', [
          '@attachmentName' => $attachmentFileEntity->getFileUri(),
          '@type' => $nodeType,
          '@node' => $node->apic_ref->value
        ]);

        array_push($attachments, [
          'target_id' => $attachmentFileEntity->id(),
          'display' => 1,
          'description' => $description
        ]);
      }

      $node->set('apic_attachments', $attachments);
      $node->save();
      $responseMessage = sprintf("Attachment successfully added to %s %s. This %s now has %d attachments.", $nodeType, $node->apic_ref->value, $nodeType, count($attachments));
    }
    return $responseMessage;
  }

  /**
   * @param $attachmentPath The path to the attachment file
   * @param $description A description of the attachment
   *
   * @return bool
   */
  private static function validAttachmentArgs(string $attachmentPath, string $description = ''): bool {
    if (strlen($description) > 500) {
      \Drupal::logger('node_controller')->error('provided description is too long! It must be within 500 characters', []);
      return false;
    }

    if (!file_exists($attachmentPath)) {
      \Drupal::logger('node_controller')->error('input attachment file not found at ' . $attachmentPath, []);
      return false;
    }

    $allowedTypes = ['txt', 'doc', 'pdf', 'xls', 'ppt', 'pptx', 'docx', 'xlsx', 'rtf', 'odt', 'ods', 'odp', 'md', 'json', 'yaml', 'yml', 'tgz', 'tar', 'zip'];
    // max size 10 MB
    $maxFileSizeBytes = 2097152;
    return self::validAttachment($attachmentPath, $allowedTypes, $maxFileSizeBytes);
  }

  /**
   * @param $apiRef The name:version or id of the api
   * @param $attachmentPath The path to the attachment file
   *
   * @return string A success message
   */
  public static function addApiAttachment(string $apiRef, string $attachmentPath, string $description = ''): string {
    $node = ApicNodeListController::getEntityofType($apiRef, 'api');

    $validAttachment = self::validAttachmentArgs($attachmentPath, $description);
    if (!$validAttachment) {
      return '';
    }

    return self::addAttachment($node, $attachmentPath, $description);
  }

  /**
   * @param $productRef The name:version or id of the product
   * @param $attachmentPath The path to the attachment file
   *
   * @return string A success message
   */
  public static function addProductAttachment(string $productRef, string $attachmentPath, string $description = ''): string {
    $node = ApicNodeListController::getEntityofType($productRef, 'product');

    $validAttachment = self::validAttachmentArgs($attachmentPath, $description);
    if (!$validAttachment) {
      return '';
    }

    return self::addAttachment($node, $attachmentPath, $description);
  }
}
