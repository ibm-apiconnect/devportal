/**
 * @file
 * Javascript to generate Stripe token in PCI-compliant way.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the apicStripeForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop object cardNumber
   *   Stripe card number element.
   * @prop object cardExpiry
   *   Stripe card expiry element.
   * @prop object cardCvc
   *   Stripe card cvc element.
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the apicStripeForm behavior.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the apicStripeForm behavior.
   *
   * @see Drupal.apicStripe
   */
  Drupal.behaviors.apicStripeForm = {
    cardNumber: null,
    cardExpiry: null,
    cardCvc: null,

    attach: function (context) {
      var self = this;
      if (!drupalSettings.apicStripe || !drupalSettings.apicStripe.publishableKey) {
        return;
      }
      $('.stripe-form', context).once('stripe-processed').each(function () {
        var $form = $(this).closest('form');

        // Create a Stripe client.
        /* global Stripe */
        try {
          var stripe = Stripe(drupalSettings.apicStripe.publishableKey);
        } catch (e) {
          $form.find('#payment-errors').html(Drupal.theme('apicStripeError', e.message));
          $form.find('button.form-submit').prop('disabled', true);
          $(this).find('.form-item').hide();
          return;
        }

        // Create an instance of Stripe Elements.
        var elements = stripe.elements();
        var classes = {
          base: 'form-text',
          invalid: 'error'
        };
        // Create instances of the card elements.
        self.cardNumber = elements.create('cardNumber', {
          classes: classes,
          placeholder: ''
        });
        self.cardExpiry = elements.create('cardExpiry', {
          classes: classes
        });
        self.cardCvc = elements.create('cardCvc', {
          classes: classes
        });
        // Add an instance of the card UI components into the "scard-element" element <div>
        self.cardNumber.mount('#card-number-element');
        self.cardExpiry.mount('#expiration-element');
        self.cardCvc.mount('#security-code-element');

        // Input validation.
        self.cardNumber.on('change', function (event) {
          stripeErrorHandler(event);
        });
        self.cardExpiry.on('change', function (event) {
          stripeErrorHandler(event);
        });
        self.cardCvc.on('change', function (event) {
          stripeErrorHandler(event);
        });

        // Helper to handle the Stripe responses with errors.
        var stripeErrorHandler = function (result) {
          if (result.error) {
            // Inform the user if there was an error.
            // Display the message error in the payment form.
            Drupal.apicStripe.displayError(result.error.message);

            // Allow the customer to re-submit the form.
            $form.find('button.form-submit').prop('disabled', false);
          }
          else {
            // Clean up error messages.
            $form.find('#payment-errors').html('');
          }
        };

        // Form submit.
        $form.on('submit', function (e) {
          if ($('#stripe-payment-method-id', $form).val().length > 0) {
            return true;
          }

          if (!observer) {
            var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
            var observer = new MutationObserver(function(mutations) {
                if ($('.page-load-progress-lock-screen').length > 0) {
                    $('.page-load-progress-lock-screen').remove();
                    $('body').css('overflow','inherit');
                    observer.disconnect();
                }
            });
          }

        //Don't create payment method title field is empty
        if (!$("input[name='title']").val()) {
            Drupal.apicStripe.displayError("Enter a title for your new payment method");
            stopLoading(observer);
            return false;
        }

          if (drupalSettings.apicStripe.clientSecret === null) {
            // Try to create the Stripe token and submit the form.
            stripe.createPaymentMethod('card', self.cardNumber).then(function (result) {
              if (result.error) {
                // Inform the user if there was an error.
                stripeErrorHandler(result);
                stopLoading(observer);

              } else {
                observer.disconnect();
                $('#stripe-payment-method-id', $form).val(result.paymentMethod.id);
                $form.find('button.form-submit').click();
              }
            });
          } else {
            stripe.handleCardSetup(drupalSettings.apicStripe.clientSecret, self.cardNumber).then(function (result) {
              if (result.error) {
                // Inform the user if there was an error.
                stripeErrorHandler(result);
                stopLoading(observer);

              }
              else {
                observer.disconnect();
                // Insert the payment method ID into the form so it gets submitted to
                // the server.
                // Set the Stripe token value.
                $('#stripe-payment-method-id', $form).val(result.setupIntent.payment_method);
                // Submit the form.
                $form.find('button.form-submit').click();
              }
            });
          }

          // Prevent the form from submitting with the default action.
          if ($('#card-number-element', $form).length) {
            return false;
          }
        });
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }
      var self = this;
      ['cardNumber', 'cardExpiry', 'cardCvc'].forEach(function (i) {
        if (self[i] && self[i].length > 0) {
          self[i].unmount();
          self[i] = null;
        }
      });
      var $form = $('.stripe-form', context).closest('form');
      if ($form.length === 0) {
        return;
      }
      $form.off('submit');
    }
  };

  function stopLoading(observer) {
    if ($('.page-load-progress-lock-screen').length > 0) {
        $('.page-load-progress-lock-screen').remove();
        $('body').css('overflow','inherit');
    } else {
        observer.observe(document.body, {
            childList: true, // observe direct children
            subtree: false, // and lower descendants too
            characterDataOldValue: false // pass old data to callback
        });
    }
  }

})(jQuery, Drupal, drupalSettings);
