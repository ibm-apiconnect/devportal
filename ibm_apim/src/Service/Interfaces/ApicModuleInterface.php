<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Interfaces;


interface ApicModuleInterface {

  /**
   * Delete list of extensions from <site_dir>/(themes/modules) directory.
   *
   * @param array $extensions
   *
   * @return bool TRUE if all extensions deleted successfully, FALSE there were problems, see logs for more details.
   */
  public function deleteExtensionOnFileSystem(string $extensionType, array $extensions): bool;


  /**
   * Purge any blocklisted modules from the site.
   * This will uninstall the module if running and delete the files from the filesystem.
   *
   * @return bool TRUE if successful, FALSE otherwise
   */
  public function purgeBlockListedModules(): bool;

}
