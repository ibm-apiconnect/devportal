<?php


namespace Drupal\ibm_apic_getting_started\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the Getting Started tutorials.
 */
class TutorialsController extends ControllerBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a TutorialsController object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * Get available tutorials.
   *
   * @return array
   *   Array of tutorial definitions.
   */
  protected function getTutorials() {
    return [
      'customize-portal-theme' => [
        'id' => 'customize-portal-theme',
        'title' => 'Customizing portal theme',
        'description' => 'Learn how to customize the portal theme for your Drupal site.',
        'template' => 'customizing_portal_theme',
        'image_dir' => 'theme',
      ], 
      'install-custom-module' => [
        'id' => 'install-custom-module',
        'title' => 'Installing custom module',
        'description' => 'Learn how to install a custom module in your Drupal site.',
        'template' => 'installing_custom_module',
        'image_dir' => 'installing-module',
      ],
      'customize-hero-image' => [
        'id' => 'customize-hero-image',
        'title' => 'Customizing hero image',
        'description' => 'Learn how to customize the hero image of your Portal site.',
        'template' => 'customizing_hero_image',
        'image_dir' => 'hero-image',
      ],
      'customize-front-page' => [
        'id' => 'customize-front-page',
        'title' => 'Customizing front page',
        'description' => 'Learn how to customize the front page of your Portal site.',
        'template' => 'customizing_front_page',
        'image_dir' => 'front-page',
      ],
      'taxonomy' => [
        'id' => 'taxonomy',
        'title' => 'Grouping products by category',
        'description' => 'Learn how to setup taxonomy in your Drupal site.',
        'template' => 'taxonomy',
        'image_dir' => 'taxonomy',
      ],
      'email-subscribers' => [
        'id' => 'email-subscribers',
        'title' => 'Emailing subscribers',
        'description' => 'Learn how to email all the subscribers of a specific API, plan, product, or all registered consumer organizations.',
        'template' => 'email-subscribers',
        'image_dir' => 'email-subscribers',
      ],
      'content-security-policy' => [
        'id' => 'content-security-policy',
        'title' => 'Enforcing content security policy',
        'description' => 'Learn how to enforce content security policy to support your specific security requirements.',
        'template' => 'content_security_policy',
        'image_dir' => 'content-security-policy',
      ],
    ];
  }

  /**
   * Get a specific tutorial by ID.
   *
   * @param string $tutorial_id
   *   The tutorial ID.
   *
   * @return array|null
   *   The tutorial definition or NULL if not found.
   */
  protected function getTutorial($tutorial_id) {
    $tutorials = $this->getTutorials();
    return isset($tutorials[$tutorial_id]) ? $tutorials[$tutorial_id] : NULL;
  }
  
  /**
   * Title callback for tutorial routes.
   *
   * @param string $tutorial_id
   *   The tutorial ID.
   *
   * @return string
   *   The tutorial title.
   */
  public function getTutorialTitle($tutorial_id = 'customize-portal-theme') {
    $tutorial = $this->getTutorial($tutorial_id);
    return $tutorial ? $tutorial['title'] : $this->t('Tutorial');
  }

  /**
   * Displays the Getting Started page.
   *
   * @return array
   *   A render array.
   */
  public function page() {
    $tutorials = $this->getTutorials();
    
    // Get the last viewed tutorial from tempstore
    $tempstore = $this->tempStoreFactory->get('ibm_apic_getting_started');
    $last_tutorial_id = $tempstore->get('last_tutorial_id');
    
    // If no tutorial is stored or the stored tutorial doesn't exist, use the first one
    if (!$last_tutorial_id || !isset($tutorials[$last_tutorial_id])) {
      $last_tutorial_id = key($tutorials);
    }
    
    $build = [
      '#theme' => 'getting_started_page',
      '#tutorials' => $tutorials,
      '#last_tutorial_id' => $last_tutorial_id,
      '#attached' => [
        'library' => [
          'ibm_apic_getting_started/getting_started',
        ],
        'drupalSettings' => [
          'ibmApicGettingStarted' => [
            'tutorials' => array_keys($tutorials),
            'lastTutorialId' => $last_tutorial_id,
          ],
        ],
      ],
    ];
    return $build;
  }

  /**
   * Content for a specific tutorial.
   *
   * @param string $tutorial_id
   *   The tutorial ID.
   *
   * @return array
   *   A render array.
   */
  public function content($tutorial_id = 'customize-portal-theme') {
    $tutorial = $this->getTutorial($tutorial_id);
    if (!$tutorial) {
      throw new NotFoundHttpException();
    }
    
    // Store the current tutorial ID in tempstore
    $tempstore = $this->tempStoreFactory->get('ibm_apic_getting_started');
    $tempstore->set('last_tutorial_id', $tutorial_id);
    
    $images_path = base_path() . \Drupal::service('extension.list.module')->getPath('ibm_apic_getting_started') . '/images/' . $tutorial['image_dir'];
    
    return [
      '#theme' => $tutorial['template'],
      '#tutorial' => $tutorial,
      '#images_path' => $images_path,
    ];
  }

  /**
   * Off-canvas panel content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $tutorial_id
   *   The tutorial ID.
   *
   * @return array
   *   A render array.
   */
  public function panel(Request $request, $tutorial_id = 'customize-portal-theme') {
    $tutorial = $this->getTutorial($tutorial_id);
    if (!$tutorial) {
      throw new NotFoundHttpException();
    }
    
    // Store the current tutorial ID in tempstore
    $tempstore = $this->tempStoreFactory->get('ibm_apic_getting_started');
    $tempstore->set('last_tutorial_id', $tutorial_id);
    
    $images_path = base_path() . \Drupal::service('extension.list.module')->getPath('ibm_apic_getting_started') . '/images/' . $tutorial['image_dir'];
    
    return [
      '#theme' => $tutorial['template'],
      '#tutorial' => $tutorial,
      '#images_path' => $images_path,
      '#attached' => [
        'library' => [
          'ibm_apic_getting_started/getting_started',
        ],
      ],
    ];
  }
}
