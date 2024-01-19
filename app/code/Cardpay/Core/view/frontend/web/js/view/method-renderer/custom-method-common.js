define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        return Component.extend({
            iframePadding: 40,
            maxIframeWidth: 1000,
            placeOrderHandler: null,
            validateHandler: null,

            getCheckoutConfigParam: function (key, defaultValue) {
                if (typeof window.checkoutConfig.payment[this.getCode()] === 'undefined') {
                    return defaultValue;
                }
                return window.checkoutConfig.payment[this.getCode()][key];
            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            getLogoUrl: function () {
                return this.getCheckoutConfigParam('logoUrl', '');
            },

            getSuccessUrl: function () {
                return this.getCheckoutConfigParam('success_url', '');
            },

            getCountryId: function () {
                return this.getCheckoutConfigParam('country', '');
            },

            afterPlaceOrder: function () {
                window.location = this.getSuccessUrl();
            },

            getApiAccessMode: function () {
                return this.getCheckoutConfigParam('api_access_mode', 'gateway');
            },

            redirectFunc: function (payment_method) {
                this.initModalSize(payment_method);
                jQuery('#unlimit_' + payment_method + '_modal_bg').removeClass('closed');
                jQuery('body').css('overflow', 'hidden');
                jQuery('#unlimit_' + payment_method + '_modal_iframe').attr('src', this.getSuccessUrl());
            },

            initModalSize: function (payment_method) {
                const self = this;
                jQuery(window).resize(function () {
                    self.setModalSize(payment_method);
                });
                self.setModalSize(payment_method);
            },

            setModalSize: function (payment_method) {
                const backWindow = jQuery('#unlimit_' + payment_method + '_modal_page');
                const w = jQuery(window).width();
                const {iframePadding, maxIframeWidth} = this;

                const marginTop = 40;
                const marginBottom = 20;

                const newWidth = Math.min(w - iframePadding, maxIframeWidth);
                const margin = Math.round((w - newWidth) / 2);

                backWindow.css({
                    'background': '#FFF',
                    'max-height': '800px',
                    'height': '100%',
                    'border-radius': '10px',
                    'padding': '10px',
                    'box-shadow': '0 0 10px rgba(0, 0, 0, 0.2)',
                    'margin-top': marginTop + 'px',
                    'margin-left': margin + 'px',
                    'margin-bottom': marginBottom + 'px',
                    'width': newWidth + 'px',
                });
            },
        });
    }
);
