<?php

/**
 * @file
 * Default theme implementation for applications.
 *
 *
 * @see template_preprocess()
 * @see template_preprocess_application()
 * @see template_process()
 * @see theme_application()
 *
 * @ingroup themeable
 */
drupal_add_library('system', 'ui.accordion');
drupal_add_js('jQuery(document).ready(function(){
      jQuery("div#accordion").accordion({
        header: "> div > h3",
        collapsible: true,
        active: false,
        heightStyle: "content",
      });
      jQuery(".detailsLink").on("click", null, null, function(event) {
        window.location.href = jQuery(this).attr("href");
        event.preventDefault();
      });
    });', 'inline');
drupal_add_js(drupal_get_path('module', 'application') . '/js/showclientid.js', array(
  'weight' => 3
));
?>
<div class="pagebreadcrumb"><?php print l("< " . t('Back to Apps'), 'application'); ?></div>
<article id="node-<?php print $node->nid; ?>"
         class="<?php print $classes; ?> clearfix" <?php print $attributes; ?>>

  <?php print $unpublished; ?>

  <?php print render($title_prefix); ?>
  <?php $showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1); ?>

  <div class="portal">
    <?php
    if (isset($application_state[0]['safe_value']) && strtoupper($application_state[0]['safe_value']) == 'SUSPENDED') {
      print '<div class="appSuspended">' . t('This application is currently suspended. The client id is blocked from accessing APIs.') . '</div>';
    }
    ?>

    <div class="apimTopSection">
      <div class="apimAPIImage">
        <?php if (isset($content['application_image']) && !(empty($content['application_image']))) {
          print render($content['application_image']);
        }
        elseif ($showplaceholders != 0) {
          print '<div class="apimIcon">
			 <div class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
			   <div class="field-items">
				 <figure class="clearfix field-item even">
				   <img typeof="foaf:Image" class="image-style-none" src="' . file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/app/' . application_random_image($node->title)) . '" width="123" height="123" alt="">
				 </figure>
		       </div>
			 </div>
		   </div>';
        }
        else {
          print "<span class=\"appNoImage\">" . t("No Image") . "</span>";
        } ?>
        <?php

        print '<span class="apimImageActions">' . $uploadimagelink;
        if (isset($content['application_image']) && !(empty($content['application_image']))) {
          print " | " . $removeimagelink . '</span>';
        }
        ?>

      </div>
      <div class="apimInfoSection">
        <div class="apimTitleContainer">
          <div class="apimTitle">
            <h1><?php print $title; ?></h1>
            <?php print '<span class="apimAppActions">' . $analyticslink . ' | ' . $notificationsettingslink;
            if (isset($isdev) && $isdev == TRUE) {
              print ' | ' . $editlink . ' | ' . $deletelink;
            }
            print '</span>';
            ?>
          </div>
          <div class="apimUpdated clearBoth"><?php try {
              if (isset($application_updated[0]['value'])) {
                if (is_numeric($application_updated[0]['value'])) {
                  $epoch = (int) ($application_updated[0]['value']);
                  $updateddate = new DateTime("@$epoch");
                }
                else {
                  $updateddate = new DateTime($application_updated[0]['value']);
                }
                print $updateddate->format('Y-m-d');
              }
            } catch (Exception $e) {
            } ?></div>
          <?php
          if (isset($content['field_applicationrating'])) {
            $content['field_applicationrating']['#label_display'] = 'hidden';
            print render($content['field_applicationrating']);
          }
          ?>
        </div>

        <?php if (isset($application_description[0]['safe_value']) && !empty($application_description[0]['safe_value'])) : ?>
          <div class="clearBoth section apimMainContent">
            <label class="label apimField apimDescription"><?php print t('Description'); ?></label>
            <div id="app_description"><?php print $application_description[0]['safe_value']; ?></div>
          </div>
        <?php endif; ?>


        <?php
        if (isset($isdev) && $isdev == TRUE) {
          print '<div class="credentialList clearBoth section apimMainContent"><div class="credentialTitle">' . t('Client Credentials') . '<span class="addCredentials">' . $addcredentialslink . '</span></div>';
          $credcount = count($credentials);
          $index = 0;
          foreach ($credentials as $cred) {
            drupal_add_js('jQuery(document).ready(function(){
        jQuery("#show-clientID' . $index . '").change(function(){
          jQuery("#clientID' . $index . '").hideShowPassword(jQuery(this).prop("checked"));
        });
      });', 'inline');
            print '<div class="credentialTable"><div class="credentialPreSpacer"><div class="credentialSpacer"></div><div class="credentialSpacer"></div></div><div class="credentialContainer">
       <div class="credentialInfo">
        <div class="credentialInfoDescription">' . check_plain($cred['description']) . '</div>
        <label for="clientID" class="label apimField apiClientID">' . t('Client ID') . '</label>
        <div id="app_client_id" class="app_client_id">
		  <input class="toggle-password" id="clientID' . $index . '" type="password" readonly value="' . $cred['client-id'] . '" />
          <div class="passwordToggleContainer">
			<input type="checkbox" id="show-clientID' . $index . '" /> <label for="show-clientID' . $index . '">' . t('Show') . '</label> &nbsp;&nbsp;&nbsp;&nbsp; <a class="buttonLink" href="' . url("application/" . $application_apiid[0]['value'] . "/reset-clientid/" . $cred['id']) . '">' . t('Reset') . '</a>
          </div>
		</div>
		<label for="clientSecret" class="label apimField apiClientSecret">' . t('Client Secret') . '</label>
		<div class="client_secret">
          <input id="clientSecret' . $index . '" class="clientSecretInput" disabled readonly /> <a class="buttonLink" href="' . url("application/" . $application_apiid[0]['value'] . "/verify/" . $cred['id']) . '">' . t('Verify') . '</a> &nbsp;&nbsp;&nbsp;&nbsp; <a class="buttonLink" href="' . url("application/" . $application_apiid[0]['value'] . "/reset-secret/" . $cred['id']) . '">' . t('Reset') . '</a>
        </div>
       </div>';

            print '<div class="credentialActions">
        <a href="' . url("application/" . $application_apiid[0]['value'] . "/update-clientcreds/" . $cred['id']) . '" title="' . t('Edit') . '"><i class="material-icons">mode_edit</i></a>';
            if ($credcount > 1) {
              print ' | <a href="' . url("application/" . $application_apiid[0]['value'] . "/delete-clientcreds/" . $cred['id']) . '" title="' . t('Delete') . '"><i class="material-icons">delete</i></a>';
            }
            print '</div>';
            print '</div><div class="credentialPostSpacer"><div class="credentialSpacer"></div><div class="credentialSpacer"></div></div></div>';
            $index++;
          }
          print '</div>';
        }
        ?>

        <?php if (isset($application_oauthredirecturi[0]['safe_value']) && !empty($application_oauthredirecturi[0]['safe_value'])) : ?>
          <div class="clearBoth section apimMainContent oauthRedirect">
            <label class="label apimField apiOauthRedirectionURL"><?php print t('OAuth Redirection URL'); ?></label>
            <div id="app_oauth_redirecturi"
                 class="app_oauth_redirecturi"><?php print $application_oauthredirecturi[0]['safe_value']; ?></div>

          </div>
        <?php endif; ?>
        <div>
          <?php if (is_array($customfields) && count($customfields) > 0) {
            foreach ($customfields as $customfield) {
              print render($content[$customfield]);
            }
          } ?>
        </div>
      </div>
    </div>
    <?php
    if (module_exists('product')) {
      print '<div class="clearBoth portal section apimMainContent apimPlansApis">';
      print '<h2>' . t('Subscriptions') . '</h2>';
      if (is_array($subscriptions) && count($subscriptions) > 0) {
        foreach ($subscriptions as $plankey => $sub) {
          //get correct plan info
          $pieces = explode(":", $plankey);
          $productname = $pieces[0];
          $productversion = $pieces[1];
          $planname = $pieces[2];

          $pendingapp = '';
          if ($sub['approved'] != TRUE) {
            $pendingapp = ' (' . t('Pending Approval') . ')';
          }
          $unsubscribelink = '';
          if (isset($isdev) && $isdev == TRUE) {
            $unsubscribelink = "<a href='" . url('application/' . $application_apiid[0]['safe_value'] . '/unsubscribe/' . check_plain($sub['subid'])) . "'>" . t('Unsubscribe') . "</a>";
          }
          $showversion = variable_get('ibm_apim_show_versions', 1);
          $versiontext = '';
          if ($showversion == 1) {
            $versiontext = ' (v' . $productversion . ')';
          }
          $moreinfolink = '<a href="' . url('node/' . $sub['productnid']) . '" title="' . t('More information') . '"><i class="material-icons">info</i></a>';
          print "<div class='planWrapper'><h3 class='appPlanName'>" . check_plain($productname) . $versiontext . ' ' . $planname . ' ' . $pendingapp . ' ' . $moreinfolink . " <span class='planUnsubscribe'>" . $unsubscribelink . "</span></h3>";
          if (isset($sub['superseded-by']) && !empty($sub['superseded-by']) && isset($isdev) && $isdev == TRUE) {
            print "<div class='migratePlanContainer'><div class='migrateButton'>" . l(t('Migrate'), 'application/' . $application_apiid[0]['safe_value'] . '/migrate/' . $sub['subid'] . '/' . ibm_apim_base64_url_encode($sub['superseded-by'])) . "</div><div class='migratePlanText'>" . t('A new version of this plan has been published.') . "</div></div>";
          }
          print "<div id='accordion'>";
          if (isset($sub['nodes'])) {
            foreach ($sub['nodes'] as $apiurl => $api) {
              $versiontext = '';
              if ($showversion == 1) {
                $versiontext = ' (v' . $api['version'] . ')';
              }
              print "<div class='apiwrapper'><h3 class='subApiContainer'>" . check_plain($api['title']) . $versiontext . "</h3>";
              print "<div class='portalApi " . $api['protocol'] . " animateMaxHeight subAPIContent'>";
              if (isset($api['protocol']) && $api['protocol'] == 'wsdl') {
                print "<div class='tableHeaderBackground clearTable'>
			<div class='column resourceMethod'>" . t('SOAP Action') . "</div>
			<div class='column ascendingSort resourcePathShort'>" . t('Path') . "</div>
			<div class='column resourceDesc'>" . t('Description') . "</div>
			<div class='column resourceRateLimit'>" . t('Rate Limit') . "</div>
		    </div>";
              }
              else {
                print "<div class='tableHeaderBackground clearTable'>
			<div class='column resourceMethod'>" . t('Verb') . "</div>
			<div class='column ascendingSort resourcePathShort'>" . t('Path') . "</div>
			<div class='column resourceDesc'>" . t('Description') . "</div>
			<div class='column resourceRateLimit'>" . t('Rate Limit') . "</div>
		    </div>";
              }
              print "<div class='resourceView resourcePlanView'>";
              foreach ($api['resources'] as $pathname => $path) {
                foreach ($path as $verb => $op) {
                  print "<div class='displayInlineTop resourceHeadline'>";
                  if (isset($api['protocol']) && $api['protocol'] == 'wsdl') {
                    // remove soap action from path
                    $dirs = explode('/', $pathname);
                    $unneeded = array_pop($dirs);
                    $pathname = join('/', $dirs);
                    if (!isset($op['soap-action'])) {
                      $op['soap-action'] = '';
                    }
                    if (!isset($op['description'])) {
                      $op['description'] = '';
                    }
                    print "<div class='displayInlineTop resourceMethod resourceMethodBadge'>" . check_plain($op['soap-action']) . "</div>
		                   <div class='displayInlineTop resourcePathShort boundedText' title='" . check_plain($pathname) . "'>" . check_plain($pathname) . "</div>";
                  }
                  else {
                    print "<div class='displayInlineTop resourceMethod resourceMethodBadge " . strtoupper(check_plain($verb)) . "'>" . strtoupper(check_plain($verb)) . "</div>
		                   <div class='displayInlineTop resourcePathShort boundedText' title='" . check_plain($pathname) . "'>" . check_plain($pathname) . "</div>";
                  }
                  print "<div class='displayInlineTop resourceDesc boundedText' title='" . check_plain($op['description']) . "'>" . check_plain($op['description']) . "</div>
		      <div class='displayInlineTop boundedText tableLabel'>" . t('Rate Limit:') . "</div>
		      <div class='displayInlineTop resourceRateLimit'>" . $op['rateLimit'] . "</div>";
                  print "</div>";
                }
              }
              print "</div></div></div>";
            }
          }
          print "</div></div>";
        }
      }
      else {
        print "<div>" . t('No subscriptions found. Why not browse the <a href="@product">available APIs</a>?', array('@product' => url('product'))) . "</div>";
      }
      print "</div>";
    }
    ?>
    <div <?php print $content_attributes; ?>>
      <?php
      hide($content['comments']);
      hide($content['links']);
      if (isset($content['field_applicationtags'])) {
        print render($content['field_applicationtags']);
      }
      ?>
    </div>

    <?php if ($links = render($content['links'])): ?>
      <nav <?php print $links_attributes; ?>><?php print $links; ?></nav>
    <?php endif; ?>

    <?php print render($content['comments']); ?>

</article>
