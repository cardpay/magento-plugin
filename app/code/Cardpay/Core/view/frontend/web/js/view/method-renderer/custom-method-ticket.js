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

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_ticket',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,
            placeOrderHandler: null,
            validateHandler: null,
            error_selector: '#mp-error-',

            initializeMethod: function () {
                const cardpay_site_id = String(window.checkoutConfig.payment[this.getCode()]['country']);
                let payer_email = "";

                if (typeof quote == 'object' && typeof quote.guestEmail == 'string') {
                    payer_email = quote.guestEmail
                }

                CPv1Ticket.text.apply = $t('Apply');
                CPv1Ticket.text.remove = $t('Remove');

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
                CPv1Ticket.Initialize(cardpay_site_id, payer_email);
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

            getZipCode: function () {
                let zipcode = '';
                try {
                    const billing = quote.billingAddress();
                    const shipping = quote.shippingAddress();
                    const sa = (shipping) ? shipping.postcode : '';
                    const ba = (billing) ? billing.postcode : '';
                    zipcode = sa || ba;
                } catch {

                }
                zipcode = zipcode.replace(/[\D]/g, "");
                return (zipcode.length >= 8) ? zipcode : '';
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
                    return window.checkoutConfig.payment[this.getCode()]['logoUrl'];
                }
                return '';
            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            getCountryId: function () {
                return window.checkoutConfig.payment[this.getCode()]['country'];
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
                return window.checkoutConfig.payment[this.getCode()]['options'];
            },

            getCountTickets: function () {
                return (0);
            },

            getFirstTicketId: function () {
                var options = this.getTicketsData();

                return options[0]['id'];
            },

            getInitialGrandTotal: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['grand_total'];
                }
                return '';
            },

            getSuccessUrl: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()] !== 'undefined') {
                    return window.checkoutConfig.payment[this.getCode()]['success_url'];
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
                        'zip': document.getElementById('zip_boleto').value,
                        'cpf': document.getElementById('cpf_boleto').value
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
                this.validateCpfBoleto();
                this.validateZipBoleto();
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

            validateZipBoleto: function (a, b) {
                const self = this;
                const zipSelector = '#zip_boleto';

                self.hideErrorBoleto('E305');
                self.hideErrorBoleto('E305-second');

                if (!jQuery(zipSelector).is(':visible')) {
                    return;
                }

                const postcode = jQuery(zipSelector).val();
                const newZip = postcode.replace(/[\D]+/, '');
                if (newZip !== postcode) {
                    window.setTimeout(function () {
                        jQuery(zipSelector).val(newZip);
                        self.validateZipBoleto();
                    }, 100);
                }

                if (newZip.length !== 8) {
                    if (newZip.length === 0) {
                        self.showError('E305-second');
                    } else {
                        self.showError('E305');
                    }
                }
            },

            validateCpfBoleto: function (a, b) {
                var self = this;
                var cpfBoleto = document.querySelector(CPv1Ticket.selectors.cpfBoleto).value;
                self.hideErrorBoleto('E303');
                self.hideErrorSecond('E304');
                if (!jQuery(CPv1Ticket.selectors.cpfBoleto).is(':visible')) {
                    return;
                }
                if (!cpfBoleto.length || cpfBoleto === 'XXX.XXX.XXX-XX') {
                    self.hideErrorBoleto('E303');
                    self.showErrorsecond('E304');
                    return;
                }

                if (cpfBoleto !== "") {
                    if (cpfBoleto.includes('X') || !this.isValidCPFBoleto(cpfBoleto)) {
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

            isValidCPFBoleto(cpf) {
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
                const $form = document.querySelector(CPv1Ticket.selectors.formBoleto);
                const $span = $form.querySelector(this.error_selector + code);
                if ($span) {
                    $span.style.display = 'inline-block';
                }
            },

            hideErrorBoleto: function (code) {
                const $form = document.querySelector(CPv1Ticket.selectors.formBoleto);
                const $span = $form.querySelector(this.error_selector + code);
                if ($span) {
                    $span.style.display = 'none';
                }
            },

            showErrorsecond: function (code) {
                const $form = document.querySelector(CPv1Ticket.selectors.formBoleto);
                const $span = $form.querySelector(this.error_selector + code + '-second');
                $span.style.display = 'inline-block';
            },

            hideErrorSecond: function (code) {
                const $form = document.querySelector(CPv1Ticket.selectors.formBoleto);
                const $span = $form.querySelector(this.error_selector + code + '-second');
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
