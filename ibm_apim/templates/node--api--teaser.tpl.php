<?php

/**
 * @file
 * Default teaser theme implementation for apis.
 *
 * @see template_preprocess()
 * @see template_preprocess_api()
 * @see template_process()
 * @see theme_api()
 *
 * @ingroup themeable
 */
?>
<article id="node-<?php print $node->nid; ?>"
	class="<?php print $classes; ?> apimTeaser clearfix"
	<?php print $attributes; ?>>
	<?php $showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);?>

	<div class="apimSummaryContainer">
		<div class="apimOuterContainer">
			<div class="apimSummary">
				<div class="apimInnerContainer">
	 <?php if (isset($content['api_image'])) : ?>
						<div class="apimIcon">
							<?php print render($content['api_image']); ?>
						</div>
     <?php elseif($showplaceholders != 0): ?>
				<div class="apimIcon">
					<div class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
					<div class="field-items">
					<figure class="clearfix field-item even">
						<img typeof="foaf:Image" class="image-style-none"
							 src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/api/' . api_random_image($node->title)); ?>"
							 width="123" height="123" alt="">
					</figure>
					</div>
					</div>
				</div>
	  <?php else: ?>
	     <div class="apimIcon" style="display:none"></div>
      <?php endif; ?>
      <div class="apimTeaserRating">
      <?php if (isset($content['field_apirating'])) {
    $content['field_apirating']['#label_display'] = 'hidden';
    print render($content['field_apirating']);
  } ?></div>
					<h2 class="apimSummaryTitle"><?php print $titlelink; ?></h2>
					<div class="apimUpdated">
	<?php try {
			  if (is_numeric($api_updated[0]['value'])) {
				  $epoch = (int) ($api_updated[0]['value']);
			    $updateddate = new DateTime("@$epoch");
			  } else {
			    $updateddate = new DateTime($api_updated[0]['value']);
			  }
			   print $updateddate->format('Y-m-d');
    } catch (Exception $e) {
    } ?></div>

                        <div class="apimSummaryDescription">

                        <?php
                        print '<div class="apimFade" title="'. $api_description[0]['safe_value'] .'">';
                        if (module_exists('markdown')) {
                          print _filter_markdown($api_description[0]['safe_value'], null);
                        } else {
                          print '<p>'. $api_description[0]['safe_value'] .'</p>';
                        }
                        print '</div>';
                        ?>
						</div>
						<div class="extraFields">
						<?php if (is_array($customfields) && count($customfields) > 0) {
  							foreach($customfields as $customfield) {
   	 							print render($content[$customfield]);
  							}
						} ?>
						</div>
					</div>
				</div>
			</div>
		</div>
</article>
