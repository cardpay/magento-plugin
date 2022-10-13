define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Cardpay_Core/js/model/set-analytics-information'
    ],
    function (Component, setAnalyticsInformation) {
        'use strict';

        const configPayment = window.checkoutConfig.payment.cardpay_basic;

        return Component.extend({
            defaults: {
                template: 'Cardpay_Core/payment/basic_method',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,

            initObservable: function () {
                this._super().observe('paymentReady');

                return this;
            },
            isPaymentReady: function () {
                return this.paymentReady();
            },

            afterPlaceOrder: function () {
                window.location = this.getActionUrl();
            },

            /**
             * Places order in pending payment status.
             */
            placePendingPaymentOrder: function () {
                this.placeOrder();
            },
            initialize: function () {
                this._super();
                setAnalyticsInformation.beforePlaceOrder(this.getCode());
            },

            /**
             * @returns {string}
             */
            getCode: function () {
                return 'cardpay_basic';
            },

            /**
             * @returns {*}
             */
            getLogoUrl: function () {
                if (configPayment !== undefined) {
                    return configPayment['logoUrl'];
                }
                return '';
            },

            /**
             * @returns {boolean}
             */
            existBanner: function () {
                if ((configPayment !== undefined) && (configPayment['bannerUrl'] != null) ) {
                        return true;
                }
                return false;
            },

            /**
             * @returns {*}
             */
            getBannerUrl: function () {
                if (configPayment !== undefined) {
                    return configPayment['bannerUrl'];
                }
                return '';
            },

            /**
             * @returns {*}
             */
            getActionUrl: function () {
                if (configPayment !== undefined) {
                    return configPayment['actionUrl'];
                }
                return '';
            },


            /**
             * Basic Checkout
             */

            getRedirectImage: function () {
                return configPayment['redirect_image'];
            },

            getInfoBanner: function ($pm) {
                if (configPayment !== undefined) {
                    return configPayment['banner_info'][$pm];
                }
                return 0;
            },

            getInfoBannerInstallments: function () {
                if (configPayment !== undefined) {
                    return configPayment['banner_info']['installments'];
                }
                return 0;
            },

            getInsertList: function ($pmFilter, pmSelected) {
                let insertList = false;
                const paymentTypeId = String(pmSelected.payment_type_id);

                if (String($pmFilter) === 'credit') {
                    if (paymentTypeId === 'credit_card') {
                        insertList = true
                    }
                } else if (String($pmFilter) === 'debit') {
                    if (paymentTypeId === 'debit_card' || paymentTypeId === 'prepaid_card') {
                        insertList = true
                    }
                } else {
                    if (paymentTypeId !== 'credit_card' && paymentTypeId !== 'debit_card' && paymentTypeId !== 'prepaid_card') {
                        insertList = true
                    }
                }

                return insertList;
            },

            getInfoBannerPaymentMethods: function ($pmFilter) {
                const listPm = []

                if (configPayment !== undefined) {
                    const paymentMethods = configPayment['banner_info']['checkout_methods'];
                    if (paymentMethods) {

                        for (let pmIndex = 0; pmIndex < paymentMethods.length; pmIndex++) {
                            const pmSelected = paymentMethods[pmIndex];

                            const insertList = this.getInsertList($pmFilter, pmSelected);
                            if (insertList) {
                                listPm.push({
                                    src: pmSelected.secure_thumbnail,
                                    name: pmSelected.name
                                });
                            }
                        }
                    }
                }

                return listPm;
            },
        });
    }
);
