<?php

namespace Cardpay\Core\Block\Basic;

use Magento\Framework\View\Element\Template;

class Failure extends Template
{
    /**
     * Failure construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('basic/failure.phtml');
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return __('The order was not successful!');
    }
}
