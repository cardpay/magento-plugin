<?php

namespace Cardpay\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

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
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/cardpay.log';
}