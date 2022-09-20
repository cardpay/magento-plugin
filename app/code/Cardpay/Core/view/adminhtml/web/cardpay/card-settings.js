const unlimintSettingsFields = {
    active: 'active',
    api_access_mode: 'api_access_mode',
    ask_cpf: 'ask_cpf',
    title: 'title',
    maximum_accepted_installments: 'maximum_accepted_installments',
    installment_type: 'installment_type',
    minimum_installment_amount: 'minimum_installment_amount',
    installment: 'installment',
    callback_secret: 'callback_secret',
    terminal_code: 'terminal_code',
    terminal_password: 'access_token',
    dynamic_descriptor: 'descriptor',
    saveButtonID:'save'
};

const unlimintCardSettings = {
    prefix: '',
    selPaymentPage: null,
    selEnabled: null,
    selInstType: null,
    selInstEnabled: null,
    minimumInstallmentAmount: null,
    askCpf: null,
    maximumAcceptedInstallments: null,
    installmentsLimits: [],
    installmentsLimitsIF: [3, 6, 9, 12, 18],
    installmentsLimitsMfHold: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
    defaultInstallments: [],
    installmentSettingsFields: ['minimum_installment_amount', 'maximum_accepted_installments', 'installment_type'],
    initScript: function() {
        const self = this;
        if (window.jQuery) {
            self.init();
        } else {
            window.setTimeout(function() {
                self.initScript();
            }, 100);
        }
    },
    init: function () {
        if (document.querySelector('[data-ui-id="select-groups-cardpay-configurations-groups-custom-checkout-fields-active-value"]') === null) {
            return;
        }
        const firstField = jQuery.find('[data-ui-id="select-groups-cardpay-configurations-groups-custom-checkout-fields-active-value"]');

        const id = (jQuery(firstField).attr('id'));
        this.prefix = id.replace('active', '');
        this.selPaymentPage = jQuery(`#${this.prefix}${unlimintSettingsFields.api_access_mode}`);
        this.selInstType = jQuery(`#${this.prefix}${unlimintSettingsFields.installment_type}`);
        this.selEnabled = jQuery(`#${this.prefix}${unlimintSettingsFields.active}`);
        this.selInstEnabled = jQuery(`#${this.prefix}${unlimintSettingsFields.installment}`);
        this.minimumInstallmentAmount = jQuery(`#${this.prefix}${unlimintSettingsFields.minimum_installment_amount}`);
        this.maximumAcceptedInstallments = jQuery(`#${this.prefix}${unlimintSettingsFields.maximum_accepted_installments}`);
        this.askCpf = jQuery(`#${this.prefix}ask_cpf`);
        this.installmentSettingsFields = [
            unlimintSettingsFields.minimum_installment_amount,
            unlimintSettingsFields.maximum_accepted_installments,
            unlimintSettingsFields.installment_type
        ];
        this.toggleSettings();
        this.selectPpAndInstType();
        this.processInstallmentSettings();
        this.setupListeners();
        this.validateForm();
        this.setupInstallmentsTypes();
        this.checkMinimumInstallmentAmount();
    },
    setupInstallmentsTypes: function() {
        if (jQuery(this.selInstType).find('option[value="MF_HOLD"]').length==0 && jQuery(this.selPaymentPage).val() === 'gateway') {
            jQuery(this.selInstType).append('<option value="MF_HOLD">Merchant financed</option>');
        } else if(jQuery(this.selPaymentPage).val() !== 'gateway') {
            jQuery(this.selInstType).find('option[value="MF_HOLD"]').remove();
        }
    },
    setupListeners: function () {
        const obj = this;
        jQuery(`#${unlimintSettingsFields.saveButtonID}`).on('click', function (e) {
            obj.onFormSubmit(e);
        });
        jQuery(obj.selPaymentPage).on('change', function () {
            obj.toggleSettings();
            obj.setupInstallmentsTypes();
            if (jQuery(obj.selPaymentPage).val() === 'payment_page') {
                obj.selectPpAndInstType();
            }
        });
        jQuery(obj.selInstType).on('change', function () {
            obj.selectPpAndInstType();
        });

        jQuery(obj.selInstEnabled).on('change', function () {
            obj.processInstallmentSettings();
        });

        jQuery(obj.maximumAcceptedInstallments).on('change keyup', function (e) {
            obj.checkMaximumAcceptedInstallments(false, (e.type === 'change'));
        });
        jQuery(obj.minimumInstallmentAmount).on('change', function() {
            obj.checkMinimumInstallmentAmount();
        });
    },
    checkMinimumInstallmentAmount: function() {
        let val = parseFloat(this.minimumInstallmentAmount.val()).toFixed(4);
        if (val<0) {
            highlightUlAdminError(jQuery(this.minimumInstallmentAmount).attr('id'));
        }
        if (isNaN(val)) {
            val = 0;
        }
        val = val + '';
        val = val.replace(/^0+|0+$/g, '').replace(/\.$/,'');
        this.minimumInstallmentAmount.val(val);
    },
    validateForm: function(displayError) {
        let error = false;
        error = !validateUlAdminField(this.prefix + unlimintSettingsFields.terminal_code, 128, 'terminal code', true, displayError) || error;
        error = !validateUlAdminField(this.prefix + unlimintSettingsFields.terminal_password, 128, 'terminal password', false, displayError) || error;
        error = !validateUlAdminField(this.prefix + unlimintSettingsFields.callback_secret, 128, 'callback secret', false, displayError) || error;
        error = !validateUlAdminField(this.prefix + unlimintSettingsFields.title, 128, 'payment title', false, displayError) || error;
        error = !validateUlAdminField(this.prefix + unlimintSettingsFields.dynamic_descriptor, 22, 'dynamic descriptor', false, displayError) || error;
        if (jQuery(this.selInstEnabled).val() === '1') {
            error = !this.checkMaximumAcceptedInstallments(displayError) || error;
        }
        return !error;
    },
    onFormSubmit: function (e) {
        if (!jQuery(this.selPaymentPage).is(':visible')) {
            return;
        }
        if (!this.validateForm(true)) {
            e.preventDefault(e);
        }
    },
    processInstallmentSettings: function () {
        const obj = this;
        const show = (jQuery(obj.selInstEnabled).val() === '1');
        jQuery(obj.installmentSettingsFields).each(function () {
            const el = jQuery(`#${obj.prefix}${this}`).parent().parent();
            if (show) {
                el.show('slow');
            } else {
                el.hide();
            }
        });
    },
    toggleSettings: function () {
        const cpfEl = jQuery(this.askCpf).parent().parent();
        if (jQuery(this.selPaymentPage).val() !== 'gateway') {
            cpfEl.hide();
        } else {
            cpfEl.show();
        }
    },
    normalizeIntVal: function (val) {
        val = val.replace(/[\D]/g, '');
        val = parseInt(val);
        return (isNaN(val)) ? '' : val;
    },
    fixInstallmentSettings: function(value) {
        const obj = this;
        const defaults = (jQuery(this.selInstType).val() === 'IF') ? '3,6,9,12,18' : '2-12';
        const values = value.split(',');
        if (values.length === 0 || (values.length === 1 && values[0] === '')) {
            return defaults;
        }
        const newValues = [];
        jQuery.each(values, function(){
            if (this.indexOf('-') > -1) {
                const vals = this.split('-');
                vals[0] = obj.normalizeIntVal(vals[0]);
                if(vals.length>1) {
                    vals[1] = obj.normalizeIntVal(vals[1]);
                    newValues.push(vals.join('-'));
                } else {
                    newValues.push(vals[0] + '-');
                }
            } else {
                newValues.push(obj.normalizeIntVal(this));
            }
        });
        const result = newValues.join(',');
        return (result === '') ? defaults : result;
    },
    validateInstallmentRange: function(value) {
        const parsed = value.split('-');
        if (parsed.length !== 2) {
            return false;
        }
        parsed[0] = this.normalizeIntVal(parsed[0]);
        parsed[1] = this.normalizeIntVal(parsed[1]);
        let error = (
            this.installmentsLimits.indexOf(parsed[0]) === -1 ||
            this.installmentsLimits.indexOf(parsed[1]) === -1 ||
            parsed[0] >= parsed[1]
        );
        for(let i = parsed[0]; i<=parsed[1]; i++) {
            error = error || (this.installmentsLimits.indexOf(i) === -1);
        }
        return !error;
    },
    displayInstallmentsError: function(displayError) {
        if (displayError === true) {
            let errorMessage = jQuery.mage.__('Allowed installments range') + ' ';
            switch (jQuery(this.selInstType).val()) {
                case 'IF': {
                    errorMessage = errorMessage + jQuery.mage.__('not in row 3, 6, 9, 12, 18');
                    break
                }
                default: {
                    errorMessage = errorMessage + jQuery.mage.__('not in interval 1-12');
                }
            }
            showUlAdminError('maximum_accepted_installments', errorMessage);
        }
        highlightUlAdminError(jQuery(this.maximumAcceptedInstallments).attr('id'));
    },
    checkMaximumAcceptedInstallments: function (displayError, fix) {
        const obj = this;

        if (jQuery(obj.maximumAcceptedInstallments).val() === '') {
            jQuery(obj.maximumAcceptedInstallments).val(obj.defaultInstallments);
            return true;
        }
        let value = jQuery(obj.maximumAcceptedInstallments).val();

        if (fix === true) {
            const newValue = this.fixInstallmentSettings(value);
            if (newValue !== value) {
                window.setTimeout(function () {
                    jQuery(obj.maximumAcceptedInstallments).val(value);
                }, 1);
                value = newValue;
            }
        }

        let error = false;
        if (value.search(/[^\d-,]/)!== -1) {
            error = true;
        }
        const values = value.split(',');

        jQuery.each(values, function () {
            if (this.indexOf('-') > -1) {
                error = error || !(obj.validateInstallmentRange(this));
            } else {
                error = error || (obj.installmentsLimits.indexOf(obj.normalizeIntVal(this)) === -1);
            }
        });
        if (error) {
            this.displayInstallmentsError(displayError);
        }
        if (!error) {
            hideUlAdminError(jQuery(obj.maximumAcceptedInstallments).attr('id'));
        }
        return !error;
    },
    selectPpAndInstType: function () {
        switch (jQuery(this.selInstType).val()) {
            case 'IF': {
                this.installmentsLimits = this.installmentsLimitsIF;
                this.defaultInstallments = '3,6,9,12,18';
                jQuery(this.maximumAcceptedInstallments).attr('placeholder', '3, 6, 9, 12, 18');
                break;
            }
            case 'MF_HOLD': {
                this.installmentsLimits = this.installmentsLimitsMfHold;
                this.defaultInstallments = '2-12';
                jQuery(this.maximumAcceptedInstallments).attr('placeholder', '1, 2, 3-5, 7-12');
                break;
            }
            default: {
                //
            }
        }
        this.checkMaximumAcceptedInstallments();
    }
};

document.addEventListener('DOMContentLoaded', function () {
    unlimintCardSettings.initScript();
}, false);
