define(
    [
        'Cardpay_Core/js/view/method-renderer/custom-method-common'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_sepa',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'cardpay_sepa';
            },

            getSepaLogoURL: function () {
                return this.getCheckoutConfigParam('sepa_logo_url','');
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
                        if (self.getApiAccessMode() === 'pp') {
                            self.redirectFunc('sepa');
                        } else {
                            self.afterPlaceOrder();
                        }
                    }
                );
                return true;
            }
        });
    }
);
