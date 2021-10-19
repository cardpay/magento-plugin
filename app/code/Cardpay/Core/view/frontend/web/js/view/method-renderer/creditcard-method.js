define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/iframe',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-list',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'Magento_Customer/js/model/customer',
        'mage/translate',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/cart/cache',
        'CPv1'
    ],
    function ($, Component, quote, paymentService, paymentMethodList, getTotalsAction, fullScreenLoader, additionalValidators,
              setPaymentInformationAction, placeOrderAction, customer, $t, defaultTotal, cartCache) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/creditcard_method'
            },
            placeOrderHandler: null,
            validateHandler: null,
            redirectAfterPlaceOrder: false,
            initialGrandTotal: null,

            initApp: function () {
                var self = this;
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    CPv1.text.choose = $t('Choose');
                    CPv1.text.other_bank = $t('Other Bank');
                    CPv1.gateway_mode = window.checkoutConfig.payment[this.getCode()]['gateway_mode'];
                    CPv1.paths.loading = window.checkoutConfig.payment[this.getCode()]['loading_gif'];
                    CPv1.customer_and_card.default = false;
                }
            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            context: function () {
                return this;
            },

            getCode: function () {
                return 'cardpay_custom';
            },

            isActive: function () {
                return true;
            },

            getCardListCustomerCards: function () {
                return [];
            },

            existBanner: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    if (window.checkoutConfig.payment[this.getCode()]['bannerUrl'] != null) {
                        return true;
                    }
                }
                return false;
            },

            getBannerUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['bannerUrl'];
                }
                return '';
            },

            getGrandTotal: function () {
                return quote.totals().base_grand_total;
            },

            getInitialGrandTotal: function () {
                return quote.totals().base_subtotal
                    + quote.totals().base_shipping_incl_tax
                    + quote.totals().base_tax_amount
                    + quote.totals().base_discount_amount;
            },

            getBaseUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['base_url'];
                }
                return '';
            },

            getRoute: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['route'];
                }
                return '';
            },

            getCountry: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['country'];
                }
                return '';
            },

            getSuccessUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['success_url'];
                }
                return '';
            },

            getCustomer: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['customer'];
                }
                return '';
            },

            getLoadingGifUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['loading_gif'];
                }
                return '';
            },

            getMpGatewayMode: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['mp_gateway_mode'];
                }
                return 0;
            },

            isCpfRequired: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['is_cpf_required'] == 1;
                }

                return false;
            },

            getCardBrandsLogoURL: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['card_brands_logo_url'];
                }

                return '';
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['logoUrl'];
                }
                return '';
            },

            /**
             * @override
             */
            getData: function () {
                // data to Post in backend
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payment[method]': this.getCode(),
                        'card_expiration_month': document.querySelector(CPv1.selectors.cardExpirationMonth).value,
                        'card_expiration_year': document.querySelector(CPv1.selectors.cardExpirationYear).value,
                        'card_number': document.querySelector(CPv1.selectors.cardNumber).value,
                        'security_code': document.querySelector(CPv1.selectors.securityCode).value,
                        'card_holder_name': document.querySelector(CPv1.selectors.cardholderName).value,
                        'installments': document.querySelector(CPv1.selectors.installments).value,
                        'cpf': document.getElementById('cpf') ? document.querySelector(CPv1.selectors.cpf).value : null,
                        'total_amount': document.querySelector(CPv1.selectors.amount).value,
                        'amount': document.querySelector(CPv1.selectors.amount).value,
                        'site_id': this.getCountry(),
                        'token': document.querySelector(CPv1.selectors.token).value,
                        'payment_method_id': document.querySelector(CPv1.selectors.paymentMethodId).value,
                        'one_click_pay': document.querySelector(CPv1.selectors.CustomerAndCard).value,
                        'gateway_mode': document.querySelector(CPv1.selectors.MpGatewayMode).value,
                    }
                };
            },

            afterPlaceOrder: function () {
                window.location = this.getSuccessUrl();
            },

            validate: function () {
                return this.validateHandler();
            },

            hasErrors: function () {
                var allMessageErrors = jQuery('.mp-error');
                if (allMessageErrors.length > 1) {
                    for (var x = 0; x < allMessageErrors.length; x++) {
                        if ($(allMessageErrors[x]).css('display') !== 'none') {
                            return true
                        }
                    }
                } else {
                    if (allMessageErrors.css('display') !== 'none') {
                        return true
                    }
                }

                return false;
            },

            /**
             * Place order
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate() && !this.hasErrors()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            getPlaceOrderDeferredObject: function () {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },

            initialize: function () {
                this._super();
            },

            updateSummaryOrder: function () {
                cartCache.set('totals', null);
                defaultTotal.estimateTotals();
            },

            /*
             * Validation of the main fields to process a payment by credit card
             */
            onlyNumbersInCardNumber: function (t, evt) {
                var cardNumber = document.querySelector(CPv1.selectors.cardNumber);
                if (cardNumber.value.match(/[^0-9]/g)) {
                    cardNumber.value = cardNumber.value.replace(/[^0-9]/g, '');
                }
            },

            validateCreditCardNumber: function (a, b) {
                var self = this;
                self.hideError('E301');
            },

            validateExpirationDate: function (a, b) {
                var self = this;
                self.hideError('208');

                var expirationMonth = document.querySelector(CPv1.selectors.cardExpirationMonth).value;
                var expirationYear = document.querySelector(CPv1.selectors.cardExpirationYear).value;
                if (expirationMonth !== "" && expirationYear !== "") {
                    var currentTime = new Date()
                    var currentYear = currentTime.getFullYear();
                    var currentMonth = currentTime.getMonth() + 1;

                    if ((expirationYear == currentYear) && (expirationMonth < currentMonth)) {
                        self.showError('208');
                    }
                }
            },

            validateCardHolderName: function (a, b) {
                var self = this;
                self.hideError('316');
            },

            validateSecurityCode: function (a, b) {
                var self = this;
                self.hideError('E302');

                var securityCode = document.querySelector(CPv1.selectors.securityCode).value;
                if (securityCode !== "" && securityCode.length < 3) {
                    self.showError('E302');
                }
            },

            validateCpf: function (a, b) {
                var self = this;
                self.hideError('E303');

                var cpf = document.querySelector(CPv1.selectors.cpf).value;
                if (cpf !== "") {
                    if (cpf.includes('X') || !this.isValidCPF(cpf)) {
                        self.showError('E303');
                    }
                }
            },

            onlyNumbersInSecurityCode: function (t, evt) {
                var securityCode = document.querySelector(CPv1.selectors.securityCode);
                if (securityCode.value.match(/[^0-9]/g)) {
                    securityCode.value = securityCode.value.replace(/[^0-9]/g, '');
                }
            },

            applyInputMask: function (a, b) { //NOSONAR
                function doFormat(x, pattern, mask) {
                    var strippedValue = x.replace(/[^0-9]/g, "");
                    var chars = strippedValue.split('');
                    var count = 0;

                    var formatted = '';
                    for (var i = 0; i < pattern.length; i++) {
                        const c = pattern[i];
                        if (chars[count]) {
                            if (/\*/.test(c)) {
                                formatted += chars[count];
                                count++;
                            } else {
                                formatted += c;
                            }
                        } else if (mask) {
                            if (mask.split('')[i]) {
                                formatted += mask.split('')[i];
                            }
                        }
                    }

                    return formatted;
                }

                document.querySelectorAll('[data-mask]').forEach(function (e) {
                    function format(elem) {
                        const val = doFormat(elem.value, elem.getAttribute('data-format'));
                        elem.value = doFormat(elem.value, elem.getAttribute('data-format'), elem.getAttribute('data-mask'));

                        if (elem.createTextRange) {
                            var range = elem.createTextRange();
                            range.move('character', val.length);
                            range.select();
                        } else if (elem.selectionStart) {
                            elem.focus();
                            elem.setSelectionRange(val.length, val.length);
                        }
                    }

                    e.addEventListener('keyup', function () {
                        format(e);
                    });

                    e.addEventListener('keydown', function () {
                        format(e);
                    });

                    format(e)
                });
            },

            isValidCPF(cpf) {
                if (typeof cpf !== "string") {
                    return false
                }

                cpf = cpf.replace(/[\s.-]*/igm, '')
                if (
                    !cpf ||
                    cpf.length != 11 ||
                    cpf == "00000000000" ||
                    cpf == "11111111111" ||
                    cpf == "22222222222" ||
                    cpf == "33333333333" ||
                    cpf == "44444444444" ||
                    cpf == "55555555555" ||
                    cpf == "66666666666" ||
                    cpf == "77777777777" ||
                    cpf == "88888888888" ||
                    cpf == "99999999999"
                ) {
                    return false
                }

                var sum = 0
                var remainder
                for (var i = 1; i <= 9; i++) {
                    sum = sum + parseInt(cpf.substring(i - 1, i)) * (11 - i)
                }

                remainder = (sum * 10) % 11
                if ((remainder == 10) || (remainder == 11)) {
                    remainder = 0
                }

                if (remainder != parseInt(cpf.substring(9, 10))) {
                    return false
                }

                sum = 0
                for (var j = 1; j <= 10; j++) {
                    sum = sum + parseInt(cpf.substring(j - 1, j)) * (12 - j)
                }

                remainder = (sum * 10) % 11
                if ((remainder == 10) || (remainder == 11)) {
                    remainder = 0
                }

                return remainder == parseInt(cpf.substring(10, 11));
            },

            showError: function (code) {
                var $form = CPv1.getForm();
                var $span = $form.querySelector('#mp-error-' + code);
                $span.style.display = 'inline-block';
            },

            hideError: function (code) {
                var $form = CPv1.getForm();
                var $span = $form.querySelector('#mp-error-' + code);
                $span.style.display = 'none';
            }
        });
    }
);