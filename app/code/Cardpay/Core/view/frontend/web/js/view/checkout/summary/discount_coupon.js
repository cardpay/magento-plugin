define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, quote, priceUtils, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/checkout/summary/discount_coupon'
            },
            totals: quote.getTotals(),
            isDisplayed: function () {
                return this.isFullMode();
            },

            getRawValue: function () {
                var price = 0;
                if (this.totals() && totals.getSegment('discount_coupon')) {
                    price = totals.getSegment('discount_coupon').value;
                }
                return price;
            },

            getValue: function () {
                var price = this.getRawValue();
                return this.getFormattedPrice(price);
            },
        });
    }
);
