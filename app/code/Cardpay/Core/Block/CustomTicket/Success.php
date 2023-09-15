<?php

namespace Cardpay\Core\Block\CustomTicket;

use Cardpay\Core\Block\AbstractSuccess;

/**
 * Class Success
 *
 * @package Cardpay\Core\Block\CustomTicket
 */
class Success extends AbstractSuccess
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('custom_ticket/success.phtml');
    }
}