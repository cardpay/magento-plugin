define(
    [
        'Cardpay_Core/js/view/method-renderer/custom-method-common',
        'Magento_Checkout/js/model/quote'
    ],
    function (Component, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_gpay',
                paymentReady: false
            },
            paymentsClient: null,
            grandTotalAmount: 0,
            baseRequest: {
                apiVersion: 2,
                apiVersionMinor: 0
            },
            baseCardPaymentMethod: {
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                    allowedCardNetworks: ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"]
                }
            },

            initObservable: function () {
                this._super();
                this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                this.currencyCode = quote.totals()['base_currency_code'];

                quote.totals.subscribe(function () {
                    if (this.grandTotalAmount !== quote.totals()['base_grand_total']) {
                        this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                    }
                }.bind(this));

                return this;
            },

            getGooglePayBtn: function (id) {
                const paymentsClient = this.getGooglePaymentsClient();
                var self = this;
                paymentsClient.isReadyToPay(this.getGoogleIsReadyToPayRequest())
                    .then(function (response) {
                        if (response.result) {
                            self.addGooglePayButton(id);
                        }
                    })
                    .catch(function (err) {
                        // show error in developer console for debugging
                        console.error(err);
                    });
            },
            addGooglePayButton: function (id) {
                const paymentsClient = this.getGooglePaymentsClient();
                const button =
                    paymentsClient.createButton({ onClick: this.onGooglePaymentButtonClicked.bind(this) });
                document.getElementById(id).appendChild(button);
            },
            onGooglePaymentButtonClicked: function () {
                const paymentDataRequest = this.getGooglePaymentDataRequest();

                const paymentsClient = this.getGooglePaymentsClient();
                paymentsClient.loadPaymentData(paymentDataRequest);
            },
            getGooglePaymentDataRequest: function () {
                const tokenizationSpecification = {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        'gateway': 'unlimint',
                        'gatewayMerchantId': this.getGpayMerchantId()
                    }
                };
                const cardPaymentMethod = Object.assign(
                    {},
                    this.baseCardPaymentMethod,
                    {
                        tokenizationSpecification: tokenizationSpecification
                    }
                );
                const paymentDataRequest = Object.assign({}, this.baseRequest);
                paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
                paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();
                paymentDataRequest.merchantInfo = {
                    // @todo a merchant ID is available for a production environment after approval by Google
                    // See {@link https://developers.google.com/pay/api/web/guides/test-and-deploy/integration-checklist|Integration checklist}
                    // merchantId: '01234567890123456789',
                    merchantName: 'Unlimint'
                };
                paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];

                return paymentDataRequest;
            },
            getGoogleTransactionInfo: function () {
                return {
                    totalPriceStatus: 'FINAL',
                    totalPrice: this.grandTotalAmount,
                    currencyCode: this.currencyCode
                };
            },
            getGoogleIsReadyToPayRequest: function () {
                return Object.assign(
                    {},
                    this.baseRequest,
                    {
                        allowedPaymentMethods: [this.baseCardPaymentMethod]
                    }
                );
            },
            getGooglePaymentsClient: function () {
                if (this.paymentsClient === null) {
                    this.paymentsClient = new google.payments.api.PaymentsClient({
                        environment: this.getEnvironment(),
                        paymentDataCallbacks: {
                            onPaymentAuthorized: this.onPaymentAuthorized.bind(this)
                        }
                    });
                }
                return this.paymentsClient;
            },
            onPaymentAuthorized: function (paymentData) {
                var self = this;
                return new Promise(function (resolve, reject) {
                    // handle the response
                    self.processPayment(paymentData)
                        .then(function () {
                            self.placeOrder();
                            resolve({ transactionState: 'SUCCESS' });
                        })
                        .catch(function () {
                            resolve({
                                transactionState: 'ERROR',
                                error: {
                                    intent: 'PAYMENT_AUTHORIZATION',
                                    message: 'Insufficient funds, try again. Next attempt should work.',
                                    reason: 'PAYMENT_DATA_INVALID'
                                }
                            });
                        });
                });
            },
            processPayment: function (paymentData) {
                return new Promise(function (resolve, reject) {
                    setTimeout(function () {
                        var paymentToken = paymentData.paymentMethodData.tokenizationData.token;
                        jQuery('#co-cardpay-form-gpay').find('[name="cardpay_custom_gpay[signature]"]').val(paymentToken);
                        resolve({});
                    }, 500);
                });
            },

            getEnvironment: function () {
                return this.getCheckoutConfigParam('sandbox', 'TEST');
            },
            getGpayMerchantId: function () {
                return this.getCheckoutConfigParam('gpay_merchant_id', 'googletest');
            },

            context: function () {
                return this;
            },

            getCode: function () {
                return 'cardpay_gpay';
            },
            placeOrder: function (data, event) {
                var self = this;
                if (event) {
                    event.preventDefault();
                }

                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                    .fail(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).done(function () {
                        self.afterPlaceOrder();
                    });

                return true;
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
                        'encrypted_data': document.getElementById('signatureGpay') ? document.querySelector('#signatureGpay').value : null
                    }
                };
            }
        });
    }
);
