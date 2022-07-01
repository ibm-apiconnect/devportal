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
namespace Drupal\ibm_apim\Controller;

use Drupal\node\Controller\NodeController;

/**
 * Returns responses for Node routes.
 */
class IbmApimNodeController extends NodeController {


  /**
   * Displays add content links for available content types.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addPage() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $build = [
      '#theme' => 'node_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('node_type')->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use node types the user has access to.
    foreach ($this->entityTypeManager()->getStorage('node_type')->loadMultiple() as $type) {
      // Block access to APIC content types
      if (!in_array($type->get('type'), array('consumerorg', 'api', 'application', 'product'))) {
        $access = $this->entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
        if ($access->isAllowed()) {
          $content[$type->id()] = $type;
        }
        $this->renderer->addCacheableDependency($build, $access);
      }
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) === 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', array('node_type' => $type->id()));
    }

    $build['#content'] = $content;

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $build;
  }

}
