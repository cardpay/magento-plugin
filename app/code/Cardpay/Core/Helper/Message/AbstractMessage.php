<?php

namespace Cardpay\Core\Helper\Message;

/**
 * Class AbstractMessage
 *
 * @package Cardpay\Core\Helper\Message
 */
abstract class AbstractMessage implements MessageInterface
{
    /**
     * Return message array based on subclass
     *
     * @return mixed
     */
    public abstract function getMessageMap();

    /**
     * Get message text from array based on key
     *
     * @param      $key
     *
     * @return string
     */
    public function getMessage($key)
    {
        $messageMap = $this->getMessageMap();
        if (isset($messageMap[$key])) {
            return $messageMap[$key];
        }

        return '';
    }
}