/**
 * @file
 * Fixes contextual delete links by removing destination parameter.
 */

(function (Drupal) {
  'use strict';

  /**
   * Remove destination parameter from delete links in contextual menus.
   */
  Drupal.behaviors.contextualLinksFix = {
    attach: function (context, settings) {
      // Find all contextual delete links
      const deleteLinks = context.querySelectorAll('.contextual-links a[href*="/delete?destination="]');
      
      deleteLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        
        // Check if this is a node delete link with destination parameter
        if (href && href.includes('/node/') && href.includes('/delete?destination=')) {
          // Extract the node ID from the URL
          const nodeIdMatch = href.match(/\/node\/(\d+)\/delete/);
          
          if (nodeIdMatch) {
            const nodeId = nodeIdMatch[1];
            const destinationPattern = 'destination=/ibm/catalog-2/node/' + nodeId;
            
            // If destination points to the node being deleted, remove it
            if (href.includes(destinationPattern)) {
              const newHref = href.replace(/\?destination=[^&]+(&|$)/, '').replace(/&destination=[^&]+/, '');
              link.setAttribute('href', newHref);
            }
          }
        }
      });
    }
  };

})(Drupal);