<?php

namespace Cardpay\Core\Helper;

class ConfigData
{
    // payment methods
    public const BANKCARD_PAYMENT_METHOD = 'cardpay_custom';
    public const BOLETO_PAYMENT_METHOD = 'cardpay_customticket';

    // Unlimint API payment methods
    public const BANK_CARD_API_PAYMENT_METHOD = 'BANKCARD';
    public const BOLETO_API_PAYMENT_METHOD = 'BOLETO';

    // credentials path (cards)
    public const PATH_BANKCARD_TERMINAL_CODE = 'payment/cardpay_custom/terminal_code';
    public const PATH_BANKCARD_TERMINAL_PASSWORD = 'payment/cardpay_custom/terminal_password';
    public const PATH_BANKCARD_CALLBACK_SECRET = 'payment/cardpay_custom/callback_secret';
    public const PATH_BANKCARD_SANDBOX = 'payment/cardpay_custom/sandbox';

    // credentials path (Boleto)
    public const PATH_BOLETO_TERMINAL_CODE = 'payment/cardpay_customticket/terminal_code_boleto';
    public const PATH_BOLETO_TERMINAL_PASSWORD = 'payment/cardpay_customticket/terminal_password_boleto';
    public const PATH_BOLETO_CALLBACK_SECRET = 'payment/cardpay_customticket/callback_secret_boleto';
    public const PATH_BOLETO_SANDBOX = 'payment/cardpay_customticket/sandbox_boleto';

    // configuration hidden path
    public const PATH_SITE_ID = 'payment/cardpay/site_id';
    public const PATH_SPONSOR_ID = 'payment/cardpay/sponsor_id';

    // custom method credit and debit card
    public const PATH_CUSTOM_ACTIVE = 'payment/cardpay_custom/active';
    public const PATH_CUSTOM_BINARY_MODE = 'payment/cardpay_custom/binary_mode';
    public const PATH_CUSTOM_STATEMENT_DESCRIPTOR = 'payment/cardpay_custom/statement_descriptor';
    public const PATH_CUSTOM_BANNER = 'payment/cardpay_custom/banner_checkout';
    public const PATH_CUSTOM_COUPON = 'payment/cardpay_custom/coupon_cardpay';
    public const PATH_CUSTOM_GATEWAY_MODE = 'payment/cardpay_custom/gateway_mode';
    public const PATH_CUSTOM_ASK_CPF = 'payment/cardpay_custom/ask_cpf';
    public const PATH_CUSTOM_INSTALLMENT = 'payment/cardpay_custom/installment';
    public const PATH_CUSTOM_DESCRIPTOR = 'payment/cardpay_custom/descriptor';
    public const PATH_CUSTOM_CAPTURE = 'payment/cardpay_custom/capture';

    // Boleto payment method
    public const PATH_CUSTOM_TICKET_ACTIVE = 'payment/cardpay_customticket/active';
    public const PATH_CUSTOM_TICKET_COUPON = 'payment/cardpay_customticket/coupon_cardpay';
    public const PATH_CUSTOM_TICKET_BANNER = 'payment/cardpay_customticket/banner_checkout';
    public const PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_customticket/excluded_payment_methods';

    // custom method bank transfer
    public const PATH_CUSTOM_BANK_TRANSFER_ACTIVE = 'payment/cardpay_custom/active';
    public const PATH_CUSTOM_BANK_TRANSFER_BANNER = 'payment/cardpay_custom/banner_checkout';
    public const PATH_CUSTOM_BANK_TRANSFER_REDIRECT_PAYER = 'payment/cardpay_custom/redirect_payer';

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

    // order configuration
    public const STATUS_CANCELLED = 'payment/cardpay/order_status_cancelled';
    public const STATUS_IN_PROCESS = 'payment/cardpay/order_status_in_process';

    public const PATH_ORDER_AUTHORIZED = 'payment/cardpay/order_status_authorized';
    public const PATH_ORDER_IN_PROCESS = self::STATUS_IN_PROCESS;
    public const PATH_ORDER_NEW = 'payment/cardpay/order_status_pending';
    public const PATH_ORDER_COMPLETED = self::STATUS_IN_PROCESS;
    public const PATH_ORDER_DECLINED = 'payment/cardpay/order_status_rejected';
    public const PATH_ORDER_CANCELLED = self::STATUS_CANCELLED;
    public const PATH_ORDER_VOIDED = self::STATUS_CANCELLED;
    public const PATH_ORDER_CHARGED_BACK = 'payment/cardpay/order_status_chargeback';
    public const PATH_ORDER_CHARGEBACK_RESOLVED = 'payment/cardpay/order_status_approved';
    public const PATH_ORDER_IN_MEDIATION = 'payment/cardpay/order_status_in_mediation';
    public const PATH_ORDER_TERMINATED = self::STATUS_CANCELLED;
    public const PATH_ORDER_REFUNDED = 'payment/cardpay/order_status_refunded';
    public const PATH_ORDER_PARTIALLY_REFUNDED = 'payment/cardpay/order_status_partially_refunded';
    public const PATH_ORDER_REFUND_AVAILABLE = 'payment/cardpay/refund_available';
    public const PATH_ORDER_CANCEL_AVAILABLE = 'payment/cardpay/cancel_payment';

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
}