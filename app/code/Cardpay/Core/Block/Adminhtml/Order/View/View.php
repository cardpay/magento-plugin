<?php

namespace Cardpay\Core\Block\Adminhtml\Order\View;

class View extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    public function translatePaymentStatus()
    {
        return __('Payment status');
    }
}
