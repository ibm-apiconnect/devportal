<?php


namespace Drupal\consumerorg\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display member user information.
 *
 * @ViewsField("member_user_field")
 */
class MemberUserField extends FieldPluginBase {

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

    // For invitations, show email
    if ($entity->isInvitation()) {
      $email = $entity->getEmail();
      return $email ? ['#markup' => $this->sanitizeValue($email)] : ['#markup' => '-'];
    }

    // For members, show user information
    if ($entity->isMember()) {
      $user_data = $entity->getUser();
      
      if ($user_data) {
        $user = json_decode($user_data, TRUE);
        
        if (isset($user['username'])) {
          return ['#markup' => $this->sanitizeValue($user['username'])];
        }
        
        if (isset($user['mail'])) {
          return ['#markup' => $this->sanitizeValue($user['mail'])];
        }
      }
      
      // Fallback to user_url if available
      $user_url = $entity->getUserUrl();
      if ($user_url) {
        // Extract username from URL if possible
        $parts = explode('/', $user_url);
        $username = end($parts);
        return ['#markup' => $this->sanitizeValue($username)];
      }
    }

    return ['#markup' => '-'];
  }

}
