<?php


namespace Drupal\ibm_apim\External;

/**
 * Class SerializationUtility
 *
 * This class provides utility methods for safely handling serialization and deserialization.
 *
 * @package Drupal\ibm_apim\External
 */
class SerializationUtility {

  /**
   * @param string|null $data
   * @param bool $assoc
   *   when TRUE, returned objects will be converted into associative arrays.
   * @param mixed $allowedClasses
   * @return mixed
   */
  public static function decodeData($data, bool $assoc = TRUE, $allowedClasses = FALSE) {
    if (empty($data)) {
      return NULL;
    }
    
    $decodedData = NULL;
    $jsonError = NULL;

    try {
      $decodedData = Json::decode($data, $assoc);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decodedData;
      } 
    } catch (\JsonException $e) {
      $jsonError = $e->getMessage();
    }
  
    if ($decodedData === NULL) {
      if (is_string($data) && preg_match('/^(a|O|s|i|b|d|N):/', $data)) {
        try {
          $decodedData = unserialize($data, ['allowed_classes' => $allowedClasses]);
          if ($decodedData === false) {
            throw new \Exception('Failed to unserialize data');
          }
        } catch (\Exception $e) {
          \Drupal::logger('ibm_apim')->error('Failed to unserialize data: @error (JSON error: @jsonError)', [
            '@error' => $e->getMessage(),
            '@jsonError' => $jsonError,
          ]);
          return NULL;
        }
      }
    }
  
    return $decodedData;
  }

  /**
   * @param mixed $data
   * @param int $options
   * @param int $depth
   * @return string
   */
  public static function encodeData($data, int $options = 0, int $depth = 512): string {
    if ($data === NULL) {
      return 'null';
    }
    
    try {
      return Json::encode($data, $options, $depth);
    }
    catch (\JsonException $e) {
      \Drupal::logger('ibm_apim')->error('Failed to encode data to JSON: @error', ['@error' => $e->getMessage()]);
      return 'null';
    }
  }

  /**
   * @param mixed $data
   * @param mixed $allowedClasses
   * @return bool
   */
  public static function isSerializedData($data, $allowedClasses = FALSE): bool {
    if (!is_string($data)) {
      return FALSE;
    }
    
    if (preg_match('/^(a|O|s|i|b|d|N):/', $data)) {
      try {
        $result = @unserialize($data, ['allowed_classes' => $allowedClasses]);
        return $result !== FALSE || $data === 'b:0;';
      }
      catch (\Exception $e) {
        return FALSE;
      }
    }
    
    return FALSE;
  }

  /**
   * @param string $data
   * @return string
   */
  public static function migrateToJson($data): string {
    $decoded = self::decodeData($data);
    return self::encodeData($decoded);
  }

  /**
   * Encodes a JWTToken object to a JSON string
   * 
   * @param \Drupal\auth_apic\JWTToken $token
   * @return string
   */
  public static function encodeJWTToken(\Drupal\auth_apic\JWTToken $token): string {
    $data = [
      'url' => $token->getUrl(),
      'decodedJwt' => $token->getDecodedJwt(),
      'headers' => $token->getHeaders(),
      'payload' => $token->getPayload(),
      'signature' => $token->getSignature()
    ];
    return self::encodeData($data);
  }


  /**
   * Reconstructs a JWTToken object from JSON data
   * 
   * @param string $jsonData
   * @return \Drupal\auth_apic\JWTToken|null
   */
  public static function decodeJWTToken(string $jsonData): ?\Drupal\auth_apic\JWTToken {
    try {
      $data = json_decode($jsonData, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        if (self::isSerializedData($jsonData)) {
          return unserialize($jsonData, ['allowed_classes' => [\Drupal\auth_apic\JWTToken::class]]);
        }
        return NULL;
      }

      $token = new \Drupal\auth_apic\JWTToken();
      if (isset($data['url'])) {
        $token->setUrl($data['url']);
      }
      if (isset($data['decodedJwt'])) {
        $token->setDecodedJwt($data['decodedJwt']);
      }
      if (isset($data['headers'])) {
        $token->setHeaders($data['headers']);
      }
      if (isset($data['payload'])) {
        $token->setPayload($data['payload']);
      }
      if (isset($data['signature'])) {
        $token->setSignature($data['signature']);
      }
      
      return $token;
    } catch (\Exception $e) {
      \Drupal::logger('ibm_apim')->error('Failed to decode JWTToken: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }
}
