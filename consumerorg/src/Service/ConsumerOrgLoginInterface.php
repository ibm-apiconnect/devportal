<?php

namespace Drupal\consumerorg\Service;


use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\ibm_apim\ApicType\ApicUser;

interface ConsumerOrgLoginInterface {

  /**
   * On login the stub of a consumer org is returned from GET /consumer-api/me?expand=true
   * This exists to allow us to populate the database with enough data to have a valid login, i.e. an org in the selector while
   * we wait for the full data to arrive via webhooks.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $consumerorg
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\consumerorg\ApicType\ConsumerOrg|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateLoginOrg(ConsumerOrg $consumerorg, ApicUser $user): ?ConsumerOrg;

}
