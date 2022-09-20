<?php

namespace Cardpay\Core\Helper\Message;

/**
 * Map Payment Messages with the Credit Card Payment response detail
 * @package Cardpay\Core\Helper
 */
class StatusDetailMessage extends AbstractMessage
{
    /**
     * Map error messages
     *
     * @var array
     */
    protected $messagesMap = [
        "cc_rejected_bad_filled_card_number" => 'Check the card number.',
        "cc_rejected_bad_filled_date" => 'Check the expiration date.',
        "cc_rejected_bad_filled_other" => 'Check the data.',
        "cc_rejected_bad_filled_security_code" => 'Check the CVV2/CVC2.',
        "cc_rejected_blacklist" => 'We could not process your payment.',
        "cc_rejected_call_for_authorize" => 'You must authorize to %1 the payment of $ %2 to Cardpay.',
        "cc_rejected_card_disabled" => 'Call %1 to activate your card.<br/>The phone is on the back of your card.',
        "cc_rejected_card_error" => 'We could not process your payment.',
        "cc_rejected_duplicated_payment" => 'You already made a payment by that value.<br/>If you need to repay, use another card or other payment method.',
        "cc_rejected_high_risk" => 'Your payment was rejected.<br/>Choose another payment method, we recommend cash methods.',
        "cc_rejected_insufficient_amount" => 'Your %1 do not have sufficient funds.',
        "cc_rejected_invalid_installments" => '%1 does not process payments in %2 installments.',
        "cc_rejected_max_attempts" => 'You have got to the limit of allowed attempts.<br/>Choose another card or another payment method.',
        "cc_rejected_other_reason" => '%1 did not process the payment.',
    ];

    /**
     * Return array map error mesages
     *
     * @return array
     */
    public function getMessageMap()
    {
        return $this->messagesMap;
    }
}
