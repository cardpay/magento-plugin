<?php

namespace Cardpay\Core\Block\Basic;

use Cardpay\Core\Block\AbstractSuccess;

/**
 * Class Success
 * @package Cardpay\Core\Block\Basic
 */
class Success extends AbstractSuccess
{
    /**
     * Success constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('basic/success.phtml');
    }
}
