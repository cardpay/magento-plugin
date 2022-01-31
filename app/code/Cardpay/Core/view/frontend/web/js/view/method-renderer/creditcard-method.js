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
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
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
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    if (window.checkoutConfig.payment[this.getCode()]['bannerUrl'] != null) {
                        return true;
                    }
                }
                return false;
            },

            getBannerUrl: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
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
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['base_url'];
                }
                return '';
            },

            getRoute: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['route'];
                }
                return '';
            },

            getCountry: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['country'];
                }
                return '';
            },

            getSuccessUrl: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['success_url'];
                }
                return '';
            },

            getCustomer: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['customer'];
                }
                return '';
            },

            getLoadingGifUrl: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['loading_gif'];
                }
                return '';
            },

            getMpGatewayMode: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['mp_gateway_mode'];
                }
                return 0;
            },

            isCpfRequired: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return parseInt(window.checkoutConfig.payment[this.getCode()]['is_cpf_required']) === 1;
                }

                return false;
            },

            areInstallmentsEnabled: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return parseInt(window.checkoutConfig.payment[this.getCode()]['are_installments_enabled']) === 1;
                }

                return false;
            },

            getCardBrandsLogoURL: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['card_brands_logo_url'];
                }

                return '';
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
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
                        'card_expiration_date': document.querySelector(CPv1.selectors.cardExpirationDate).value,
                        'card_number': document.querySelector(CPv1.selectors.cardNumber).value,
                        'security_code': document.querySelector(CPv1.selectors.securityCode).value,
                        'card_holder_name': document.querySelector(CPv1.selectors.cardholderName).value,
                        'installments': document.getElementById('installments') ? document.querySelector(CPv1.selectors.installments).value : null,
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
                if (cardNumber.value.match(/[^\d]/g)) {
                    cardNumber.value = cardNumber.value.replace(/[^\d]/g, '');
                }
            },

            luhnValidation: function (cardNumber) {
                let digit, odd, sum, _i, _len;
                odd = true;
                sum = 0;
                const digits = (cardNumber + '').split('').reverse();

                for (_i = 0, _len = digits.length; _i < _len; _i++) {
                    digit = digits[_i];
                    digit = parseInt(digit, 10);
                    odd = !odd
                    if (odd) {
                        digit *= 2;
                    }
                    if (digit > 9) {
                        digit -= 9;
                    }
                    sum += digit;
                }

                return (sum % 10 === 0);
            },

            validateCreditCardNumber: function (a, b) {
                const cardBrands = [
                    {
                        cbType: "visa",
                        pattern: /^4/,
                        cnLength: [13, 14, 15, 16, 19],
                    },
                    {
                        cbType: "mir",
                        pattern: /^220[0-4][\d]+/,
                        cnLength: [16, 17, 18, 19],
                    },
                    {
                        cbType: "discover",
                        pattern: /^(60110\d|6011[2-4]\d|601174|60117[7-9]|6011[8-9][4-9]|644\d\d\d|65\d\d\d\d|64[4-9]\d+|369989)/,
                        cnLength: [16, 17, 18, 19],
                    },
                    {
                        cbType: "dinersclub",
                        pattern: /^(30[0-5]\d\d\d|3095\d\d|3[8-9]\d\d\d\d)/,
                        cnLength: [16, 17, 18, 19],
                    }, {
                        cbType: "dinersclub",
                        pattern: /^(36\d\d\d\d)/,
                        cnLength: [14, 15, 16, 17, 18, 19],
                    },
                    {
                        cbType: "amex",
                        pattern: /^3[47]/,
                        cnLength: [15],
                    },
                    {
                        cbType: "jcb",
                        pattern: /^(((352[8-9][0-9][0-9])|(35[3-8][0-9][0-9][0-9]))|((30[8-9][8-9][0-9][0-9])|309[0-4][0-9][0-9])|((309[6-9][0-9][0-9])|310[0-2][0-9][0-9])|(311[2-9][0-9][0-9])|(3120[0-9][0-9])|(315[8-9][0-9][0-9])|((333[7-9][0-9][0-9])|(334[0-9][0-9][0-9])))/,
                        cnLength: [16, 17, 18, 19],
                    },
                    {
                        cbType: "unionpay",
                        pattern: /^(62|9558|81)/,
                        cnLength: [13, 14, 15, 16, 17, 18, 19],
                    },
                    {
                        cbType: "elo",
                        pattern: /^(50(67(0[78]|1[5789]|2[012456789]|3[01234569]|4[0-7]|53|7[4-8])|9(0(0[0123478]|14|2[0-2]|3[359]|4[01235678]|5[1-9]|6[0-9]|7[0134789]|8[04789]|9[12349])|1(0[34568]|4[6-9]|83)|2(20|5[7-9]|6[0-6])|4(0[7-9]|1[0-2]|31)|7(22|6[5-9])))|4(0117[89]|3(1274|8935)|5(1416|7(393|63[12])))|6(27780|36368|5(0(0(3[12356789]|4[0-9]|5[01789]|6[01345678]|7[78])|4(0[6-9]|1[0-3]|2[2-6]|3[4-9]|8[5-9]|9[0-9])|5(0[012346789]|1[0-9]|2[0-9]|3[0-8]|7[7-9]|8[0-9]|9[0-8])|72[0-7]|9(0[1-9]|1[0-9]|2[0128]|3[89]|4[6-9]|5[045]|6[25678]|71))|16(5[2-9]|6[0-9]|7[01456789])|50(0[0-9]|1[0-9]|2[1-9]|3[0-6]|5[1-7]))))/,
                        cnLength: [13, 16, 19],
                    },
                    {
                        cbType: "mastercard",
                        pattern: /^5[1-5]|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))/,
                        cnLength: [16],
                    },
                    {
                        cbType: "maestro",
                        pattern: /^(0604|50|5[6789]|60|61|63|64|67|6660|6670|6818|6858|6890|6901|6907)/,
                        cnLength: [12, 13, 14, 15, 16, 17, 18, 19],
                    },
                ];

                var self = this;
                self.hideError('card-number');

                const cardBrandSpan = document.getElementById('card-brand');
                cardBrandSpan.removeAttribute('class');

                const cardNumberInputField = document.querySelector(CPv1.selectors.cardNumber);
                if (cardNumberInputField === null || typeof cardNumberInputField === 'undefined') {
                    return;
                }

                const cardNumber = cardNumberInputField.value;
                if (cardNumber === null || typeof cardNumber === 'undefined') {
                    return;
                }

                let isCardNumberValid = true;
                let isCardBrandDetected = false;
                for (let cardBrandIndex = 0; cardBrandIndex <= cardBrands.length - 1; cardBrandIndex++) {
                    const cardBrand = cardBrands[cardBrandIndex];
                    if (cardNumber.match(cardBrand.pattern)) {
                        isCardBrandDetected = true;
                        if (!cardBrand.cnLength.includes(cardNumber.length) || !this.luhnValidation(cardNumber)) {
                            isCardNumberValid = false;
                        }

                        cardBrandSpan.className = 'card-brand-' + cardBrand.cbType;
                        break;
                    }
                }

                // unknown card brand
                if (!isCardBrandDetected && (cardNumber.length < 13 || cardNumber.length > 19 || !this.luhnValidation(cardNumber))) {
                    isCardNumberValid = false;
                }

                if (!isCardNumberValid) {
                    self.showError('card-number');
                }
            },

            formatCardExpirationDate: function (a, b) {
                const expirationDateObj = document.querySelector(CPv1.selectors.cardExpirationDate);
                if (!expirationDateObj || !expirationDateObj.value) {
                    return;
                }

                expirationDateObj.value = expirationDateObj.value.replace(/\D/g, '')
                    .replace(/(\d{2})(\d)/, "$1/$2")
                    .replace(/(\d{2})(\d{2})$/, "$1$2");
            },

            validateCardExpirationDate: function (a, b) {
                const self = this;
                const errorCode = '209';
                self.hideError(errorCode);

                const expirationValues = document.querySelector(CPv1.selectors.cardExpirationDate).value.split('/');
                if (typeof expirationValues[0] === 'undefined' || typeof expirationValues[1] === 'undefined') {
                    self.showError(errorCode);
                    return;
                }

                const expirationMonth = parseInt(expirationValues[0]);
                const expirationYear = parseInt(expirationValues[1]);
                if (expirationMonth < 1 || expirationMonth > 12) {
                    self.showError(errorCode);
                    return;
                }

                const currentTime = new Date()
                const currentYear = currentTime.getFullYear();
                const currentMonth = currentTime.getMonth() + 1;

                if (expirationYear < currentYear
                    || (expirationYear > currentYear + 40)
                    || (expirationYear === currentYear && expirationMonth < currentMonth)) {
                    self.showError(errorCode);
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
                if (securityCode.value.match(/[^\d]/g)) {
                    securityCode.value = securityCode.value.replace(/[^\d]/g, '');
                }
            },

            applyInputMask: function (a, b) { //NOSONAR
                function doFormat(x, pattern, mask) {
                    var strippedValue = x.replace(/[^\d]/g, "");
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
                    cpf.length !== 11 ||
                    cpf === "00000000000" ||
                    cpf === "11111111111" ||
                    cpf === "22222222222" ||
                    cpf === "33333333333" ||
                    cpf === "44444444444" ||
                    cpf === "55555555555" ||
                    cpf === "66666666666" ||
                    cpf === "77777777777" ||
                    cpf === "88888888888" ||
                    cpf === "99999999999"
                ) {
                    return false
                }

                let sum = 0
                let remainder
                for (let i = 1; i <= 9; i++) {
                    sum = sum + parseInt(cpf.substring(i - 1, i)) * (11 - i)
                }

                remainder = parseInt(sum * 10) % 11
                if ((remainder === 10) || (remainder === 11)) {
                    remainder = 0
                }

                if (remainder !== parseInt(cpf.substring(9, 10))) {
                    return false
                }

                sum = 0
                for (let j = 1; j <= 10; j++) {
                    sum = sum + parseInt(cpf.substring(j - 1, j)) * (12 - j)
                }

                remainder = parseInt((sum * 10) % 11)
                if ((remainder === 10) || (remainder === 11)) {
                    remainder = 0
                }

                return remainder === parseInt(cpf.substring(10, 11));
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