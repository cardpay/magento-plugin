<?php

namespace Cardpay\Core\Helper\Message;

/**
 * Interface MessageInterface
 *
 * @package Cardpay\Core\Helper\Message
 */
interface MessageInterface
{
    /**
     * Return message array based on subclass
     *
     * @return mixed
     */
    public function getMessageMap();

    /**
     * @param      $key
     *
     * @return string
     */
    public function getMessage($key);
}