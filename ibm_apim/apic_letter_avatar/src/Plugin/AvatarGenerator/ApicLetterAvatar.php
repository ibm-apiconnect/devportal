<?php

/**
 * @file
 * Contains \Drupal\apic_letter_avatar\Plugin\AvatarGenerator\LetterAvatar.
 */

namespace Drupal\apic_letter_avatar\Plugin\AvatarGenerator;

use Drupal\avatars\Plugin\AvatarGenerator\AvatarGeneratorBase;
use Drupal\Core\Session\AccountInterface;
use LasseRafn\InitialAvatarGenerator\InitialAvatar;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Gravatar avatar generator.
 *
 * @AvatarGenerator(
 *   id = "apic_letter_avatar",
 *   label = @Translation("APIC Letter Avatar"),
 *   description = @Translation("Letter generated by the ApicLetterAvatar library."),
 *   fallback = TRUE,
 *   dynamic = FALSE,
 *   remote = TRUE
 * )
 */
class ApicLetterAvatar extends AvatarGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function getFile(AccountInterface $account) {
    $directory = 'public://avatar_kit/ak_letter';
    $strJsonFileContents = file_get_contents(\Drupal::service('extension.list.module')->getPath('apic_letter_avatar') . "/src/Plugin/AvatarGenerator/colors.json");
    $arrColors = json_decode($strJsonFileContents, true);
    if (\Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY)) {
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');

      $path = $directory . '/' . $account->id() . '.png';

      // todo: update existing file entity
      // if you update a file on the file system directly, page caches and image
      // styles will not flush.
      $ids = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->getQuery()
        ->condition('uri', $path)
        ->execute();

      if ($id = reset($ids)) {
        $file = File::load($id);
        $file->delete();
      }

      $colorToUse = rand(0, count($arrColors) - 1);

      $avatar = new InitialAvatar();
      $image = $avatar
      ->name($account->getAccountName())
      ->length(1)
      ->size(125)
      ->fontSize(0.8)
      ->background($arrColors[$colorToUse]['b'])
      ->color($arrColors[$colorToUse]['t'])
      ->generate();
      $image->save($file_system->realpath($path));

      // File cannot chain methods.
      $file = File::create();
      $file->setFileUri($path);
      $file->setOwnerId($account->id());
      // Temporary until AvatarPreview adds usage.
      $file->setTemporary();
      return $file;
    }

    return NULL;
  }

}