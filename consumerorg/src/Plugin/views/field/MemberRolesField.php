<?php


namespace Drupal\consumerorg\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Component\Utility\Html;

/**
 * Field handler to display member roles.
 *
 * @ViewsField("member_roles_field")
 */
class MemberRolesField extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The consumer org service.
   *
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected $consumerOrgService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->consumerOrgService = $container->get('ibm_apim.consumerorg');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No query changes needed - we'll process the data in render
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    
    if (!$entity) {
      return '';
    }

    $role_urls_json = $entity->getRoleUrls();
    
    if (empty($role_urls_json)) {
      return ['#markup' => '-'];
    }

    $role_urls = json_decode($role_urls_json, TRUE);
    
    if (!is_array($role_urls) || empty($role_urls)) {
      return ['#markup' => '-'];
    }

    // Get org URL to fetch roles
    $org_url = $entity->getOrgUrl();
    $all_roles = [];
    
    if ($org_url) {
      $roles_array = $this->consumerOrgService->getRoles($org_url);
      if ($roles_array) {
        foreach ($roles_array as $role) {
          $all_roles[$role->getUrl()] = $role;
        }
      }
    }
    
    $role_items = [];
    $identifier = $entity->isInvitation() ? $entity->getEmail() : NULL;

    if (!$identifier && $entity->isMember()) {
      $user_data = $entity->getUser();
      if ($user_data) {
        $user = json_decode($user_data, TRUE);
        $identifier = $user['username'] ?? $user['mail'] ?? NULL;
      }

      if (!$identifier) {
        $identifier = $entity->getUserUrl() ? basename($entity->getUserUrl()) : NULL;
      }
    }

    foreach ($role_urls as $role_url) {
      if (isset($all_roles[$role_url])) {
        $role = $all_roles[$role_url];
        $role_name = $role->getTitle() ?? $role->getName() ?? basename($role_url);
      } else {
        // Fallback to extracting name from URL
        $role_name = basename($role_url);
      }

      $item = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->sanitizeValue($role_name),
        '#attributes' => [],
      ];

      if ($identifier) {
        $item['#attributes']['class'][] = Html::getClass('apicmyorgmemberrole-' . $identifier . '-' . strtolower($role_name));
      }

      $role_items[] = $item;
    }

    if (empty($role_items)) {
      return ['#markup' => '-'];
    }

    $build = [];
    foreach ($role_items as $index => $item) {
      if ($index > 0) {
        $build[] = ['#markup' => ', '];
      }
      $build[] = $item;
    }

    return $build;
  }

}
