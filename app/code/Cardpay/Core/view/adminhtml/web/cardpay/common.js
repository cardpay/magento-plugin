const doProcessError = function(displayError, errorMessageId, fieldName, msg) {
    if (displayError === true) {
        showUlAdminError(errorMessageId, msg);
    }
    highlightUlAdminError(fieldName);
};

const validateUlAdminField = function (fieldName, maxLength, errorField, positiveInteger, displayError) {
    const errorMessageId = fieldName + '-error';
    const errorMessageField = jQuery(`[id=${errorMessageId}]`);
    if (errorMessageField) {
        errorMessageField.remove();
    }

    const adminField = jQuery(`#${fieldName}`);
    if (!adminField) {
        return true;
    }
    const fieldValue = adminField.val();
    if (!fieldValue || fieldValue.trim().length === 0) {
        doProcessError(displayError, errorMessageId, fieldName, jQuery.mage.__('Empty') +  ` ${errorField}`);
        return false;
    }

    if (fieldValue.length > maxLength || (positiveInteger && (isNaN(fieldValue) || parseInt(fieldValue) < 0))) {
        doProcessError(displayError, errorMessageId, fieldName, jQuery.mage.__('Invalid') + ` ${errorField}`);
        return false;
    }
    hideUlAdminError(fieldName);
    return true;
}

const hideUlAdminError = function(id) {
    jQuery(`#${id}`).parent().parent().removeClass('ul_error');
}

const highlightUlAdminError = function(id) {
    jQuery(`#${id}`).parent().parent().addClass('ul_error');
}

const showUlAdminError = function (errorMessageId, errorMessage) {
    alert(errorMessage);
}
