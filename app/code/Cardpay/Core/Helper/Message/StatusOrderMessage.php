<?php

namespace Cardpay\Core\Helper\Message;

/**
 * Map Payment Messages with the Payment response
 */
class StatusOrderMessage extends AbstractMessage
{
    /**
     * Message by status order
     *
     * @var array
     */
    protected $messagesMap = [
        'approved' => 'Automatic notification of the Cardpay: The payment was approved.',
        'refunded' => 'Automatic notification of the Cardpay: The payment was refunded.',
        'pending' => 'Automatic notification of the Cardpay: The payment is being processed.',
        'in_process' => 'Automatic notification of the Cardpay: The payment is being processed.'.
            ' Will be approved within 2 business days.',
        'in_mediation' => 'Automatic notification of the Cardpay: The payment is in the process of Dispute,'.
            ' check the graphic account of the Unlimit for more information.',
        'cancelled' => 'Automatic notification of the Cardpay: The payment was cancelled.',
        'rejected' => 'Automatic notification of the Cardpay: The payment was rejected.',
        'charged_back' => 'Automatic notification of the Cardpay: One chargeback was initiated for this payment.',
    ];

    /**
     * Return array map
     *
     * @return array
     */
    public function getMessageMap()
    {
        return $this->messagesMap;
    }
}