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
            redirectAfterPlaceOrder: true,
            initialGrandTotal: null,
            iframePadding: 40,
            maxIframeWidth: 1000,
            error_selector: '#mp-error-',

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

            getCheckoutConfigParam: function (key, defaultValue) {
                if (typeof window.checkoutConfig.payment[this.getCode()] === 'undefined') {
                    return defaultValue;
                }
                return window.checkoutConfig.payment[this.getCode()][key];
            },
            existBanner: function () {
                return (this.getCheckoutConfigParam('bannerUrl', null) !== null);
            },

            getBannerUrl: function () {
                return this.getCheckoutConfigParam('bannerUrl', '');
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
                return this.getCheckoutConfigParam('base_url', '');
            },

            getRoute: function () {
                return this.getCheckoutConfigParam('route', '');
            },

            getCountry: function () {
                return this.getCheckoutConfigParam('country', '');
            },

            getSuccessUrl: function () {
                return this.getCheckoutConfigParam('success_url', '');
            },

            getCustomer: function () {
                return this.getCheckoutConfigParam('customer', '');
            },

            getLoadingGifUrl: function () {
                return this.getCheckoutConfigParam('loading_gif', '');
            },

            getMpGatewayMode: function () {
                return this.getCheckoutConfigParam('mp_gateway_mode', 0);
            },

            getApiAccessMode: function () {
                return this.getCheckoutConfigParam('api_access_mode', 'gateway');
            },

            isCpfRequired: function () {
                return this.getCheckoutConfigParam('is_cpf_required', false);
            },

            areInstallmentsEnabled: function () {
                return parseInt(this.getCheckoutConfigParam('are_installments_enabled', 0)) === 1;
            },

            getCardBrandsLogoURL: function () {
                return this.getCheckoutConfigParam('card_brands_logo_url', '');
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                return this.getCheckoutConfigParam('logoUrl', '');
            },
            safeGetData: function (selector) {
                const el = jQuery(selector);
                return ('object' === typeof el) ? el.val() : null;
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
                        'card_expiration_date': this.safeGetData(CPv1.selectors.cardExpirationDate),
                        'card_number': this.safeGetData(CPv1.selectors.cardNumber),
                        'security_code': this.safeGetData(CPv1.selectors.securityCode),
                        'card_holder_name': this.safeGetData(CPv1.selectors.cardholderName),
                        'installments': this.safeGetData(CPv1.selectors.installments),
                        'cpf': this.safeGetData(CPv1.selectors.cpf),
                        'total_amount': this.safeGetData(CPv1.selectors.amount),
                        'amount': this.safeGetData(CPv1.selectors.amount),
                        'site_id': this.getCountry(),
                        'token': this.safeGetData(CPv1.selectors.token),
                        'payment_method_id': this.safeGetData(CPv1.selectors.paymentMethodId),
                        'one_click_pay': this.safeGetData(CPv1.selectors.CustomerAndCard),
                        'gateway_mode': this.safeGetData(CPv1.selectors.MpGatewayMode),
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
                const allMessageErrors = jQuery('.mp-error');
                if (allMessageErrors.length > 1) {
                    for (let x = 0; x < allMessageErrors.length; x++) {
                        if (jQuery(allMessageErrors[x]).is(':visible')) {
                            return true;
                        }
                    }
                } else {
                    if (jQuery(allMessageErrors).is(':visible')) {
                        return true;
                    }
                }

                return false;
            },
            initModalSize: function () {
                const self = this;
                jQuery(window).resize(function () {
                    self.setModalSize();
                });
                self.setModalSize();
            },
            setModalSize: function () {
                const backWindow = jQuery('#unlimit_modal_page');
                const w = jQuery(window).width();
                const {iframePadding, maxIframeWidth} = this;

                const marginTop = 40;
                const marginBottom = 20;

                const newWidth = Math.min(w - iframePadding, maxIframeWidth);
                const margin = Math.round((w - newWidth) / 2);

                backWindow.css({
                    'background': '#FFF',
                    'max-height': '800px',
                    'height': '100%',
                    'border-radius': '10px',
                    'padding': '10px',
                    'box-shadow': '0 0 10px rgba(0, 0, 0, 0.2)',
                    'margin-top': marginTop + 'px',
                    'margin-left': margin + 'px',
                    'margin-bottom': marginBottom + 'px',
                    'width': newWidth + 'px',
                });
            },
            redirectFunc: function () {
                this.initModalSize();
                jQuery('#unlimit_modal_bg').removeClass('closed');
                jQuery('body').css('overflow', 'hidden');
                jQuery('#unlimit_modal_iframe').attr('src', this.getSuccessUrl());
            },
            /**
             * Place order
             */
            placeOrder: function (data, event) {
                var self = this;

                if (self.getApiAccessMode() !== 'pp') {
                    this.validateCardNumber();
                    this.validateCardHolderName();
                    this.validateCardExpirationDate();
                    this.validateSecurityCode();

                    if (self.isCpfRequired()) {
                        this.validateCpf();
                    }
                }

                if (self.areInstallmentsEnabled()) {
                    this.validateInstallments();
                }

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
                            if (self.getApiAccessMode() === 'pp') {
                                self.redirectFunc();
                            } else {
                                self.afterPlaceOrder();
                            }
                        }
                    );

                    return true;
                }

                jQuery('div.mage-error').css('display', 'none');

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

                cardNumber.value = cardNumber.value.replace(/\D/g, '')
                    .replace(/^(\d{4})(\d)/g, "$1 $2")
                    .replace(/^(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3")
                    .replace(/^(\d{4})\s(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3 $4")
                    .replace(/^(\d{4})\s(\d{4})\s(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3 $4 $5");

            },

            luhnValidation: function (cardNumber) {
                if (!cardNumber) {
                    return false;
                }

                const cardNumberWithoutSpaces = (cardNumber + '').replace(/\s/g, '');
                let digit, odd, sum, _i, _len;
                odd = true;
                sum = 0;
                const digits = cardNumberWithoutSpaces.split('').reverse();

                for (_i = 0, _len = digits.length; _i < _len; _i++) {
                    digit = digits[_i];
                    digit = parseInt(digit, 10);
                    odd = !odd;
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

            validateCardNumber: function (_a, _b) {
                const cardBrands = [
                    {
                        cbType: 'visa',
                        pattern: /^4/,
                        cnLength: [13, 14, 15, 16, 19],
                    },
                    {
                        cbType: 'mir',
                        pattern: /^220[0-4]\d+/,
                        cnLength: [16, 17, 18, 19],
                    },
                    {
                        cbType: 'discover',
                        pattern: /^(60110\d|6011[2-4]\d|601174|60117[7-9]|6011[8-9][4-9]|644\d\d\d|65\d\d\d\d|64[4-9]\d+|369989)/,
                        cnLength: [16, 17, 18, 19],
                    },
                    {
                        cbType: 'dinersclub',
                        pattern: /^(30[0-5]\d\d\d|3095\d\d|3[8-9]\d\d\d\d)/,
                        cnLength: [16, 17, 18, 19],
                    }, {
                        cbType: 'dinersclub',
                        pattern: /^(36\d\d\d\d)/,
                        cnLength: [14, 15, 16, 17, 18, 19],
                    },
                    {
                        cbType: 'amex',
                        pattern: /^3[47]/,
                        cnLength: [15],
                    },
                    {
                        cbType: 'jcb',
                        pattern: /^(((352[8-9][0-9][0-9])|(35[3-8][0-9][0-9][0-9]))|((30[8-9][8-9][0-9][0-9])|309[0-4][0-9][0-9])|((309[6-9][0-9][0-9])|310[0-2][0-9][0-9])|(311[2-9][0-9][0-9])|(3120[0-9][0-9])|(315[8-9][0-9][0-9])|((333[7-9][0-9][0-9])|(334[0-9][0-9][0-9])))/,  // NOSONAR
                        cnLength: [16, 17, 18, 19],
                    },
                    {
                        cbType: 'unionpay',
                        pattern: /^(62|9558|81)/,
                        cnLength: [13, 14, 15, 16, 17, 18, 19],
                    },
                    {
                        cbType: 'elo',
                        pattern: /^(50(67(0[78]|1[5789]|2[012456789]|3[01234569]|4[0-7]|53|7[4-8])|9(0(0[0123478]|14|2[0-2]|3[359]|4[01235678]|5[1-9]|6[0-9]|7[0134789]|8[04789]|9[12349])|1(0[34568]|4[6-9]|83)|2(20|5[7-9]|6[0-6])|4(0[7-9]|1[0-2]|31)|7(22|6[5-9])))|4(0117[89]|3(1274|8935)|5(1416|7(393|63[12])))|6(27780|36368|5(0(0(3[12356789]|4[0-9]|5[01789]|6[01345678]|7[78])|4(0[6-9]|1[0-3]|2[2-6]|3[4-9]|8[5-9]|9[0-9])|5(0[012346789]|1[0-9]|2[0-9]|3[0-8]|7[7-9]|8[0-9]|9[0-8])|72[0-7]|9(0[1-9]|1[0-9]|2[0128]|3[89]|4[6-9]|5[045]|6[25678]|71))|16(5[2-9]|6[0-9]|7[01456789])|50(0[0-9]|1[0-9]|2[1-9]|3[0-6]|5[1-7]))))/,
                        cnLength: [13, 16, 19],
                    },
                    {
                        cbType: 'mastercard',
                        pattern: /^5[1-5]|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))/,
                        cnLength: [16],
                    },
                    {
                        cbType: 'maestro',
                        pattern: /^(0604|50|5[6789]|60|61|63|64|67|6660|6670|6818|6858|6890|6901|6907)/,
                        cnLength: [12, 13, 14, 15, 16, 17, 18, 19],
                    },
                ];

                const cardBrandSpan = document.getElementById('card-brand');
                cardBrandSpan.removeAttribute('class');

                const cardNumberInputField = document.querySelector(CPv1.selectors.cardNumber);
                if (cardNumberInputField === null || typeof cardNumberInputField === 'undefined') {
                    return;
                }
                cardNumberInputField.removeAttribute("style")

                const cardNumber = cardNumberInputField.value.replace(/[^\d]/gi, '');
                var self = this;
                let isCardNumberValid = true;
                for (let cardBrandIndex = 0; cardBrandIndex <= cardBrands.length - 1; cardBrandIndex++) {
                    const cardBrand = cardBrands[cardBrandIndex];
                    if (cardBrand.pattern.test(cardNumber)) {
                        if (cardBrand.cbType === 'unionpay') {
                            if (!cardBrand.cnLength.includes(cardNumber.length) || cardNumber.length < 13 || cardNumber.length > 19) {
                                isCardNumberValid = false;
                                self.show();
                            }

                            cardBrandSpan.className = 'card-brand-' + cardBrand.cbType;
                            return isCardNumberValid;
                        }

                        if (!cardBrand.cnLength.includes(cardNumber.length) || !this.luhnValidation(cardNumber)) {
                            isCardNumberValid = false;
                        }

                        cardBrandSpan.className = 'card-brand-' + cardBrand.cbType;
                        break;
                    }
                }

                // unknown card brand
                const card_number = 'card-number';

                self.hideErrorSecond(card_number);
                self.hideError(card_number);
                if (!cardNumber.length) {
                    self.showErrorsecond(card_number);
                    return;
                }

                if (cardNumber.length < 13 || cardNumber.length > 19 || !this.luhnValidation(cardNumber)) {
                    self.showError(card_number);
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

                const valueExpirationDate = document.querySelector(CPv1.selectors.cardExpirationDate).value;
                const expirationValuesEmpty = valueExpirationDate;
                const errorCodeSecond = '208';
                self.hideErrorSecond(errorCodeSecond);
                if (!expirationValuesEmpty.length) {
                    self.showErrorsecond(errorCodeSecond);
                    return;
                }

                const expirationValues = valueExpirationDate.split('/');
                if (typeof expirationValues[0] === 'undefined' || typeof expirationValues[1] === 'undefined') {
                    self.showError(errorCode);
                    return;
                }

                const expirationMonth = parseInt(expirationValues[0]);
                if (expirationMonth < 1 || expirationMonth > 12) {
                    self.showError(errorCode);
                    return;
                }

                const expirationYear = parseInt(20 + expirationValues[1]);

                const currentTime = new Date();
                const currentYear = currentTime.getFullYear();
                const currentMonth = currentTime.getMonth() + 1;

                if (expirationYear < currentYear
                    || (expirationYear > currentYear + 40)
                    || (expirationYear === currentYear && expirationMonth < currentMonth)) {
                    self.showError(errorCode);
                }
            },

            validateCardHolderName: function (a, b) {
                const cardHolderName = document.querySelector(CPv1.selectors.cardholderName).value;
                const self = this;

                self.hideError('316');
                self.hideErrorSecond('317');
                if (cardHolderName.length === 0 || cardHolderName === '') {
                    self.showErrorsecond('317');
                    self.hideError('316');
                    return;
                }

                if (cardHolderName.length < 2 || cardHolderName.length > 50) {
                    self.showError('316');
                    self.hideErrorSecond('317');
                }
            },

            validateInstallments: function () {
                var installmentsInput = document.querySelector(CPv1.selectors.installments).value;
                var self = this;
                self.hideError('210');
                if (!installmentsInput.length) {
                    var installmentsError = document.getElementById('installments-error');
                    if (installmentsError) {
                        installmentsError.style.display = 'none';
                    }
                    self.showError('210');
                }
            },

            validateSecurityCode: function () {
                var self = this;
                self.hideError('E302');
                self.hideErrorSecond('224');

                const securityCode = document.querySelector(CPv1.selectors.securityCode).value;
                if (!securityCode) {
                    self.hideError('E302');
                    self.showErrorsecond('224');
                }

                if (securityCode.length === 1 || securityCode.length === 2) {
                    self.hideErrorSecond('224');
                    self.showError('E302');
                }
            },

            validateCpf: function (a, b) {
                const self = this;
                const cpf = document.querySelector(CPv1.selectors.cpf).value;
                self.hideError('E303');
                self.hideErrorSecond('E304');

                if (!cpf.length || cpf === 'XXX.XXX.XXX-XX') {
                    self.hideError('E303');
                    self.showErrorsecond('E304');
                    return;
                }

                if (cpf !== '' && (cpf.includes('X') || !this.isValidCPF(cpf))) {
                    self.hideErrorSecond('E304');
                    self.showError('E303');
                }
            },

            onlyNumbersInSecurityCode: function () {
                const securityCode = document.querySelector(CPv1.selectors.securityCode);
                if (securityCode.value.match(/[^\d]/g)) {
                    securityCode.value = securityCode.value.replace(/[^\d]/g, '');
                }
            },

            applyInputMask: function () { //NOSONAR
                function doFormat(x, pattern, mask) {
                    const strippedValue = x.replace(/[^\d]/g, "");
                    const chars = strippedValue.split('');
                    let count = 0;

                    let formatted = '';
                    for (let i = 0; i < pattern.length; i++) {
                        const c = pattern[i];
                        if (chars[count]) {
                            if (/\*/.test(c)) {
                                formatted += chars[count];
                                count++;
                            } else {
                                formatted += c;
                            }
                        } else {
                            if (mask && (mask.split('')[i])) {
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
                if (!cpf) {
                    return false;
                }

                cpf = cpf.replace(/[\s.-]*/igm, '');
                if (
                    !cpf ||
                    cpf.length !== 11 ||
                    cpf === '00000000000' ||
                    cpf === '11111111111' ||
                    cpf === '22222222222' ||
                    cpf === '33333333333' ||
                    cpf === '44444444444' ||
                    cpf === '55555555555' ||
                    cpf === '66666666666' ||
                    cpf === '77777777777' ||
                    cpf === '88888888888' ||
                    cpf === '99999999999'
                ) {
                    return false;
                }

                let sum = 0;
                let remainder;
                for (let i = 1; i <= 9; i++) {
                    sum = sum + parseInt(cpf.substring(i - 1, i)) * (11 - i);
                }

                remainder = parseInt(sum * 10) % 11;
                if ((remainder === 10) || (remainder === 11)) {
                    remainder = 0;
                }

                if (remainder !== parseInt(cpf.substring(9, 10))) {
                    return false;
                }

                sum = 0;
                for (let j = 1; j <= 10; j++) {
                    sum = sum + parseInt(cpf.substring(j - 1, j)) * (12 - j);
                }

                remainder = parseInt((sum * 10) % 11);
                if ((remainder === 10) || (remainder === 11)) {
                    remainder = 0;
                }

                return remainder === parseInt(cpf.substring(10, 11));
            },

            showError: function (code) {
                const $form = CPv1.getForm();
                const $span = $form.querySelector(this.error_selector + code);
                $span.style.display = 'inline-block';
            },

            hideError: function (code) {
                const $form = CPv1.getForm();
                const $span = $form.querySelector(this.error_selector + code);
                $span.style.display = 'none';
            },

            showErrorsecond: function (code) {
                const $form = CPv1.getForm();
                const $span = $form.querySelector(this.error_selector + code + '-second');
                $span.style.display = 'inline-block';
            },

            hideErrorSecond: function (code) {
                const $form = CPv1.getForm();
                const $span = $form.querySelector(this.error_selector + code + '-second');
                $span.style.display = 'none';
            },
        });
    }
);
