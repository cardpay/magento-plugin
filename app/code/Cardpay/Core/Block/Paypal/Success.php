<?php

namespace Cardpay\Core\Block\Paypal;

use Cardpay\Core\Block\AbstractSuccess;

/**
 * Class Success
 *
 * @package Cardpay\Core\Block\Paypal
 */
class Success extends AbstractSuccess
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paypal/success.phtml');
    }
}