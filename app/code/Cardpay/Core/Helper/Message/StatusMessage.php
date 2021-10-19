<?php

namespace Cardpay\Core\Helper\Message;

/**
 * Payment response user friendly messages
 */
class StatusMessage extends AbstractMessage
{
    const WE_ARE_PROCESSING_THE_PAYMENT_MESSAGE = 'We are processing the payment.';

    /**
     * mapps messages by status
     *
     * @var array
     */
    protected $messagesMap = [
        'approved' => [
            'title' => 'Done, your payment was accredited!',
            'message' => ''
        ],

        'in_process' => [
            'title' => self::WE_ARE_PROCESSING_THE_PAYMENT_MESSAGE,
            'message' => 'In less than an hour we will send you by e-mail the result.'
        ],

        'authorized' => [
            'title' => self::WE_ARE_PROCESSING_THE_PAYMENT_MESSAGE,
            'message' => 'In less than an hour we will send you by e-mail the result.'
        ],

        'pending' => [
            'title' => self::WE_ARE_PROCESSING_THE_PAYMENT_MESSAGE,
            'message' => ''
        ],

        'rejected' => [
            'title' => 'We could not process your payment.',
            'message' => ''
        ],

        'cancelled' => [
            'title' => 'Payments were canceled.',
            'message' => 'Contact for more information.'
        ],

        'other' => [
            'title' => 'Thank you for your purchase!',
            'message' => ''
        ]
    ];

    /**
     * return array map
     *
     * @return array
     */
    public function getMessageMap()
    {
        return $this->messagesMap;
    }
}