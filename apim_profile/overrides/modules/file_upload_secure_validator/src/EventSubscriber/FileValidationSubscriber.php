<?php

namespace Drupal\file_upload_secure_validator\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Drupal\file\Validation\FileValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;

/**
 * Subscribes to the file validation event to add ClamAV scanning.
 */
class FileValidationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

   /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the file upload secure validation service.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service object.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   The string translation service object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service object.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->loggerChannelFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * File validation function.
   *
   * @param \Drupal\file\Validation\FileValidationEvent $event
   *   The file validation event.
   */
  public function onFileValidate(FileValidationEvent $event) {
        $file = $event->file;
        $violations = $event->violations;

        // Get mime type from filename.
        $mimeByFilename = $file->getMimeType();
        // Get mime type from fileinfo.
        try {
          $mimeByFileinfo = (new FileinfoMimeTypeGuesser())->guessMimeType($file->getFileUri());
        }
        catch (InvalidArgumentException $e) {
            $message = $this->t('Caught an InvalidArgumentException; cannot validate missing file requested as @path', ['@path' => $file->getFileUri()]);
            $violation = new ConstraintViolation($message, $message, [], $file, '', $file);
            $violations->add($violation);
        }

        // Early exit, fileinfo agrees with the file's extension.
        if ($mimeByFilename === $mimeByFileinfo) {
          return;
        }

        // Check against known MIME types equivalence groups.
        $mimeTypesGroups = $this->configFactory->get('file_upload_secure_validator.settings')
          ->get('mime_types_equivalence_groups');
        // Exit when a mime-type equivalence pairing is found.
        foreach ($mimeTypesGroups as $mimeTypesGroup) {
          if (empty(array_diff(
            [
              $mimeByFilename,
              $mimeByFileinfo,
            ], $mimeTypesGroup))) {
            return;
          }
        }

        // Log disagreement.
        $this->loggerChannelFactory->get('file_upload_secure_validator')
          ->error("Error while uploading file: MimeTypeGuesser guessed '%mime_by_fileinfo' and fileinfo '%mime_by_filename'", [
            '%mime_by_fileinfo' => $mimeByFileinfo,
            '%mime_by_filename' => $mimeByFilename,
          ]);

        $message = $this->t("Error while uploading file: MimeTypeGuesser guessed '%mime_by_fileinfo' and fileinfo '%mime_by_filename'", [
            '%mime_by_fileinfo' => $mimeByFileinfo,
            '%mime_by_filename' => $mimeByFilename,
        ]);
        $violation = new ConstraintViolation($message, $message, [], $file, '', $file);
        $violations->add($violation);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FileValidationEvent::class => 'onFileValidate',
    ];
  }

}
