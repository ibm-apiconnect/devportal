<?php
namespace Drupal\mail_subscribers\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mail_subscribers\Entity\EmailList;

/**
 * Provides a form to delete email lists
 */
class EmailListDeleteForm extends ConfirmFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected $emailList;

  /**
   * Constructs a ConditionalConfirmForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_subscribers_email_list_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $listId = NULL) {

    if (empty($listId)) {
      \Drupal::messenger()->addError($this->t('No email list provided to delete.'));

      $url = Url::fromRoute('mail_subscribers.settings')->toString();
      $response = new RedirectResponse($url);
      $response->send();
    }
    $list = EmailList::load($listId);
    if (empty($list)) {
      \Drupal::messenger()->addError($this->t('Failed to load email list.'));

      $url = Url::fromRoute('mail_subscribers.settings')->toString();
      $response = new RedirectResponse($url);
      $response->send();
    }
    $this->emailList = $list;

    $form = parent::buildForm($form, $form_state);

    $form['list'] = [
      '#type' => 'hidden',
      '#value' => $listId
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %title?', ['%title' => $this->emailList->title->value]);
  }

  public function getDescription() {
    return $this->t('Are you sure you want to delete this email list? This action cannot be undone.');
  }


  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mail_subscribers.settings'); // Redirect URL if cancelled.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle the deletion of selected entities directly.

      $this->emailList->delete();

      \Drupal::messenger()->addMessage($this->t('%title has been deleted.', ['%title' => $this->emailList->title->value]));

      // Redirect or perform additional actions if needed.
      $form_state->setRedirect('mail_subscribers.settings');
    }
}
