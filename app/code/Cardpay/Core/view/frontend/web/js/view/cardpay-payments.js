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
                type: 'cardpay_apay',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-apay'
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
        rendererList.push(
            {
                type: 'cardpay_paypal',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-paypal'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_gpay',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-gpay'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_sepa',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-sepa'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_spei',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-spei'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_multibanco',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-multibanco'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_mbway',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-mbway'
            }
        );
        rendererList.push(
            {
                type: 'cardpay_oxxo',
                component: 'Cardpay_Core/js/view/method-renderer/custom-method-oxxo'
            }
        );

        return Component.extend({});
    }
);
