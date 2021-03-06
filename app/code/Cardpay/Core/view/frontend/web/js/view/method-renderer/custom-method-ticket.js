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
        'CPv1Ticket'
    ],
    function (Component, quote, paymentService, paymentMethodList, getTotalsAction, $, fullScreenLoader, setAnalyticsInformation, $t, defaultTotal, cartCache) {
        'use strict';

        const configPayment = window.checkoutConfig.payment.cardpay_customticket;

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_ticket',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,
            placeOrderHandler: null,
            validateHandler: null,

            initializeMethod: function () {
                const cardpay_site_id = String(window.checkoutConfig.payment[this.getCode()]['country']);
                const cardpay_coupon = window.checkoutConfig.payment[this.getCode()]['discount_coupon'];
                const cardpay_url = "/cardpay/api/coupon";
                let payer_email = "";

                if (typeof quote == 'object' && typeof quote.guestEmail == 'string') {
                    payer_email = quote.guestEmail
                }

                CPv1Ticket.text.apply = $t('Apply');
                CPv1Ticket.text.remove = $t('Remove');
                CPv1Ticket.text.coupon_empty = $t('Please, inform your coupon code');

                CPv1Ticket.actionsMLB = function () {
                    if (document.querySelector(CPv1Ticket.selectors.docNumber)) {
                        CPv1Ticket.addListenerEvent(document.querySelector(CPv1Ticket.selectors.docNumber), 'keyup', CPv1Ticket.execFormatDocument);
                    }
                    if (document.querySelector(CPv1Ticket.selectors.radioTypeFisica)) {
                        CPv1Ticket.addListenerEvent(document.querySelector(CPv1Ticket.selectors.radioTypeFisica), "change", CPv1Ticket.initializeDocumentPessoaFisica);
                    }
                    if (document.querySelector(CPv1Ticket.selectors.radioTypeFisica)) {
                        CPv1Ticket.addListenerEvent(document.querySelector(CPv1Ticket.selectors.radioTypeJuridica), "change", CPv1Ticket.initializeDocumentPessoaJuridica);
                    }
                }

                if (cardpay_site_id === 'CPB') {
                    this.setBillingAddress();
                }

                //change url loading
                CPv1Ticket.paths.loading = window.checkoutConfig.payment[this.getCode()]['loading_gif'];

                //Initialize CPv1Ticket
                CPv1Ticket.Initialize(cardpay_site_id, cardpay_coupon, cardpay_url, payer_email);
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
                document.querySelector(CPv1Ticket.selectors.firstName).value = "firstname" in billingAddress ? billingAddress.firstname : '';
                document.querySelector(CPv1Ticket.selectors.lastName).value = "lastname" in billingAddress ? billingAddress.lastname : '';
                document.querySelector(CPv1Ticket.selectors.address).value = address;
                document.querySelector(CPv1Ticket.selectors.number).value = number;
                document.querySelector(CPv1Ticket.selectors.city).value = "city" in billingAddress ? billingAddress.city : '';
                document.querySelector(CPv1Ticket.selectors.state).value = "regionCode" in billingAddress ? billingAddress.regionCode : '';
                document.querySelector(CPv1Ticket.selectors.zipcode).value = "postcode" in billingAddress ? billingAddress.postcode : '';
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

            getLogoUrl: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return configPayment['logoUrl'];
                }
                return '';
            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            getCountryId: function () {
                return configPayment['country'];
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

            getCode: function () {
                return 'cardpay_customticket';
            },

            getTicketsData: function () {
                return configPayment['options'];
            },

            getCountTickets: function () {
                return (0);
            },

            getFirstTicketId: function () {
                var options = this.getTicketsData();

                return options[0]['id'];
            },

            getInitialGrandTotal: function () {
                if (typeof configPayment !== 'undefined') {
                    return configPayment['grand_total'];
                }
                return '';
            },

            getSuccessUrl: function () {
                if (typeof configPayment !== 'undefined') {
                    return configPayment['success_url'];
                }
                return '';
            },

            getPaymentSelected: function () {
                if (parseInt(this.getCountTickets()) === 1) {
                    var input = document.getElementsByName("cardpay_custom_ticket[payment_method_ticket]")[0];
                    return input.value;
                }

                var element = document.querySelector('input[name="cardpay_custom_ticket[payment_method_ticket]"]:checked');
                if (this.getCountTickets() > 1 && element) {
                    return element.value;
                } else {
                    return false;
                }
            },

            getBoletoLogoURL: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['boleto_logo_url'];
                }

                return '';
            },

            /**
             * @override
             */
            getData: function () {
                var dataObj = {
                    'method': this.item.method,
                    'additional_data': {
                        'method': this.getCode(),
                        'site_id': this.getCountryId(),
                        'payment_method_ticket': this.getPaymentSelected(),
                        'cpf': document.getElementById('cpf_boleto') ? document.querySelector(CPv1.selectors.cpfBoleto).value : null
                    }
                };

                if (String(this.getCountryId()) === 'CPB' && this.getCountTickets() > 0) {
                    dataObj.additional_data.firstName = document.querySelector(CPv1Ticket.selectors.firstName).value
                    dataObj.additional_data.lastName = document.querySelector(CPv1Ticket.selectors.lastName).value
                    dataObj.additional_data.docType = CPv1Ticket.getDocTypeSelected();
                    dataObj.additional_data.docNumber = document.querySelector(CPv1Ticket.selectors.docNumber).value
                    dataObj.additional_data.address = document.querySelector(CPv1Ticket.selectors.address).value
                    dataObj.additional_data.addressNumber = document.querySelector(CPv1Ticket.selectors.number).value
                    dataObj.additional_data.addressCity = document.querySelector(CPv1Ticket.selectors.city).value
                    dataObj.additional_data.addressState = document.querySelector(CPv1Ticket.selectors.state).value
                    dataObj.additional_data.addressZipcode = document.querySelector(CPv1Ticket.selectors.zipcode).value
                }

                return dataObj;
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

            placeOrder: function (data, event) {
                var self = this;

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

            validateCpfBoleto: function (a, b) {
                var self = this;
                self.hideError('E304');

                var cpfBoleto = document.querySelector(CPv1.selectors.cpfBoleto).value;
                if (cpfBoleto !== "") {
                    if (cpfBoleto.includes('X') || !this.isValidCPF(cpfBoleto)) {
                        self.showError('E304');
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

                remainder = parseInt((sum * 10) % 11)
                if ((remainder === 10) || (remainder === 11)) {
                    remainder = 0
                }

                if (remainder !== parseInt(cpf.substring(9, 10))) {
                    return false
                }

                sum = 0
                for (var k = 1; k <= 10; k++) {
                    sum = sum + parseInt(cpf.substring(k - 1, k)) * (12 - k)
                }

                remainder = parseInt((sum * 10) % 11)
                if ((remainder === 10) || (remainder === 11)) {
                    remainder = 0
                }

                return remainder === parseInt(cpf.substring(10, 11));
            },

            showError: function (code) {
                var $form = document.querySelector(CPv1.selectors.formBoleto);
                var $span = $form.querySelector('#mp-error-' + code);
                $span.style.display = 'inline-block';
            },

            hideError: function (code) {
                var $form = document.querySelector(CPv1.selectors.formBoleto);
                var $span = $form.querySelector('#mp-error-' + code);
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
