<?php

namespace Cardpay\Core\Logger;

/**
 * Unlimint custom logger allows name changing to differentiate log call origin
 * Class Logger
 *
 * @package Cardpay\Core\Logger
 */
class Logger extends \Monolog\Logger
{
    /**
     * Set logger name
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
