<?php

namespace Cardpay\Core\Exceptions;

use Exception;
use Magento\Framework\Exception\LocalizedException;

class UnlimitBaseException extends LocalizedException
{
    protected $httpStatus;

    /**
     * UnlimitBaseException constructor.
     *
     * @param string $message
     * @param Exception|null $cause
     * @param int|null $httpStatus
     */
    public function __construct($message, Exception $cause = null, $httpStatus = null)
    {
        parent::__construct(__($message), $cause);
        $this->httpStatus = $httpStatus;
    }

    /**
     * Get the HTTP status associated with the exception.
     *
     * @return int|null
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }
}

