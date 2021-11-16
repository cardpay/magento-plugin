<?php

namespace Cardpay\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;

/**
 * Unlimint logger handler
 */
class System extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/cardpay.log';
}