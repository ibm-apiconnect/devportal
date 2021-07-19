<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

function apim_profile_book_content() {
  $content = array(
    'basic.html' => t('<p>You can configure multiple aspects of the Developer Portal as an administrator.</p>
<p>To learn how to configure specific aspects of the Developer Portal, use the following pages:</p>'),
    'changing_the_site_name.html' => t('REPLACE_changing_the_site_name_REPLACE'),
    'chnage_site_slogan.html' => t('REPLACE_chnage_site_slogan_REPLACE'),
    'change_site_email.html' => t('REPLACE_change_site_email_REPLACE'),
    'change_logo.html' => t('REPLACE_change_logo_REPLACE'),
    'change_shortcut_icon.html' => t('REPLACE_change_shortcut_icon_REPLACE'),
    'banner_image_change.html' => t('REPLACE_banner_image_change_REPLACE'),
    'twitter_block.html' => t('REPLACE_twitter_block_REPLACE'),
    'edit_twitter_social_block.html' => t('REPLACE_edit_twitter_social_block_REPLACE'),
    'popular_product.html' => t('REPLACE_popular_product_REPLACE'),
    'follow_social_media_links.html' => t('REPLACE_follow_social_media_links_REPLACE'),
    'forum_moderation.html' => t('<p>One of the roles provided by default is the \'Forum Moderator\'. Users in this role can manage and maintain your community support on the site forums.</p>
<p>The pages in this section explain some of the actions that Forum Moderators can perform.</p>'),
    'create_new_forum.html' => t('REPLACE_create_new_forum_REPLACE'),
    'creating_new_forum_containers.html' => t('REPLACE_creating_new_forum_containers_REPLACE'),
    'configure_creation_new_forums_for_each_API.html' => t('REPLACE_configure_creation_new_forums_for_each_API_REPLACE'),
    'mark_content_as_sticky_in_forum.html' => t('REPLACE_mark_content_as_sticky_in_forum_REPLACE'),
    'locking_forum_topics.html' => t('REPLACE_locking_forum_topics_REPLACE'),
    'removing_forum_post.html' => t('REPLACE_removing_forum_post_REPLACE'),
    'make_forum_private.html' => t('REPLACE_make_forum_private_REPLACE'),
    'content_author.html' => t('<p>\'Content Authors\' can control and configure the content in the Developer Portal. Their actions include writing documentation and blog posts, but also ensuring that APIs and Products look their best and can be easily found by setting custom icons and taxonomy tags.</p>'),
    'adding_new_pages.html' => t('REPLACE_adding_new_pages_REPLACE'),
    'edit_sample_content.html' => t('REPLACE_edit_sample_content_REPLACE'),
    'edit_site_comment.html' => t('REPLACE_edit_site_comment_REPLACE'),
    'edit_tags_specific_item.html' => t('REPLACE_edit_tags_specific_item_REPLACE'),
    'post_new_blog_entries.html' => t('REPLACE_post_new_blog_entries_REPLACE'),
    'add_FAQs.html' => t('REPLACE_add_FAQs_REPLACE'),
    'post_poll.html' => t('REPLACE_post_poll_REPLACE'),
    'attach_files_to_api.html' => t('REPLACE_attach_files_to_api_REPLACE'),
    'embed_multimedia_in_site_content.html' => t('REPLACE_embed_multimedia_in_site_content_REPLACE'),
    'cloning_static_doc.html' => t('REPLACE_cloning_static_doc_REPLACE'),
    'configuring_layout.html' => t('<p>Everyone wants their site to look different. This might be to fit in with your organization\'s corporate branding, or just to look great.</p>
<p>You can customize the entire look and feel of the Developer Portal, and in the majority of cases, this can be easily done through the administrative web interface.</p>
<p>The site theme controls the colours of the site, but the layout of elements on the page and the pages themselves are configured separately. The pages below explain how.</p>'),
    'frontpage_configure.html' => t('REPLACE_frontpage_configure_REPLACE'),
    'main_menu_change.html' => t('REPLACE_main_menu_change_REPLACE'),
    'menu_add.html' => t('REPLACE_menu_add_REPLACE'),
    'block_change.html' => t('REPLACE_block_change_REPLACE'),
    'custom_pages.html' => t('REPLACE_custom_pages_REPLACE'),
    'adding_context.html' => t('REPLACE_adding_context_REPLACE'),
    'role_based_homepage.html' => t('REPLACE_role_based_homepage_REPLACE'),
    'configuring_content.html' => t('<p>You can customise the content that is stored in the Developer Portal. This could involve creating new content types, adding extra metadata to existing types, or setting up a custom review and approval workflow.</p>
<p>See the pages below for more information.</p>'),
    'create_content_type.html' => t('REPLACE_create_content_type_REPLACE'),
    'add_field.html' => t('REPLACE_add_field_REPLACE'),
    'carousel.html' => t('REPLACE_carousel_REPLACE'),
    'configure_appearance_ratings.html' => t('REPLACE_configure_appearance_ratings_REPLACE'),
    'configure_social_sharing_widget.html' => t('REPLACE_configure_social_sharing_widget_REPLACE'),
    'file_size_limitations.html' => t('REPLACE_file_size_limitations_REPLACE'),
    'support_ticket.html' => t('REPLACE_support_ticket_REPLACE'),
    'workbench_moderation.html' => t('REPLACE_workbench_moderation_REPLACE'),
    'using_workbench_moderation.html' => t('REPLACE_using_workbench_moderation_REPLACE'),
    'managing_users.html' => t('<p>The Developer Portal has a thorough permissions architecture. The pages in this section illustrate how it can be customised to add new roles and permissions.</p>'),
    'creating_and_customising_a_new_role.html' => t('REPLACE_creating_and_customising_a_new_role_REPLACE'),
    'create_a_new_role.html' => t('REPLACE_create_a_new_role_REPLACE'),
    'assigning_people_to_a_role.html' => t('REPLACE_assigning_people_to_a_role_REPLACE'),
    'assigning_permissions_to_a_new_role.html' => t('REPLACE_assigning_permissions_to_a_new_role_REPLACE'),
    'content_author_permissions.html' => t('REPLACE_content_author_permissions_REPLACE'),
    'user_reg_config.html' => t('REPLACE_user_reg_config_REPLACE'),
    'blocking_and_unblocking_specific_users.html' => t('REPLACE_blocking_and_unblocking_specific_users_REPLACE'),
    'managing_security.html' => t('<p>It\'s important to maintain a secure website and comply with all of the necessary rules in your organization. Many different security features are included in the Developer Portal and can be customised to suit individual requirements.</p>
<p>There are also very flexible options available for customizing the authentication mechanism your Developer Portal site uses. See the \'Portal Delegated User Registry\' section for more information.</p>'),
    'captcha_config.html' => t('REPLACE_captcha_config_REPLACE'),
    'tsandcs_config.html'=> t('REPLACE_tsandcs_config_REPLACE'),
    'blocked_ip_addresses.html' => t('REPLACE_blocked_ip_addresses_REPLACE'),
    'flood_control.html' => t('REPLACE_flood_control_REPLACE'),
    'session_timeout_config.html' => t('REPLACE_session_timeout_config_REPLACE'),
    'logout_refresh.html' => t('REPLACE_logout_refresh_REPLACE'),
    'pword_policy_config.html' => t('REPLACE_pword_policy_config_REPLACE'),
    'password_reset_link.html' => t('REPLACE_password_reset_link_REPLACE'),
    'no_autocomplete.html' => t('REPLACE_no_autocomplete_REPLACE'),
    'restricting_users.html' => t('REPLACE_restricting_users_REPLACE'),
    'two_factor_auth.html' => t('REPLACE_two_factor_auth_REPLACE'),
    'security_questions.html' => t('REPLACE_security_questions_REPLACE'),
    'misc_security.html' => t('REPLACE_misc_security_REPLACE'),
    'pdur.html' => t('REPLACE_pdur_REPLACE'),
    'social_login.html' => t('REPLACE_social_login_REPLACE'),
    'application_settings_facebook.html' => t('REPLACE_application_settings_facebook_REPLACE'),
    'application_settings_google.html' => t('REPLACE_application_settings_google_REPLACE'),
    'application_settings_twitter.html' => t('REPLACE_application_settings_twitter_REPLACE'),
    'application_settings_linkedin.html' => t('REPLACE_application_settings_linkedin_REPLACE'),
    'application_settings_slack.html' => t('REPLACE_application_settings_slack_REPLACE'),
    'application_settings_windows_live.html' => t('REPLACE_application_settings_windows_live_REPLACE'),
    'application_settings_github.html' => t('REPLACE_application_settings_github_REPLACE'),
    'configure_ldap.html' => t('REPLACE_configure_ldap_REPLACE'),
    'account_approval.html' => t('REPLACE_account_approval_REPLACE'),
    'modify_emails.html' => t('REPLACE_modify_emails_REPLACE'),
    'extending.html' => t('<p>If you have very specific requirements for your Developer Portal site, it becomes necessary to extend the provided functionality. This can be achieved simply with easy to use well documented programmatic APIs.</p>
<p>The pages in this section detail how to install additional contributed modules and themes, as well as how to write your own.</p>'),
    'custom_modules.html' => t('REPLACE_custom_modules_REPLACE'),
    'additional_modules.html' => t('REPLACE_additional_modules_REPLACE'),
    'disable_module.html' => t('REPLACE_disable_module_REPLACE'),
    'install_adiitional_themes.html' => t('REPLACE_install_adiitional_themes_REPLACE'),
    'override_content_types.html' => t('REPLACE_override_content_types_REPLACE'),
    'tutorial_portal_customizing_css.html' => t('REPLACE_tutorial_portal_customizing_css_REPLACE')
  );


  return $content;
}
