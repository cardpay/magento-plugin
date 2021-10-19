<?php

namespace Cardpay\Core\Block\Analytics;

use Magento\Catalog\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class AfterCheckout extends Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_catalogSession;

    public function __construct(
        Context $context,
        Session $catalogSession,
        array   $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_catalogSession = $catalogSession;
    }

    public function getPaymentData()
    {
        return $this->_catalogSession->getPaymentData();
    }
}