<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2017
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Service;


use Drupal\consumerorg\ApicType\Role;

class RoleService {

  function __construct() {

  }

  /**
   * Parse an ApicType/Role object out of the given JSON structure (e.g. from a webhook body or snapshot)
   *
   * @param $json
   *
   * @return \Drupal\consumerorg\ApicType\Role
   */
  public function createFromJSON($json){
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $role = new Role();

    if(!empty($json['id'])) {
      $role->setId($json['id']);
    }
    if(!empty($json['url'])) {
      $role->setUrl($json['url']);
    }
    if(!empty($json['name'])) {
      $role->setName($json['name']);
    }
    if(!empty($json['title'])) {
      $role->setTitle($json['title']);
    }
    if(!empty($json['summary'])) {
      $role->setSummary($json['summary']);
    }
    if(!empty($json['permission_urls'])) {
      $role->setPermissions($json['permission_urls']);
    }
    if(!empty($json['scope'])) {
      $role->setScope($json['scope']);
    }
    if(!empty($json['org_url'])) {
      $role->setOrgUrl($json['org_url']);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $role);
    return $role;
  }

  /*
   *  {
             "type": "role",
             "api_version": "2.0.0",
             "id": "b543a730-1b93-4ff4-8431-d074502ac689",
             "name": "viewer",
             "title": "Viewer",
             "summary": "Views the app developer organization",
             "permission_urls": [
                 "/consumer-api/consumer/permissions/org/01764870-d6fa-43de-9ad2-130eff9aeece",
                 "/consumer-api/consumer/permissions/org/06692cc1-acd8-49a8-b7de-c6c5b10471dc",
                 "/consumer-api/consumer/permissions/org/a0222c0d-82cc-4fa8-b4b0-ddcc366523c7",
                 "/consumer-api/consumer/permissions/consumer/822f2c2a-b3e6-4189-8fbf-b7b7ef1c3f41",
                 "/consumer-api/consumer/permissions/consumer/d13c5e0b-fdb6-4f2a-aee7-496654a5f3cc",
                 "/consumer-api/consumer/permissions/consumer/9130578d-3e76-4392-9d0a-7f4ba685dc6e",
                 "/consumer-api/consumer/permissions/consumer/e90bb702-c54e-45c7-9f75-95f3ccbaba0e"
             ],
             "shadow_id": "cb0bf3c1-26be-11e8-a370-3f11f3667136",
             "shadow": false,
             "created_at": "2018-03-13T13:02:32.645Z",
             "updated_at": "2018-03-13T13:02:32.645Z",
             "org_url": "/consumer-api/orgs/d4fc39a5-7fef-4769-930e-d844c41dd5ca",
             "url": "/consumer-api/orgs/d4fc39a5-7fef-4769-930e-d844c41dd5ca/roles/b543a730-1b93-4ff4-8431-d074502ac689"
         }
   */

}