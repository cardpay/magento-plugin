define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'cardpay_custom',
                component: 'Cardpay_Core/js/view/method-renderer/creditcard-method'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_customticket',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-ticket'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_custompix',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-pix'
            }
        );

        return Component.extend({});
    }
);
