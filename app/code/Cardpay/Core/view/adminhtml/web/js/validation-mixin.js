define(['jquery'], function ($) {
    'use strict';

    return function (targetWidget) {
        $.validator.addMethod(
            'required-pem-file',
            function (value, element) {
                if (!value) {
                    const existing = $(element).parent().find('input[type="checkbox"]');
                    if (existing.length === 0 || existing.is(':checked')) {
                        return false;
                    }
                }
                return true;
            },
            $.mage.__('This is a required field.')
        )
        return targetWidget;
    }
});
