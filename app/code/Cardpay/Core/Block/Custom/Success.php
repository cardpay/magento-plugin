<?php

namespace Cardpay\Core\Block\Custom;

use Cardpay\Core\Block\AbstractSuccess;

/**
 * Class Success
 *
 * @package Cardpay\Core\Block\Custom
 */
class Success extends AbstractSuccess
{
    /**
     * Class constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('custom/success.phtml');
    }
}