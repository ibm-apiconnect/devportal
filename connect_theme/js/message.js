/**
 * @file
 * message.js
 */
(function ($, Drupal) {

    /**
     * Retrieves the classes for a specific message type.
     *
     * @param {String} type
     *   The type of message.
     *
     * @return {String}
     *   The classes to add, space separated.
     */
    Drupal.Message.getMessageTypeClass = function (type) {
      var classes = this.getMessageTypeClasses();
      return 'alert alert-' + (classes[type] || 'success') + ' alert-dismissible';
    };

    /**
     * Helper function to map Drupal types to Bootstrap classes.
     *
     * @return {Object<String, String>}
     *   A map of classes, keyed by message type.
     */
    Drupal.Message.getMessageTypeClasses = function () {
      return {
        status: 'success',
        error: 'danger',
        warning: 'warning',
        info: 'info',
      };
    };

    /**
     * Retrieves a label for a specific message type.
     *
     * @param {String} type
     *   The type of message.
     *
     * @return {String}
     *   The message type label.
     */
    Drupal.Message.getMessageTypeLabel = function (type) {
      var labels = this.getMessageTypeLabels();
      return labels[type];
    };

    /**
     * @inheritDoc
     */
    Drupal.Message.getMessageTypeLabels = function () {
      return {
        status: Drupal.t('Status message'),
        error: Drupal.t('Error message'),
        warning: Drupal.t('Warning message'),
        info: Drupal.t('Informative message'),
      };
    };

    /**
     * Retrieves the aria-role for a specific message type.
     *
     * @param {String} type
     *   The type of message.
     *
     * @return {String}
     *   The message type role.
     */
    Drupal.Message.getMessageTypeRole = function (type) {
      var labels = this.getMessageTypeRoles();
      return labels[type];
    };

    /**
     * Map of the message type aria-role values.
     *
     * @return {Object<String, String>}
     *   A map of roles, keyed by message type.
     */
    Drupal.Message.getMessageTypeRoles = function () {
      return {
        status: 'success',
        error: 'danger',
        warning: 'warning',
        info: 'info',
      };
    };

    /**
     * Map of the message type with header values.
     *
     * @return {Object<String, String>}
     *   A map of headers, keyed by message type.
     */
    Drupal.theme.getMessageHeaders = function () {
      return {
        status: Drupal.t('Status message'),
        error: Drupal.t('Error message'),
        warning: Drupal.t('Warning message'),
        info: Drupal.t('Informative message'),
      };
    };

    /**
     * @inheritDoc
     */
    Drupal.theme.message = ({ text }, { type, id }) => {
      var wrapper = Drupal.theme('messageWrapper', id, type, text);
      console.log('Test  ' + wrapper);
      return wrapper;
    };

    /**
     * Themes the message container.
     *
     * @param {String} id
     *   The message identifier.
     * @param {String} type
     *   The type of message.
     *
     * @return {HTMLElement}
     *   A constructed HTMLElement.
     */
    Drupal.theme.messageWrapper = function (id, type, text) {
      var wrapper = document.createElement('div');
      var label = Drupal.Message.getMessageTypeLabel(type);
      wrapper.setAttribute('class', Drupal.Message.getMessageTypeClass(type));
      wrapper.setAttribute('role', Drupal.Message.getMessageTypeRole(type));
      wrapper.setAttribute('aria-label', label);
      wrapper.setAttribute('data-drupal-message-id', id);
      wrapper.setAttribute('data-drupal-message-type', type);

      var alertDeatils = Drupal.theme('messageLabel', type, text);
      wrapper.appendChild(alertDeatils);
      wrapper.appendChild(Drupal.theme('messageClose'));

      return wrapper;
    };

    /**
     * Themes the message close button.
     *
     * @return {HTMLElement}
     *   A constructed HTMLElement.
     */
    Drupal.theme.messageClose = function () {
      var element = document.createElement('a');
      element.setAttribute('href', '#');
      element.setAttribute('class', 'close');
      element.setAttribute('type', 'button');
      element.setAttribute('role', 'button');
      element.setAttribute('data-dismiss', 'alert');
      element.setAttribute('aria-label', Drupal.t('Close'));
      element.innerHTML = '<svg aria-label="' + Drupal.t('Close') + '" focusable="false" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" role="img" class="bx--inline-notification__close-icon"><path d="M24 9.4L22.6 8 16 14.6 9.4 8 8 9.4 14.6 16 8 22.6 9.4 24 16 17.4 22.6 24 24 22.6 17.4 16 24 9.4z"></path></svg>';
      return element;
    };

    /**
     * Themes the message container.
     *
     * @param {String} label
     *   The message label.
     *
     * @return {HTMLElement}
     *   A constructed HTMLElement.
     */
    Drupal.theme.messageLabel = function (type, text) {
      var detailsElement = document.createElement('div');
      detailsElement.setAttribute('class', 'alert-details');

      var iconElement = document.createElement('span');
      iconElement.setAttribute('class', 'icon icon-' + type);
      iconElement.setAttribute('aria-hidden', 'true');
      detailsElement.appendChild(iconElement);

      var alertWrapperElement = document.createElement('div');
      alertWrapperElement.setAttribute('class', 'alert-text-wrapper');

      var header = Drupal.theme.getMessageHeaders(type);
      if (header) {
        var messageHeader = document.createElement('h4');
        messageHeader.setAttribute('class', 'sr-only')
        messageHeader.innerText = header;
        alertWrapperElement.appendChild(messageHeader);
      }

      alertWrapperElement.innerHTML += text;

      detailsElement.appendChild(alertWrapperElement);

      return detailsElement;
    };

    /**
     * Themes the message contents.
     *
     * @param {String} html
     *   The message identifier.
     *
     * @return {HTMLElement}
     *   A constructed HTMLElement.
     */
    Drupal.theme.messageContents = function (html) {
      var element = document.createElement('p');
      element.innerHTML = '' + html;
      return element;
    }

  })(window.jQuery, window.Drupal);
