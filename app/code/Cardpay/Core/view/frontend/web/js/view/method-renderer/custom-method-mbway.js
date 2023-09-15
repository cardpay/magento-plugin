define(
    [
        'Cardpay_Core/js/view/method-renderer/custom-method-common'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_mbway',
                paymentReady: false,
                form_selector: '#cardpay-form-mbway',
                error_selector: '#mp-error-',
            },
            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'cardpay_mbway';
            },

            getMbWayLogoURL: function () {
                return this.getCheckoutConfigParam('mbway_logo_url', '');
            },

            context: function () {
                return this;
            },

            placeOrder: function (data, event) {
                var self = this;
                if (event) {
                    event.preventDefault();
                }
                if (this.validateMbWayPhone()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(function () {
                            if (self.getApiAccessMode() === 'pp') {
                                self.redirectFunc('mbway');
                            } else {
                                self.afterPlaceOrder();
                            }
                        }
                    );
                    return true;
                }

                return false;
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'phone_number': document.getElementById('mbWayPhone') ? document.querySelector('#mbWayPhone').value : null
                    }
                };
            },

            validateMbWayPhone: function () {
                var self = this;
                var phoneField = document.getElementById('mbWayPhone');

                phoneField.value = phoneField.value.replace(/[^+\d]/g, '');

                var numbersOnly = phoneField.value.trim();
                var phonePattern = /^\+?351\d{9}$|^(?!.*[a-zA-Z])\d{9}$/;

                if (numbersOnly === '') {
                    self.hideErrorSecond(101);
                    self.showError(100);
                    return false;
                }

                if (!numbersOnly.match(phonePattern)) {
                    self.hideErrorMbway(100);
                    self.showErrorsecond(101);
                    return false;
                }

                self.hideErrorMbway(100);
                self.hideErrorSecond(101);
                return true;
            },

            showError: function (code) {
                var $form = document.querySelector(this.form_selector);
                var $span = $form.querySelector(this.error_selector + code);
                $span.style.display = 'inline-block';
            },

            hideErrorMbway: function (code) {
                var $form = document.querySelector(this.form_selector);
                var $span = $form.querySelector(this.error_selector + code);
                $span.style.display = 'none';
            },

            showErrorsecond: function (code) {
                var $form = document.querySelector(this.form_selector);
                var $span = $form.querySelector(this.error_selector + code + '-second');
                $span.style.display = 'inline-block';
            },

            hideErrorSecond: function (code) {
                var $form = document.querySelector(this.form_selector);
                var $span = $form.querySelector(this.error_selector + code + '-second');
                $span.style.display = 'none';
            },

        });
    }
);
