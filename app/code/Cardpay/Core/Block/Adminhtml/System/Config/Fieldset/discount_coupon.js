define(
    [
        'Cardpay_Core/js/view/checkout/summary/discount_coupon'
    ],
    function (Component) {
        'use strict';

        return Component.extend({

            /**
             * @override
             */
            isDisplayed: function () {
                return parseInt(this.getRawValue()) !== 0;
            }
        });
    }
);
