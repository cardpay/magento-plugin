define(
    [
        'Cardpay_Core/js/view/method-renderer/custom-method-common'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/custom_spei',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'cardpay_spei';
            },

            getSpeiLogoURL: function () {
                return this.getCheckoutConfigParam('spei_logo_url','');
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
                            self.redirectFunc('spei');
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
