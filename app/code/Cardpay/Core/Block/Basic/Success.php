<?php

namespace Cardpay\Core\Block\Basic;

use Cardpay\Core\Block\AbstractSuccess;

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
