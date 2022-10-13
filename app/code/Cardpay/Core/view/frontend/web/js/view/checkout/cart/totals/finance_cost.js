define(
    [
        'Cardpay_Core/js/view/checkout/summary/finance_cost'
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
