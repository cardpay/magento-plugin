<?php

namespace Cardpay\Core\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Exception which thrown by Unlimint API in case of processable error codes
 * Class Cardpay_Core_Model_Api_Exception
 *
 * @package Cardpay\Core\Model\Api\Exception
 */
class Exception extends LocalizedException
{
    /**
     * Generic message to show by default
     */
    const GENERIC_USER_MESSAGE = "We could not process your payment in this moment. Please check the form data and retry later";

    const GENERIC_API_EXCEPTION_MESSAGE = "We could not process your payment in this moment. Please retry later";

    /**
     * @var array to map messages
     */
    protected $_messagesMap;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Constructor
     *
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase, ScopeConfigInterface $scopeConfig)
    {
        parent::__construct($phrase);
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Get error message which can be displayed to website user
     *
     * @return string
     */
    public function getUserMessage($error = null)
    {
        if (!empty($error)) {
            $code = $error['code'];
            if (isset($this->_messagesMap[$code])) {
                return __($this->_messagesMap[$code]);
            }
        }

        return __(self::GENERIC_USER_MESSAGE);
    }
}
