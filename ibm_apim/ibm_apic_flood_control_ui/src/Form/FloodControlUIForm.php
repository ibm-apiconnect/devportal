<?php  
/**  
 * @file  
 * Contains Drupal\ibm_apic_flood_control_ui\Form\FloodControlUIForm.  
 */  
namespace Drupal\ibm_apic_flood_control_ui\Form;  
use Drupal\Core\Form\ConfigFormBase;  
use Drupal\Core\Form\FormStateInterface;  

class FloodControlUIForm extends ConfigFormBase {  

  /**  
   * {@inheritdoc}  
   */  
  protected function getEditableConfigNames() {  
    return [  
        'user.flood',
        'ibm_apim.settings',
        'contact.settings'
    ];  
  }  

  /**  
   * {@inheritdoc}  
   */  
  public function getFormId() {  
    return 'ibm_apic_flood_control_ui_form';  
  }  

   /**  
   * {@inheritdoc}  
   */  
  public function buildForm(array $form, FormStateInterface $form_state) {  

    $form = parent::buildForm($form, $form_state);
    $flood_config = $this->config('user.flood');
    $ibm_config = $this->config('ibm_apim.settings');
    $contact_config = $this->config('contact.settings');

    $form['intro'] = [
      '#markup' => t('Here you can define the number of failed login attempts that are allowed for each time window before further attempts from a user, or a client IP address, are blocked for the duration of the time window. By default the site allows no more than five failed login attempts from the same user in a 6 hour period, and no more than 50 failed login attempts from the same client IP address in a 1 hour period. The site administrator can unblock users, and client IP addresses, before the end of the time window by using Configuration->Flood unblock. You can also configure a limit on the number of "Contact Us" emails that users can send in a specified time period.'),
      '#weight' => -20,
    ];

    $form['user'] = array(
        '#type' => 'fieldset',
        '#title' => t('Login Settings'),
        '#access' => \Drupal::currentUser()->hasPermission('administer users'),
    );
    $form['user']['ip_limit'] = array(
        '#type' => 'number',
        '#title' => t('Failed IP login limit (min 1)'),
        '#default_value' => $flood_config->get('ip_limit', 50),
        '#min' => 1,
    );
    $form['user']['ip_window'] = array(
        '#type' => 'number',
        '#title' => $this->t('Failed IP login window in seconds (0 = Off)'),
        '#default_value' => $flood_config->get('ip_window', 3600),
        '#min' => 0,
    );
    $form['user']['user_limit'] = array(
        '#type' => 'number',
        '#title' => t('Failed User login limit (min 1)'),
        '#default_value' => $flood_config->get('user_limit', 5),
        '#min' => 1,
    );
    $form['user']['user_window'] = array(
        '#type' => 'number',
        '#title' => t('Failed User login window in seconds (0 = Off)'),
        '#default_value' => $flood_config->get('user_window', 21600),
        '#min' => 0,
    );

    $form['contact'] = array(
        '#type' => 'fieldset',
        '#title' => t('Contact Forms'),
        '#access' => \Drupal::currentUser()->hasPermission('administer contact forms'),
    );
    $form['contact']['flood']['limit'] = array(
        '#type' => 'number',
        '#title' => t('Emails sent limit'),
        '#default_value' => $contact_config->get('flood.limit', 5),
        '#min' => 0,
    );
    $form['contact']['flood']['interval'] = array(
        '#type' => 'number',
        '#title' => t('Emails sent window in seconds'),
        '#default_value' => $contact_config->get('flood.interval', 3600),
        '#min' => 0,
    );

    return $form;

  }  

   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('user.flood')
      ->set('ip_limit', $form_state->getValue('ip_limit'))
      ->set('ip_window', $form_state->getValue('ip_window'))
      ->set('user_limit', $form_state->getValue('user_limit'))
      ->set('user_window', $form_state->getValue('user_window'))
      ->save();
    $this->config('contact.settings')
      ->set('flood.limit', $form_state->getValue('limit'))
      ->set('flood.interval', $form_state->getValue('interval'))
      ->save(); 
  }

} 
