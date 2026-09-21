
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/


Drupal.behaviors.imageStylePreview = {
  attach(context) {
    once('image-style-preview', '.preview-image', context).forEach((el) => {
      const width = el.dataset.previewWidth;
      const height = el.dataset.previewHeight;

      if (width && height) {
        el.style.width = width + 'px';
        el.style.height = height + 'px';

        // Also apply to image inside, if it exists
        const img = el.querySelector('img');
        if (img) {
          img.style.width = width + 'px';
          img.style.height = height + 'px';
        }
      }
    });

    once('image-dimensions-labels', '.preview-image .height, .preview-image .width', context).forEach((el) => {
      if (el.dataset.previewHeight) {
        el.style.height = el.dataset.previewHeight + 'px';
      }
      if (el.dataset.previewWidth) {
        el.style.width = el.dataset.previewWidth + 'px';
      }
    });
  }
};
