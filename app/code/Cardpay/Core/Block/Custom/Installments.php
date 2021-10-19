<?php

namespace Cardpay\Core\Block\Custom;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Installments extends Template
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Http
     */
    private $request;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Http    $request,
        array   $data = []
    )
    {
        parent::__construct($context, $data);

        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
    }

    public function getInstallmentsUrl()
    {
        if (is_null($this->request)
            || 'checkout' !== $this->request->getModuleName()
            || 'index' !== $this->request->getControllerName()
            || 'index' !== $this->request->getActionName()) {
            return '';
        }

        $objectManager = ObjectManager::getInstance();
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currentStore = $storeManager->getStore();
        $baseUrl = $currentStore->getBaseUrl();

        return $baseUrl . 'cardpay/checkout/installments?cartId=' . $this->checkoutSession->getQuote()->getId();
    }
}