define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment-service',
        'jquery'
    ],
    function (Component, quote) {
        'use strict'

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_apay',
                paymentReady: false
            },
            grandTotalAmount: 0,
            beginPayment: function (e) {
                e.preventDefault()
                var storeName = this.getCheckoutConfigParam('store_name', '')

                var totalForDelivery = {
                    label: storeName,
                    type: "final",
                    amount: this.grandTotalAmount
                }

                // Create the Apple Pay payment request as appropriate.
                var paymentRequest = {
                    countryCode: 'US',
                    currencyCode: this.currencyCode,
                    merchantCapabilities: ['supports3DS'],
                    supportedNetworks: ['amex', 'masterCard', 'visa', 'elo', 'discover'],
                    total: totalForDelivery
                }

                const session = new ApplePaySession(3, paymentRequest)

                // Setup handler for validation the merchant session.
                session.onvalidatemerchant = function (event) {
                    console.log('Event: Validate merchant')
                    console.log(event)
                    var self = this;
                    var url = event.validationURL;
                    jQuery.get(BASE_URL + 'cardpay/applepay/validatemerchant',
                        {
                            url,
                            merchantIdentifier: self.getMerchantIdentifier(),
                            displayName: storeName
                        }, function (data) {
                            try {
                                var merchantSession = JSON.parse(data)
                            } catch (e) {
                                console.log(e);
                                //            this should never happen in our situation, unless a bad build
                                console.log('paymentSession response is not valid JSON:\n' + data + '\nApplePaySession cancelled by Apple Pay Demo Site\n')
                                if (session !== null) {
                                    self.cancelPaymentSession(session)
                                }
                            }
                            // cleaning the data
                            let sanitize = JSON.parse(data)
                            sanitize.signature = 'REDACTED'
                            sanitize.merchantSessionIdentifier = 'REDACTED'
                            sanitize.merchantIdentifier = 'REDACTED'

                            var text = JSON.stringify(sanitize, undefined, 4)
                            // Stop the session if merchantSession is not valid
                            if (typeof merchantSession === 'string' || 'statusCode' in merchantSession) {
                                console.log('paymentSession failed:\n' + text + '\nApplePaySession cancelled by Apple Pay Demo Site\n')
                                self.cancelPaymentSession(session)
                                return
                            }
                            if (!('merchantIdentifier' in merchantSession && 'merchantSessionIdentifier' in merchantSession && ('nOnce' in merchantSession || 'nonce' in merchantSession))) {
                                let errorDescription = 'merchantSession is invalid. Payment Session cancelled by Apple Pay Demo Site.\n'
                                if (!('merchantIdentifier' in merchantSession)) {
                                    errorDescription += 'merchantIdentifier is not found in merchantSession.\n'
                                }
                                if (!('merchantSessionIdentifier' in merchantSession)) {
                                    errorDescription += 'merchantSessionIdentifier is not found in merchantSession.\n'
                                }
                                if (!('nOnce' in merchantSession)) {
                                    errorDescription += 'nonce is not found in merchantSession\n'
                                }
                                errorDescription += text
                                console.log(errorDescription)
                                self.cancelPaymentSession(session)
                                return
                            }

                            console.log(text)
                            if (session !== null) {
                                session.completeMerchantValidation(merchantSession)
                            }
                        }, 'text')
                        .fail(function (xhr, textStatus, errorThrown) {
                            console.log(xhr.responseText)
                            if (session !== null) {
                                self.cancelPaymentSession(session)
                            }
                        })
                }.bind(this)

                session.onpaymentmethodselected = function onpaymentmethodselected(event) {
                    console.log('Event: onpaymentmethodselected', event);
                    var storeName = this.getCheckoutConfigParam('store_name', '')

                    var totalForDelivery = {
                        label: storeName,
                        type: "final",
                        amount: this.grandTotalAmount
                    }
                    let update = {
                        newTotal: totalForDelivery
                    }
                    console.log('Event: completePaymentMethodSelection', event);
                    session.completePaymentMethodSelection(update)
                }.bind(this)

                session.onshippingmethodselected = function onshippingmethodselected(event) {
                    console.log('Event: onshippingmethodselected', event);
                    let update = {};
                    session.completeShippingMethodSelection(update)
                }

                // Setup handler to receive the token when payment is authorized.
                session.onpaymentauthorized = function (event) {
                    console.log('Event: onpaymentauthorized', event);
                    var paymentSignature = JSON.stringify(event.payment);
                    var self = this;
                    jQuery('#co-cardpay-form-apay').find('[name="cardpay_custom_apay[signature]"]').val(paymentSignature);
                    window.setTimeout(function () {
                        let update = { status: ApplePaySession.STATUS_SUCCESS, errors: [] }

                        session.completePayment(update)
                        self.placeOrder();
                        console.log('\n\ncompletePayment executed with the following parameters:\n' + JSON.stringify({ status: update.status, errors: update.errors }, undefined, 4) + '\n')
                      }, 2000)
                }.bind(this)

                session.oncouponcodechanged = event => {
                    console.log('Event: oncouponcodechanged', event);
                    // Define ApplePayCouponCodeUpdate

                    session.completeCouponCodeChange({});
                };

                // Start the session to display the Apple Pay sheet.
                console.log('Session begin!')
                session.begin()
            },
            setupApplePay: function () {
                var merchantIdentifier = this.getMerchantIdentifier()
                ApplePaySession.openPaymentSetup(merchantIdentifier)
                    .then(function (success) {
                        if (success) {
                            this.showButton()
                        } else {
                            let text = 'Failed to set up Apple Pay.'
                            console.log(text)
                            this.showError(text)
                        }
                    }).catch(function (e) {
                        let text = 'Failed to set up Apple Pay. ' + e
                        console.log(text)
                        this.showError(text)
                    })
            },
            cancelPaymentSession: function(session) {
                console.error("Cancelling session: ", session);
                if (session !== null) { session.abort() }
            },
            showButton: function () {
                console.log('Show Button')
                var button = jQuery('#apple-pay-button')
                button.attr('lang', this.getPageLanguage())
                button.on('click', this.beginPayment.bind(this))
                if (this.supportsSetup()) {
                    console.log('Show Support Button')
                    button.addClass('apple-pay-button-with-text')
                    button.addClass('apple-pay-button-black-with-text')
                } else {
                    console.log('Hide Support Button')
                    button.addClass('apple-pay-button')
                    button.addClass('apple-pay-button-black')
                }

                button.removeClass('d-none')
            },
            showError: function (text) {
                console.log(text)
                var error = jQuery('.apple-pay-error')
                error.text(text)
                error.removeClass('d-none')
            },
            showSuccess: function () {
                jQuery('.apple-pay-intro').hide()
                var success = jQuery('.apple-pay-success')
                success.removeClass('d-none')
            },
            supportedByDevice: function () {
                console.log('Check ApplePaySession')
                return 'ApplePaySession' in window
            },
            supportsSetup: function () {
                console.log('Check openPaymentSetup')
                return 'openPaymentSetup' in ApplePaySession
            },
            getPageLanguage: function () {
                return jQuery('html').attr('lang') || 'en'
            },
            getMerchantIdentifier: function () {
                return this.getCheckoutConfigParam('apay_merchant_id', '')
            },

            initialize: function () {
                this._super()
            },

            initObservable: function () {
                this._super()
                this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2)
                this.currencyCode = quote.totals()['base_currency_code']

                quote.totals.subscribe(function () {
                    if (this.grandTotalAmount !== quote.totals()['base_grand_total']) {
                        this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2)
                    }
                }.bind(this))

                return this
            },

            context: function () {
                return this
            },
            getCheckoutConfigParam: function (key, defaultValue) {
                if (typeof window.checkoutConfig.payment[this.getCode()] === 'undefined') {
                    return defaultValue
                }
                return window.checkoutConfig.payment[this.getCode()][key]
            },
            getAPayBtn: function () {
                if (this.supportedByDevice()) {
                    console.log('Is ApplePaySession available in the browser? Yes')
                    this.showButton();
                } else {
                    let msgApplePayFailed = 'This device and/or browser does not support Apple Pay.'
                    console.log(msgApplePayFailed);
                    this.showError(msgApplePayFailed);
                }
                return;
            },

            getCode: function () {
                return 'cardpay_apay'
            },

            getSuccessUrl: function () {
                return this.getCheckoutConfigParam('success_url', '')
            },

            getCountryId: function () {
                return this.getCheckoutConfigParam('country', '')
            },

            processPayment: function (paymentData) {
                return new Promise(function (resolve, reject) {
                    setTimeout(function () {
                        var paymentToken = paymentData.paymentMethodData.tokenizationData
                        jQuery('#co-cardpay-form-apay').find('[name="cardpay_custom_apay[nonce]"]').val(paymentToken)
                        resolve({})
                    }, 500)
                })
            },
            placeOrder: function (data, event) {
                var self = this
                if (event) {
                    event.preventDefault()
                }

                this.isPlaceOrderActionAllowed(false)

                this.getPlaceOrderDeferredObject()
                    .fail(
                        function () {
                            self.isPlaceOrderActionAllowed(true)
                        }
                    ).done(function () {
                        self.afterPlaceOrder()
                    })

                return true
            },

            afterPlaceOrder: function () {
                window.location = this.getSuccessUrl()
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
                        'encrypted_data': document.getElementById('signatureApay') ? document.querySelector('#signatureApay').value : null
                    }
                };
            }
        })
    }
)
