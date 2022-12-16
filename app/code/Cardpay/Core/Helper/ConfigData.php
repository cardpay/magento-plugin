<?php

namespace Cardpay\Core\Helper;

class ConfigData
{
    // payment methods
    public const BANKCARD_PAYMENT_METHOD = 'cardpay_custom';
    public const BOLETO_PAYMENT_METHOD = 'cardpay_customticket';
    public const PIX_PAYMENT_METHOD = 'cardpay_custompix';

    // Unlimint API payment methods
    public const BANK_CARD_API_PAYMENT_METHOD = 'BANKCARD';
    public const BOLETO_API_PAYMENT_METHOD = 'BOLETO';
    public const PIX_API_PAYMENT_METHOD = 'PIX';

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
        'BOLETO' => 'payment/cardpay_customticket/',
        'PIX' => 'payment/cardpay_custompix/',
    ];

    // Boleto payment method
    public const PATH_TICKET_ACTIVE = 'payment/cardpay_customticket/active';
    public const PATH_TICKET_BANNER = 'payment/cardpay_customticket/banner_checkout';
    public const PATH_TICKET_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_customticket/excluded_payment_methods';

    // Pix payment method
    public const PATH_PIX_ACTIVE = 'payment/cardpay_custompix/active';
    public const PATH_PIX_BANNER = 'payment/cardpay_custompix/banner_checkout';
    public const PATH_PIX_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_custompix/excluded_payment_methods';

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
        $path = constant(self::class . '::' . $status);
        $replace = self::PREFIX_PAYMENT[$paymentType];

        return str_replace('payment/cardpay_custom/', $replace, $path);
    }
}
