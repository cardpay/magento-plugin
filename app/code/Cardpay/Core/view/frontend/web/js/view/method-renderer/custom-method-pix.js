define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-list',
        'Magento_Checkout/js/action/get-totals',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'Cardpay_Core/js/model/set-analytics-information',
        'mage/translate',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/cart/cache',
        'Magento_Checkout/js/model/payment/additional-validators',
        'CPv1Pix'
    ],
    function (Component, quote, paymentService, paymentMethodList, getTotalsAction, $, fullScreenLoader, setAnalyticsInformation, $t, defaultTotal, cartCache) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_pix',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,
            placeOrderHandler: null,
            validateHandler: null,

            initializeMethod: function () {
                CPv1Pix.text.apply = $t('Apply');
                CPv1Pix.text.remove = $t('Remove');
                CPv1Pix.text.coupon_empty = $t('Please, inform your coupon code');

                CPv1Pix.actionsMLB = function () {
                    if (document.querySelector(CPv1Pix.selectors.docNumber)) {
                        CPv1Pix.addListenerEvent(document.querySelector(CPv1Pix.selectors.docNumber), 'keyup', CPv1Pix.execFormatDocument);
                    }
                    if (document.querySelector(CPv1Pix.selectors.radioTypeFisica)) {
                        CPv1Pix.addListenerEvent(document.querySelector(CPv1Pix.selectors.radioTypeFisica), "change", CPv1Pix.initializeDocumentPessoaFisica);
                    }
                    if (document.querySelector(CPv1Pix.selectors.radioTypeFisica)) {
                        CPv1Pix.addListenerEvent(document.querySelector(CPv1Pix.selectors.radioTypeJuridica), "change", CPv1Pix.initializeDocumentPessoaJuridica);
                    }
                }
            },

            setBillingAddress: function (t) {
                if (typeof quote == 'object' && typeof quote.billingAddress == 'function') {
                    const billingAddress = quote.billingAddress();
                    let address = '';
                    let number = '';

                    if ("street" in billingAddress) {
                        if (billingAddress.street.length > 0) {
                            address = billingAddress.street[0]
                        }
                        if (billingAddress.street.length > 1) {
                            number = billingAddress.street[1]
                        }
                    }

                    self.setSelectorValues(billingAddress, address, number);
                }
            },

            setSelectorValues: function (billingAddress, address, number) {
                document.querySelector(CPv1Pix.selectors.firstName).value = "firstname" in billingAddress ? billingAddress.firstname : '';
                document.querySelector(CPv1Pix.selectors.lastName).value = "lastname" in billingAddress ? billingAddress.lastname : '';
                document.querySelector(CPv1Pix.selectors.address).value = address;
                document.querySelector(CPv1Pix.selectors.number).value = number;
                document.querySelector(CPv1Pix.selectors.city).value = "city" in billingAddress ? billingAddress.city : '';
                document.querySelector(CPv1Pix.selectors.state).value = "regionCode" in billingAddress ? billingAddress.regionCode : '';
                document.querySelector(CPv1Pix.selectors.zipcode).value = "postcode" in billingAddress ? billingAddress.postcode : '';
            },

            getInitialTotal: function () {
                return quote.totals().base_subtotal
                    + quote.totals().base_shipping_incl_tax
                    + quote.totals().base_tax_amount
                    + quote.totals().base_discount_amount;
            },

            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            context: function () {
                return this;
            },
            getCheckoutConfigParam: function(key, defaultValue) {
                if (typeof window.checkoutConfig.payment[this.getCode()] === 'undefined') {
                    return defaultValue;
                }
                return window.checkoutConfig.payment[this.getCode()][key];
            },
            getLogoUrl: function () {
                return this.getCheckoutConfigParam('logoUrl','');
            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            existBanner: function () {
                return this.getCheckoutConfigParam('bannerUrl',null) !== null;
            },

            getBannerUrl: function () {
                return this.getCheckoutConfigParam('bannerUrl','');
            },

            getCode: function () {
                return 'cardpay_custompix';
            },

            getPixData: function () {
                return window.checkoutConfig.payment[this.getCode()]['options'];
            },

            getCountPix: function () {
                return (0);
            },

            getFirstPixId: function () {
                var options = this.getPixData();

                return options[0]['id'];
            },

            getInitialGrandTotal: function () {
                return this.getCheckoutConfigParam('grand_total','');
            },

            getSuccessUrl: function () {
                return this.getCheckoutConfigParam('success_url','');
            },

            getCountryId: function () {
                return this.getCheckoutConfigParam('country','');
            },

            getPaymentSelected: function () {
                if (parseInt(this.getCountPix()) === 1) {
                    var input = document.getElementsByName("cardpay_custom_pix[payment_method_pix]")[0];
                    return input.value;
                }

                var element = document.querySelector('input[name="cardpay_custom_pix[payment_method_pix]"]:checked');
                if (this.getCountPix() > 1 && element) {
                    return element.value;
                } else {
                    return false;
                }
            },

            getPixLogoURL: function () {
                return this.getCheckoutConfigParam('pix_logo_url','');
            },

            /**
             * @override
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'method': this.getCode(),
                        'site_id': this.getCountryId(),
                        'payment_method_pix': this.getPaymentSelected(),
                        'cpf': document.getElementById('cpf_pix') ? document.querySelector(CPv1.selectors.cpfPix).value : null
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

            placeOrder: function (data, event) {
                var self = this;
                this.validateCpfPix()
                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && !this.hasErrors()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(function () {
                            self.afterPlaceOrder();
                        }
                    );

                    return true;
                }

                return false;
            },

            validateCpfPix: function (a, b) {
                var self = this;
                var cpfPix = document.querySelector(CPv1.selectors.cpfPix).value;
                self.hideErrorPix('E303');
                self.hideErrorSecond('E304');

                if (!cpfPix.length || cpfPix === 'XXX.XXX.XXX-XX') {
                    self.hideErrorPix('E303');
                    self.showErrorsecond('E304');
                    return;
                }

                if (cpfPix !== "") {
                    if (cpfPix.includes('X') || !this.isValidCPFPix(cpfPix)) {
                        self.hideErrorSecond('E304');
                        self.showError('E303');
                    }
                }
            },

            applyInputMask: function (a, b) { //NOSONAR
                function doFormat(x, pattern, mask) {
                    const strippedValue = x.replace(/[^0-9]/g, "");
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

            isValidCPFPix(cpf) {
                if (!cpf) {
                    return;
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
                    return;
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
                    return;
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
                var $form = document.querySelector(CPv1.selectors.formPix);
                var $span = $form.querySelector('#mp-error-' + code);
                $span.style.display = 'inline-block';
            },

            hideErrorPix: function (code) {
                var $form = document.querySelector(CPv1.selectors.formPix);
                var $span = $form.querySelector('#mp-error-' + code);
                $span.style.display = 'none';
            },

            showErrorsecond: function (code) {
                var $form = document.querySelector(CPv1.selectors.formPix);
                var $span = $form.querySelector('#mp-error-' + code + '-second');
                $span.style.display = 'inline-block';
            },

            hideErrorSecond: function (code) {
                var $form = document.querySelector(CPv1.selectors.formPix);
                var $span = $form.querySelector('#mp-error-' + code + '-second');
                $span.style.display = 'none';
            },
            /*
             * Customize CPV1
             */
            updateSummaryOrder: function () {
                cartCache.set('totals', null);
                defaultTotal.estimateTotals();
            },
        });
    }
);
