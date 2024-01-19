<?php

namespace Cardpay\Core\Helper;

class ConfigData
{
    // payment methods
    public const BANKCARD_PAYMENT_METHOD = 'cardpay_custom';
    public const APAY_PAYMENT_METHOD = 'cardpay_apay';
    public const BOLETO_PAYMENT_METHOD = 'cardpay_customticket';
    public const PIX_PAYMENT_METHOD = 'cardpay_custompix';
    public const PAYPAL_PAYMENT_METHOD = 'cardpay_paypal';
    public const GPAY_PAYMENT_METHOD = 'cardpay_gpay';
    public const SEPA_PAYMENT_METHOD = 'cardpay_sepa';
    public const SPEI_PAYMENT_METHOD = 'cardpay_spei';
    public const MULTIBANCO_PAYMENT_METHOD = 'cardpay_multibanco';
    public const MBWAY_PAYMENT_METHOD = 'cardpay_mbway';

    // Unlimit API payment methods
    public const BANK_CARD_API_PAYMENT_METHOD = 'BANKCARD';
    public const APAY_API_PAYMENT_METHOD = 'APPLEPAY';
    public const BOLETO_API_PAYMENT_METHOD = 'BOLETO';
    public const PIX_API_PAYMENT_METHOD = 'PIX';
    public const PAYPAL_API_PAYMENT_METHOD = 'PAYPAL';
    public const GPAY_API_PAYMENT_METHOD = 'GOOGLEPAY';
    public const SEPA_API_PAYMENT_METHOD = 'SEPATRANSFER';
    public const SPEI_API_PAYMENT_METHOD = 'SPEI';
    public const MULTIBANCO_API_PAYMENT_METHOD = 'MULTIBANCO';
    public const MBWAY_API_PAYMENT_METHOD = 'MBWAY';

    // credentials path (cards)
    public const PATH_BANKCARD_TERMINAL_CODE = 'payment/cardpay_custom/terminal_code';
    public const PATH_BANKCARD_TERMINAL_PASSWORD = 'payment/cardpay_custom/terminal_password';
    public const PATH_BANKCARD_CALLBACK_SECRET = 'payment/cardpay_custom/callback_secret';
    public const PATH_BANKCARD_SANDBOX = 'payment/cardpay_custom/sandbox';

    public const PATH_BANKCARD_MAXIMUM_ACCEPTED_INSTALLMENTS = 'payment/cardpay_custom/maximum_accepted_installments';
    public const PATH_BANKCARD_API_ACCESS_MODE = 'payment/cardpay_custom/api_access_mode';
    public const PATH_BANKCARD_INSTALLMENT_TYPE = 'payment/cardpay_custom/installment_type';
    public const PATH_BANKCARD_MINIMUM_INSTALLMENT_AMOUNT = 'payment/cardpay_custom/minimum_installment_amount';

    // basic method
    public const PATH_BASIC_ACTIVE = 'payment/cardpay_basic/active';
    public const PATH_BASIC_TITLE = 'payment/cardpay_basic/title';
    public const PATH_BASIC_URL_FAILURE = 'payment/cardpay_basic/url_failure';
    public const PATH_BASIC_AUTO_RETURN = 'payment/cardpay_basic/auto_return';
    public const PATH_BASIC_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_basic/excluded_payment_methods';
    public const PATH_BASIC_STATEMENT_DESCRIPTION = 'payment/cardpay_basic/statement_desc';
    public const PATH_BASIC_EXPIRATION_TIME_PREFERENCE = 'payment/cardpay_basic/exp_time_pref';
    public const PATH_BASIC_ORDER_STATUS = 'payment/cardpay_basic/order_status';
    public const PATH_BASIC_BINARY_MODE = 'payment/cardpay_basic/binary_mode';
    public const PATH_BASIC_GATEWAY_MODE = 'payment/cardpay_basic/gateway_mode';

    // credentials path (Apple Pay)
    public const PATH_APAY_TERMINAL_CODE = 'payment/cardpay_apay/terminal_code_apay';
    public const PATH_APAY_TERMINAL_PASSWORD = 'payment/cardpay_apay/terminal_password_apay';
    public const PATH_APAY_CALLBACK_SECRET = 'payment/cardpay_apay/callback_secret_apay';
    public const PATH_APAY_SANDBOX = 'payment/cardpay_apay/sandbox_apay';
    public const PATH_APAY_ACTIVE = 'payment/cardpay_apay/active';
    public const PATH_APAY_MERCHANT_ID = 'payment/cardpay_apay/merchant_id';
    public const PATH_APAY_MERCHANT_CERTIFICATE = 'payment/cardpay_apay/merchant_certificate';
    public const PATH_APAY_MERCHANT_KEY = 'payment/cardpay_apay/merchant_key';

    // credentials path (Boleto)
    public const PATH_BOLETO_TERMINAL_CODE = 'payment/cardpay_customticket/terminal_code_boleto';
    public const PATH_BOLETO_TERMINAL_PASSWORD = 'payment/cardpay_customticket/terminal_password_boleto';
    public const PATH_BOLETO_CALLBACK_SECRET = 'payment/cardpay_customticket/callback_secret_boleto';
    public const PATH_BOLETO_SANDBOX = 'payment/cardpay_customticket/sandbox_boleto';

    // credentials path (Pix)
    public const PATH_PIX_TERMINAL_CODE = 'payment/cardpay_custompix/terminal_code_pix';
    public const PATH_PIX_TERMINAL_PASSWORD = 'payment/cardpay_custompix/terminal_password_pix';
    public const PATH_PIX_CALLBACK_SECRET = 'payment/cardpay_custompix/callback_secret_pix';
    public const PATH_PIX_SANDBOX = 'payment/cardpay_custompix/sandbox_pix';

    // credentials path (Paypal)
    public const PATH_PAYPAL_TERMINAL_CODE = 'payment/cardpay_paypal/terminal_code_paypal';
    public const PATH_PAYPAL_TERMINAL_PASSWORD = 'payment/cardpay_paypal/terminal_password_paypal';
    public const PATH_PAYPAL_CALLBACK_SECRET = 'payment/cardpay_paypal/callback_secret_paypal';
    public const PATH_PAYPAL_SANDBOX = 'payment/cardpay_paypal/sandbox_paypal';
    public const PATH_PAYPAL_API_ACCESS_MODE = 'payment/cardpay_paypal/api_access_mode';

    // credentials path (Gpay)
    public const PATH_GPAY_TERMINAL_CODE = 'payment/cardpay_gpay/terminal_code_gpay';
    public const PATH_GPAY_TERMINAL_PASSWORD = 'payment/cardpay_gpay/terminal_password_gpay';
    public const PATH_GPAY_CALLBACK_SECRET = 'payment/cardpay_gpay/callback_secret_gpay';
    public const PATH_GPAY_SANDBOX = 'payment/cardpay_gpay/sandbox_gpay';
    public const PATH_GPAY_ACTIVE = 'payment/cardpay_gpay/active';
    public const PATH_GPAY_MERCHANT_ID = 'payment/cardpay_gpay/merchant_id';

    // credentials path (Sepa)
    public const PATH_SEPA_TERMINAL_CODE = 'payment/cardpay_sepa/terminal_code_sepa';
    public const PATH_SEPA_TERMINAL_PASSWORD = 'payment/cardpay_sepa/terminal_password_sepa';
    public const PATH_SEPA_CALLBACK_SECRET = 'payment/cardpay_sepa/callback_secret_sepa';
    public const PATH_SEPA_SANDBOX = 'payment/cardpay_sepa/sandbox_sepa';
    public const PATH_SEPA_API_ACCESS_MODE = 'payment/cardpay_sepa/api_access_mode';
    public const PATH_SEPA_ACTIVE = 'payment/cardpay_sepa/active';

    // credentials path (Spei)
    public const PATH_SPEI_TERMINAL_CODE = 'payment/cardpay_spei/terminal_code_spei';
    public const PATH_SPEI_TERMINAL_PASSWORD = 'payment/cardpay_spei/terminal_password_spei';
    public const PATH_SPEI_CALLBACK_SECRET = 'payment/cardpay_spei/callback_secret_spei';
    public const PATH_SPEI_SANDBOX = 'payment/cardpay_spei/sandbox_spei';
    public const PATH_SPEI_API_ACCESS_MODE = 'payment/cardpay_spei/api_access_mode';
    public const PATH_SPEI_ACTIVE = 'payment/cardpay_spei/active';

    // credentials path (Multibanco)
    public const PATH_MULTIBANCO_TERMINAL_CODE = 'payment/cardpay_multibanco/terminal_code_multibanco';
    public const PATH_MULTIBANCO_TERMINAL_PASSWORD = 'payment/cardpay_multibanco/terminal_password_multibanco';
    public const PATH_MULTIBANCO_CALLBACK_SECRET = 'payment/cardpay_multibanco/callback_secret_multibanco';
    public const PATH_MULTIBANCO_SANDBOX = 'payment/cardpay_multibanco/sandbox_multibanco';
    public const PATH_MULTIBANCO_API_ACCESS_MODE = 'payment/cardpay_multibanco/api_access_mode';
    public const PATH_MULTIBANCO_ACTIVE = 'payment/cardpay_multibanco/active';

    // credentials path (Mb Way)
    public const PATH_MBWAY_TERMINAL_CODE = 'payment/cardpay_mbway/terminal_code_mbway';
    public const PATH_MBWAY_TERMINAL_PASSWORD = 'payment/cardpay_mbway/terminal_password_mbway';
    public const PATH_MBWAY_CALLBACK_SECRET = 'payment/cardpay_mbway/callback_secret_mbway';
    public const PATH_MBWAY_SANDBOX = 'payment/cardpay_mbway/sandbox_mbway';
    public const PATH_MBWAY_API_ACCESS_MODE = 'payment/cardpay_mbway/api_access_mode';
    public const PATH_MBWAY_ACTIVE = 'payment/cardpay_mbway/active';

    // configuration hidden path
    public const PATH_SITE_ID = 'payment/cardpay/site_id';
    public const PATH_SPONSOR_ID = 'payment/cardpay/sponsor_id';

    // custom method credit and debit card
    public const PATH_CUSTOM_ACTIVE = 'payment/cardpay_custom/active';
    public const PATH_CUSTOM_BINARY_MODE = 'payment/cardpay_custom/binary_mode';
    public const PATH_CUSTOM_STATEMENT_DESCRIPTOR = 'payment/cardpay_custom/statement_descriptor';
    public const PATH_CUSTOM_BANNER = 'payment/cardpay_custom/banner_checkout';
    public const PATH_CUSTOM_GATEWAY_MODE = 'payment/cardpay_custom/gateway_mode';
    public const PATH_CUSTOM_ASK_CPF = 'payment/cardpay_custom/ask_cpf';
    public const PATH_CUSTOM_INSTALLMENT = 'payment/cardpay_custom/installment';
    public const PATH_CUSTOM_DESCRIPTOR = 'payment/cardpay_custom/descriptor';
    public const PATH_CUSTOM_CAPTURE = 'payment/cardpay_custom/capture';

    public const PREFIX_PAYMENT = [
        'BANKCARD' => 'payment/cardpay_custom/',
        'APPLEPAY' => 'payment/cardpay_apay/',
        'BOLETO' => 'payment/cardpay_customticket/',
        'PIX' => 'payment/cardpay_custompix/',
        'PAYPAL' => 'payment/cardpay_paypal/',
        'GOOGLEPAY' => 'payment/cardpay_gpay/',
        'SEPA' => 'payment/cardpay_sepa/',
        'SPEI' => 'payment/cardpay_spei/',
        'MULTIBANCO' => 'payment/cardpay_multibanco/',
        'MBWAY' => 'payment/cardpay_mbway/',
    ];

    // Boleto payment method
    public const PATH_TICKET_ACTIVE = 'payment/cardpay_customticket/active';
    public const PATH_TICKET_BANNER = 'payment/cardpay_customticket/banner_checkout';
    public const PATH_TICKET_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_customticket/excluded_payment_methods';

    // Pix payment method
    public const PATH_PIX_ACTIVE = 'payment/cardpay_custompix/active';
    public const PATH_PIX_BANNER = 'payment/cardpay_custompix/banner_checkout';
    public const PATH_PIX_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_custompix/excluded_payment_methods';

    // Paypal payment method
    public const PATH_PAYPAL_ACTIVE = 'payment/cardpay_paypal/active';
    public const PATH_PAYPAL_BANNER = 'payment/cardpay_paypal/banner_checkout';
    public const PATH_PAYPAL_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_paypal/excluded_payment_methods';

    // custom method bank transfer
    public const PATH_CUSTOM_BANK_TRANSFER_ACTIVE = 'payment/cardpay_custom/active';
    public const PATH_CUSTOM_BANK_TRANSFER_REDIRECT_PAYER = 'payment/cardpay_custom/redirect_payer';

    // order configuration
    public const PATH_ORDER_AUTHORIZED = 'payment/cardpay_custom/order_status_authorized';
    public const PATH_ORDER_COMPLETED = 'payment/cardpay_custom/order_status_completed';
    public const PATH_ORDER_DECLINED = 'payment/cardpay_custom/order_status_declined';
    public const PATH_ORDER_VOIDED = 'payment/cardpay_custom/order_status_voided';
    public const PATH_ORDER_CHARGED_BACK = 'payment/cardpay_custom/order_status_chargeback';
    public const PATH_ORDER_CHARGEBACK_RESOLVED = 'payment/cardpay_custom/order_status_resolved';
    public const PATH_ORDER_REFUND_AVAILABLE = 'payment/cardpay_custom/refund_available';

    // advanced configuration
    public const PATH_ADVANCED_LOG = 'payment/cardpay/logs';
    public const PATH_ADVANCED_COUNTRY = 'payment/cardpay/country';
    public const PATH_ADVANCED_CATEGORY = 'payment/cardpay/category_id';
    public const PATH_ADVANCED_SUCCESS_PAGE = 'payment/cardpay/use_successpage';
    public const PATH_ADVANCED_CONSIDER_DISCOUNT = 'payment/cardpay/consider_discount';
    public const PATH_ADVANCED_EMAIL_CREATE = 'payment/cardpay/email_order_create';
    public const PATH_ADVANCED_EMAIL_UPDATE = 'payment/cardpay/email_order_update';

    // log
    public const LOG_FILENAME = 'cardpay.log';
    public const BASIC_LOG_PREFIX = 'cardpay-basic';
    public const CUSTOM_LOG_PREFIX = 'cardpay-custom';

    public static function getStatusByPaymentType($status, $paymentType): string
    {
        $path = constant(self::class.'::'.$status);
        $replace = self::PREFIX_PAYMENT[$paymentType];

        return str_replace('payment/cardpay_custom/', $replace, $path);
    }
}

