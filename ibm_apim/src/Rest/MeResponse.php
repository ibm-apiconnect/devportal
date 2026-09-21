<?php


namespace Drupal\ibm_apim\Rest;


use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;

/**
 * Response to GET /me?expanded=true.
 */
class MeResponse extends RestResponse {

  /**
   * @var ApicUser|null
   */
  private ?ApicUser $user = NULL;

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser|null $user
   */
  public function setUser(?ApicUser $user): void {
    $this->user = $user;
  }


  /**
   * @return ApicUser|null
   */
  public function getUser(): ?ApicUser {
    return $this->user;
  }

}
