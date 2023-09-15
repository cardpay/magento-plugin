<?php

namespace Cardpay\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as LoggerAlias;

/**
 * Unlimit logger handler
 */
class System extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = LoggerAlias::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/cardpay.log';
}
