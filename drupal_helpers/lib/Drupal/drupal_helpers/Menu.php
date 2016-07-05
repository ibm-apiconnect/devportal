<?php

namespace Drupal\drupal_helpers;

/**
 * Class Menu.
 *
 * @package Drupal\drupal_helpers
 */
class Menu {

  /**
   * Helper to add menu item into specified menu.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $new_item
   *   Array with items keys used in menu_link_save().
   * @param bool $unique
   *   Flag to check such item already exists and do not add the item. Check is
   *   made on both title and path meaning that if both exists - item will be
   *   updated and existing item mlid will be returned.
   *
   * @return int|bool
   *   'mlid' of the created menu item or FALSE.
   *
   * @see menu_link_save()
   */
  static public function addItem($menu_name, array $new_item, $unique = TRUE) {
    if (!isset($new_item['link_title']) || !isset($new_item['link_path'])) {
      return FALSE;
    }

    // If specified, find parent and make sure that it exists.
    if (!empty($new_item['plid'])) {
      if (!self::findItem($menu_name, ['mlid' => $new_item['plid']])) {
        return FALSE;
      }
    }

    $new_item['menu_name'] = $menu_name;
    $new_item['link_path'] = drupal_get_normal_path($new_item['link_path']);

    if ($unique) {
      // Search for item and return mlid if it was found.
      // Prepare a stub item to use for search - we are searching by title and
      // path only.
      $tmp_item = array_intersect_key($new_item, array_flip([
        'link_title',
        'link_path',
      ]));
      $mlid = self::findItem($menu_name, $tmp_item);
      if ($mlid) {
        $mlid = self::updateItem($menu_name, $tmp_item, $new_item);

        return $mlid;
      }
    }

    return menu_link_save($new_item);
  }

  /**
   * Helper function to update existing menu item.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array of menu item fields to search item. Items keys used in
   *   menu_link_save().
   * @param array $updates
   *   Array of menu item fields to be updated. Items keys used in
   *   menu_link_save().
   *
   * @see menu_link_save()
   *
   * @return bool
   *   Updated menu link id if update was successful or FALSE otherwise.
   */
  static public function updateItem($menu_name, array $existing_item, $updates = []) {
    $mlid = self::findItem($menu_name, $existing_item);
    if (!$mlid) {
      return FALSE;
    }

    $item = menu_link_load($mlid);

    foreach ($updates as $k => $v) {
      // Do not allow to overwrite mlid.
      if ($k == 'mlid') {
        continue;
      }
      if ($k == 'link_path') {
        $v = drupal_get_normal_path($v);
      }
      $item[$k] = $v;
    }

    return menu_link_save($item);
  }

  /**
   * Helper function to delete existing menu item.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array of menu item fields to search item. Items keys used in
   *   menu_link_save().
   *
   * @return bool
   *   Boolean TRUE if deletion was successful or FALSE otherwise.
   *
   * @see menu_link_save()
   */
  static public function deleteItem($menu_name, array $existing_item) {
    $mlid = self::findItem($menu_name, $existing_item);
    if (!$mlid) {
      return FALSE;
    }
    menu_link_delete($mlid);

    return (bool) self::findItem($menu_name, $existing_item) ? FALSE : TRUE;
  }

  /**
   * Helper function to find existing menu item.
   *
   * @param string $menu_name
   *   String machine menu name.
   * @param array $existing_item
   *   Array that is used to lookup existing menu item. Only first match will be
   *   used. All specified items must exists to return valid result:
   *   - link_title: String title to lookup. If plid is not specified, lookup
   *   is performed among all items.
   *   - link_path: Path of the menu item to lookup. Aliased path will be looked
   *   up and replaced with a system path. If plid is not specified - lookup is
   *   performed among all items.
   *   - mlid: Menu item link id.
   *   - plid: Menu item parent link id. If this is present lookup is performed
   *     within items with specified plid.
   *
   * @return int|bool
   *   Integer mlid if item was found ot FALSE otherwise.
   */
  static public function findItem($menu_name, array $existing_item) {
    // Init query.
    $query = db_select('menu_links', 'ml')
      ->fields('ml', ['mlid'])
      ->condition('menu_name', $menu_name);

    // Traverse through fields and add to conditions.
    foreach ($existing_item as $field_name => $field_value) {
      $field_value = $field_name == 'link_path' ? drupal_get_normal_path($field_value) : $field_value;
      $query->condition($field_name, $field_value);
    }
    // Execute query and fetch an item.
    $item = $query->execute()->fetchAssoc();

    return (isset($item['mlid']) && $item['mlid']) ? $item['mlid'] : FALSE;
  }

}
