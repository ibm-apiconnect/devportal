<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\apic_app\Kernel;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Drupal\Core\File\FileSystemInterface;

/**
 * Tests file_save_upload with a simulated image upload.
 *
 * @group your_module
 */
class UploadImageTest extends KernelTestBase {

  protected static $modules = [
    'file',
    'system',
    'user',
  ];

  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

  }

  public function testFakeImageUpload() {
    // Create a fake image file in memory.
    $tmpFilePath = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tmpFilePath, []);
    echo("STEP 1");
    // Create Symfony UploadedFile object.
    $uploadedFile = new UploadedFile(
      $tmpFilePath,
      'fake_image.jpg',
      'image/jpeg',
      null,
      true // Mark test mode to avoid file checks
    );
    $request = \Drupal::request();
    $request->files->set('files', ['image' => $uploadedFile]);
    // Ensure destination exists.
    $destination = 'temporary://fake_uploads';
    \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    // Run file_save_upload.
    $file = file_save_upload('image', [
      'FileIsImage' => [],
      'FileSizeLimit' => 2 * 1024 * 1024,
      'FileExtension' => ['jpg jpeg png gif'],
    ], $destination, NULL, FileSystemInterface::EXISTS_RENAME);
  }
}
