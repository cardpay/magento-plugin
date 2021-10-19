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
                return this.getRawValue() != 0;
            }
        });
    }
);