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
drupal_add_library('system', 'ui.tooltip');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/mesh.css');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/product.css');
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/App.js', array(
  'weight' => 3
));
?>
<article id="node-<?php print $node->nid; ?>"
         class="<?php print $classes; ?> clearfix" <?php print $attributes; ?>>
  <div class="hamburger hidden"
       id="hamburger"><?php print t('App navigation'); ?></div>
  <div class="mesh-portal-product">
    <div class="application">
      <nav class="toc navigate-toc sticky stickyHeader">
        <ul>
          <li
            class="tocItem toc-breadcrumb"><i
              class="material-icons">arrow_back</i><?php print l(t('All Apps'), 'application'); ?>
          </li>
          <?php foreach ($all_apps as $applist_member) : ?>
            <?php if ($applist_member['nid'] == $node->nid) : ?>
              <li class="tocItem toc-app active">
                <span><?php print check_plain($applist_member['title']); ?></span>
              </li>
            <?php else: ?>
              <li class="tocItem toc-app"><a
                  href="<?php print url('node/' . $applist_member['nid']); ?>"><span><?php print check_plain($applist_member['title']); ?></span></a>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </nav>

      <?php print $unpublished; ?>

      <?php print render($title_prefix); ?>
      <?php $showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1); ?>
      <?php $applifecycle_enabled = variable_get('ibm_apim_applifecycle_enabled', 0); ?>

      <div class="appTitleBar"><h1 class="appTitle"><?php print $title; ?></h1>
        <?php if ($applifecycle_enabled == 1) : ?>
          <div
            class="applicationType <?php print drupal_html_class($application_type[0]['safe_value']); ?>"><?php print $application_type[0]['safe_value']; ?></div>
          <?php if (strtoupper($application_type[0]['safe_value']) == 'DEVELOPMENT') : ?>
            <?php if (!isset($application_promoteto[0]['safe_value'])) : ?>
              <div class="promotionLink"><?php print $promotelink; ?></div>
            <?php else: ?>
              <div class="promotionLink"><?php print t('Pending Upgrade') ?></div>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
        <div
          class="appTitleActions">
          <?php $analytics_enabled = variable_get('ibm_apim_show_analytics', 1);
          if ($analytics_enabled == 1) :?>
            <span class="analyticsApp"><?php print $analyticslink; ?></span>
          <?php endif; ?>
          <span
            class="notificationSettingsApp"><?php print $notificationsettingslink; ?></span>
          <span class="deleteApp"><?php print $deletelink; ?></span>
        </div>
      </div>

      <div class="portal">
        <?php if (isset($application_state[0]['safe_value']) && strtoupper($application_state[0]['safe_value']) == 'SUSPENDED') : ?>
          <div
            class="appSuspended"><?php print t('This application is currently suspended. The client ID is blocked from accessing APIs.'); ?></div>
        <?php endif; ?>

        <div class="apimTopSection">
          <div class="apimTopSectionHeading"><span
              class="apimTopSectionDetails"><?php print t('Details'); ?></span>
            <?php if (isset($isdev) && $isdev == TRUE) : ?>
              <span class="apimTopSectionEdit"><?php print $editlink; ?></span>
            <?php endif; ?>
          </div>
          <div class="apimImageAndTitle">
            <div class="apimAPIImage">
              <?php if (isset($content['application_image']) && !(empty($content['application_image']))) : ?>
                <?php print render($content['application_image']); ?>
              <?php elseif ($showplaceholders != 0) : ?>
                <div class="apimIcon">
                  <div
                    class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
                    <div class="field-items">
                      <figure class="clearfix field-item even">
                        <img typeof="foaf:Image" class="image-style-none"
                             src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/app/' . application_random_image($node->title)); ?>"
                             width="48" height="48" alt="">
                      </figure>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <span class="appNoImage"><?php print t("No Image"); ?></span>
              <?php endif; ?>
              <span class="apimImageActions"><?php print $uploadimagelink; ?>
                <?php if (isset($content['application_image']) && !(empty($content['application_image']))) : ?>
                <?php print $removeimagelink; ?></span>
            <?php endif; ?>
            </div>
            <div class="apimTitleSection">
              <div class="apimTitle">
                <h1><?php print $title; ?></h1>
              </div>
              <?php
              if (isset($content['field_applicationrating'])) {
                $content['field_applicationrating']['#label_display'] = 'hidden';
                print render($content['field_applicationrating']);
              }
              ?>
            </div>
          </div>
          <div class="apimInfoSection">
            <?php if (isset($application_description[0]['safe_value']) && !empty($application_description[0]['safe_value'])) : ?>
              <div class="section apimMainContent">
                <label
                  class="label apimField apimDescription"><?php print t('Description'); ?></label>
                <div
                  id="app_description"><?php print $application_description[0]['safe_value']; ?></div>
              </div>
            <?php endif; ?>

            <?php if (isset($application_oauthredirecturi[0]['safe_value']) && !empty($application_oauthredirecturi[0]['safe_value'])) : ?>
              <div class="section apimMainContent oauthRedirect">
                <label
                  class="label apimField apiOauthRedirectionURL"><?php print t('OAuth Redirection URL'); ?></label>
                <div id="app_oauth_redirecturi"
                     class="app_oauth_redirecturi"><?php print $application_oauthredirecturi[0]['safe_value']; ?></div>
              </div>
            <?php endif; ?>
            <?php if (isset($customfields) && is_array($customfields) && count($customfields) > 0) : ?>
              <div class="customFields application">
                <?php foreach ($customfields as $customfield) : ?>
                  <?php print render($content[$customfield]); ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (isset($isdev) && $isdev == TRUE): ?>
              <div class="credentialList clearBoth section apimMainContent">
                <div
                  class="credentialTitle"><?php print t('Credentials'); ?><span
                    class="addCredentials"><?php print $addcredentialslink; ?></span>
                </div>
                <?php $credcount = count($credentials);
                $index = 0; ?>
                <?php foreach ($credentials as $cred) : ?>
                  <?php drupal_add_js('jQuery(document).ready(function(){jQuery("#show-clientID' . $index . '").change(function(){
                jQuery("#clientID' . $index . '").hideShowPassword(jQuery(this).prop("checked"));
                }); });', 'inline');
                  $description = check_plain($cred['description']);
                  if (!isset($description) || empty($description)) {
                    $description = t('Default credentials');
                  } ?>
                  <div class="credentialTable">
                    <div class="credentialDescriptionRow">
                      <div
                        class="credentialDescription"><?php print $description; ?></div>
                      <div class="credentialActions">
                        <a
                          href="<?php print url("application/" . $application_apiid[0]['value'] . "/update-clientcreds/" . $cred['id']); ?>"
                          title="<?php print t('Edit'); ?>"><i
                            class="material-icons">mode_edit</i> <?php print t('Edit'); ?>
                        </a>
                        <?php if ($credcount > 1) : ?>
                          <a
                            href="<?php print url("application/" . $application_apiid[0]['value'] . "/delete-clientcreds/" . $cred['id']); ?>"
                            title="<?php print t('Delete'); ?>"><i
                              class="material-icons">delete</i> <?php print t('Delete'); ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="credentialContainer">
                      <div class="credentialInfo">
                        <label for="clientID<?php print $index; ?>"
                               class="label apimField apiClientID"><?php print t('Client ID'); ?></label>
                        <div id="app_client_id" class="app_client_id">
                          <input class="toggle-password"
                                 id="clientID<?php print $index; ?>"
                                 type="password" readonly
                                 value="<?php print $cred['client-id']; ?>"/>
                          <div class="passwordToggleContainer">
                            <input type="checkbox"
                                   id="show-clientID<?php print $index; ?>"/>
                            <label
                              for="show-clientID<?php print $index; ?>"><?php print t('Show'); ?></label>
                            <?php $ibm_apim_allow_clientidreset = variable_get('ibm_apim_allow_clientidreset', TRUE); ?>
                            <?php if ($ibm_apim_allow_clientidreset == 1): ?>
                            &nbsp;&nbsp;&nbsp;&nbsp; <a class="buttonLink"
                                                        href="<?php print url("application/" . $application_apiid[0]['value'] . "/reset-clientid/" . $cred['id']); ?>"><?php print t('Reset'); ?></a>
                            <?php endif; ?>
                          </div>
                        </div>
                        <label for="clientSecret<?php print $index; ?>"
                               class="label apimField apiClientSecret"><?php print t('Client Secret'); ?></label>
                        <div class="client_secret">
                          <input id="clientSecret<?php print $index; ?>"
                                 class="clientSecretInput" disabled readonly/>
                          <?php $ibm_apim_allow_clientsecretreset = variable_get('ibm_apim_allow_clientsecretreset', TRUE); ?>
                          <?php if ($ibm_apim_allow_clientsecretreset == 1): ?>
                          <a class="buttonLink"
                             href="<?php print url("application/" . $application_apiid[0]['value'] . "/verify/" . $cred['id']); ?>"><?php print t('Verify'); ?></a>
                          &nbsp;&nbsp;&nbsp;&nbsp; <a class="buttonLink"
                                                      href="<?php print url("application/" . $application_apiid[0]['value'] . "/reset-secret/" . $cred['id']); ?>"><?php print t('Reset'); ?></a>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <?php $index++; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          </div>
        </div>
        <?php if (module_exists('product')) : ?>
          <div class="apimMainContent apimPlansApis apimSubscriptionSection">
            <div class="apimSubscriptionSectionHeading">
              <span
                class="apimSubscriptionSectionDetails"><?php print t('Subscriptions'); ?></span>
            </div>
            <div class="subscriptionsWrapper">
              <?php if (is_array($subscriptions) && count($subscriptions) > 0) : ?>
                <?php foreach ($subscriptions as $plankey => $sub) : ?>
                  <?php //get correct plan info
                  $pieces = explode(":", $plankey);
                  $productname = $pieces[0];
                  $productversion = $pieces[1];
                  $planname = '(' . $pieces[2] . ')';

                  $pendingapp = '';
                  if ($sub['approved'] != TRUE) {
                    $pendingapp = ' (' . t('Pending Approval') . ')';
                  }
                  $unsubscribelink = '';
                  if (isset($isdev) && $isdev == TRUE) {
                    $unsubscribelink = '<a href="' . url('application/' . $application_apiid[0]['safe_value'] . '/unsubscribe/' . check_plain($sub['subid'])) . '"><i class="material-icons">layers_clear</i> ' . t('Unsubscribe') . '</a>';
                  }
                  $showversion = variable_get('ibm_apim_show_versions', 1);
                  $versiontext = '';
                  if ($showversion == 1) {
                    $versiontext = ' (' . $productversion . ')';
                  }
                  $moreinfolink = '<a href="' . url('node/' . $sub['productnid']) . '" title="' . t('View Details') . '"><i class="material-icons">info</i> ' . t('View Details') . '</a>';
                  ?>
                  <div class='planWrapper'>
                    <h3
                      class='appPlanName'><?php print check_plain($productname) . $versiontext . ' ' . $planname . ' ' . $pendingapp; ?>
                      <div class="planActions">
                        <span
                          class='planDetails'><?php print $moreinfolink; ?></span>
                        <span
                          class='planUnsubscribe'><?php print $unsubscribelink; ?></span>
                      </div>
                    </h3>
                    <?php if (isset($sub['superseded-by']) && !empty($sub['superseded-by']) && isset($isdev) && $isdev == TRUE) : ?>
                      <?php $parts = explode(':', $sub['superseded-by']);
                      $supersede_nid = product_get_nid_from_ref($parts[0] . ':' . $parts[1]);
                      ?>
                      <div class='migratePlanContainer'>
                        <div
                          class='migrateButton'><?php print l(t('Migrate'), 'application/' . $application_apiid[0]['safe_value'] . '/migrate/' . $sub['subid'] . '/' . ibm_apim_base64_url_encode($sub['superseded-by'])); ?></div><div
                          class='migratePlanText'><?php print t('A new version, <a href="@link">@newtitle</a>, has been published.', array(
                            '@newtitle' => $parts[0] . ' ' . $parts[1] . ' (' . $parts[2] . ')',
                            '@link' => url('node/' . $supersede_nid)
                          )); ?></div>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div><?php print t('No subscriptions found. Why not browse the <a href="@product">available APIs</a>?', array('@product' => url('product'))); ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
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
      </div>
    </div>
</article>
