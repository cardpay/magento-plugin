<?php

namespace Cardpay\Core\Helper;

class ConfigData
{
    // credentials path
    const PATH_TERMINAL_CODE = 'payment/cardpay/terminal_code';
    const PATH_TERMINAL_PASSWORD = 'payment/cardpay/terminal_password';
    const PATH_CALLBACK_SECRET = 'payment/cardpay/callback_secret';
    const PATH_SANDBOX = 'payment/cardpay/sandbox';

    // configuration hidden path
    const PATH_SITE_ID = 'payment/cardpay/site_id';
    const PATH_SPONSOR_ID = 'payment/cardpay/sponsor_id';

    // custom method credit and debit card
    const PATH_CUSTOM_ACTIVE = 'payment/cardpay_custom/active';
    const PATH_CUSTOM_BINARY_MODE = 'payment/cardpay_custom/binary_mode';
    const PATH_CUSTOM_STATEMENT_DESCRIPTOR = 'payment/cardpay_custom/statement_descriptor';
    const PATH_CUSTOM_BANNER = 'payment/cardpay_custom/banner_checkout';
    const PATH_CUSTOM_COUPON = 'payment/cardpay_custom/coupon_cardpay';
    const PATH_CUSTOM_GATEWAY_MODE = 'payment/cardpay_custom/gateway_mode';
    const PATH_CUSTOM_ASK_CPF = 'payment/cardpay_custom/ask_cpf';
    const PATH_CUSTOM_INSTALLMENT = 'payment/cardpay_custom/installment';
    const PATH_CUSTOM_DESCRIPTOR = 'payment/cardpay_custom/descriptor';
    const PATH_CUSTOM_CAPTURE = 'payment/cardpay_custom/capture';

    // custom method ticket
    const PATH_CUSTOM_TICKET_ACTIVE = 'payment/cardpay_customticket/active';
    const PATH_CUSTOM_TICKET_COUPON = 'payment/cardpay_customticket/coupon_cardpay';
    const PATH_CUSTOM_TICKET_BANNER = 'payment/cardpay_customticket/banner_checkout';
    const PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_customticket/excluded_payment_methods';

    // custom method bank transfer
    const PATH_CUSTOM_BANK_TRANSFER_ACTIVE = 'payment/cardpay_custom_bank_transfer/active';
    const PATH_CUSTOM_BANK_TRANSFER_BANNER = 'payment/cardpay_custom_bank_transfer/banner_checkout';
    const PATH_CUSTOM_BANK_TRANSFER_REDIRECT_PAYER = 'payment/cardpay_custom_bank_transfer/redirect_payer';

    // basic method
    const PATH_BASIC_ACTIVE = 'payment/cardpay_basic/active';
    const PATH_BASIC_TITLE = 'payment/cardpay_basic/title';
    const PATH_BASIC_URL_FAILURE = 'payment/cardpay_basic/url_failure';
    const PATH_BASIC_AUTO_RETURN = 'payment/cardpay_basic/auto_return';
    const PATH_BASIC_EXCLUDE_PAYMENT_METHODS = 'payment/cardpay_basic/excluded_payment_methods';
    const PATH_BASIC_STATEMENT_DESCRIPTION = 'payment/cardpay_basic/statement_desc';
    const PATH_BASIC_EXPIRATION_TIME_PREFERENCE = 'payment/cardpay_basic/exp_time_pref';
    const PATH_BASIC_ORDER_STATUS = 'payment/cardpay_basic/order_status';
    const PATH_BASIC_BINARY_MODE = 'payment/cardpay_basic/binary_mode';
    const PATH_BASIC_GATEWAY_MODE = 'payment/cardpay_basic/gateway_mode';

    // order configuration
    const STATUS_CANCELLED = 'payment/cardpay/order_status_cancelled';
    const STATUS_IN_PROCESS = 'payment/cardpay/order_status_in_process';

    const PATH_ORDER_AUTHORIZED = 'payment/cardpay/order_status_authorized';
    const PATH_ORDER_IN_PROCESS = self::STATUS_IN_PROCESS;
    const PATH_ORDER_NEW = 'payment/cardpay/order_status_pending';
    const PATH_ORDER_COMPLETED = self::STATUS_IN_PROCESS;
    const PATH_ORDER_DECLINED = 'payment/cardpay/order_status_rejected';
    const PATH_ORDER_CANCELLED = self::STATUS_CANCELLED;
    const PATH_ORDER_VOIDED = self::STATUS_CANCELLED;
    const PATH_ORDER_CHARGED_BACK = 'payment/cardpay/order_status_chargeback';
    const PATH_ORDER_CHARGEBACK_RESOLVED = 'payment/cardpay/order_status_approved';
    const PATH_ORDER_IN_MEDIATION = 'payment/cardpay/order_status_in_mediation';
    const PATH_ORDER_TERMINATED = self::STATUS_CANCELLED;
    const PATH_ORDER_REFUNDED = 'payment/cardpay/order_status_refunded';
    const PATH_ORDER_PARTIALLY_REFUNDED = 'payment/cardpay/order_status_partially_refunded';
    const PATH_ORDER_REFUND_AVAILABLE = 'payment/cardpay/refund_available';
    const PATH_ORDER_CANCEL_AVAILABLE = 'payment/cardpay/cancel_payment';

    // advanced configuration
    const PATH_ADVANCED_LOG = 'payment/cardpay/logs';
    const PATH_ADVANCED_COUNTRY = 'payment/cardpay/country';
    const PATH_ADVANCED_CATEGORY = 'payment/cardpay/category_id';
    const PATH_ADVANCED_SUCCESS_PAGE = 'payment/cardpay/use_successpage';
    const PATH_ADVANCED_CONSIDER_DISCOUNT = 'payment/cardpay/consider_discount';
    const PATH_ADVANCED_EMAIL_CREATE = 'payment/cardpay/email_order_create';
    const PATH_ADVANCED_EMAIL_UPDATE = 'payment/cardpay/email_order_update';

    // log
    const BASIC_LOG_PREFIX = 'cardpay-basic';
    const CUSTOM_LOG_PREFIX = 'cardpay-custom';
}