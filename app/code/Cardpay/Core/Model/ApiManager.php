<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\Data as dataHelper;
use Cardpay\Core\Lib\Api;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;

class ApiManager
{
    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var dataHelper
     */
    protected $_coreHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        dataHelper $coreHelper,
        OrderFactory $orderFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_coreHelper = $coreHelper;
        $this->_orderFactory = $orderFactory;
    }

    public function postPayment($requestParams, $order = null)
    {
        $url = "/api/payments";

        /**
         * @var Api
         */
        $api = $this->getApiInstance($order);
        $response = $api->post($url, $requestParams);

        $this->_coreHelper->log('Core Post Payment Return', 'cardpay', $response);

        return $response;
    }

    public function getApiInstance($order = null)
    {
        if (is_null($order)) {
            $orderId = $this->getQuote()->getReservedOrderId();
            $order = $this->getOrder($orderId);
        }

        return $this->_coreHelper->getApiInstance($order);
    }

    protected function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    public function getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Return response of api to a preference
     *
     * @param $preference
     *
     * @return array
     * @throws LocalizedException|\Exception
     */
    public function postRefund($idPayment)
    {
        /**
         * @var Api
         */
        $api = $this->getApiInstance();

        $response = $api->refund($idPayment);

        $this->_coreHelper->log('Core Cancel Payment Return', 'cardpay', $response);

        return $response;
    }

    public function getPayment($paymentId)
    {
        $api = $this->getApiInstance();

        return $api->get('/api/payments/'.$paymentId);
    }

    public function getMerchantOrder($merchantOrderId)
    {
        $api = $this->getApiInstance();

        return $api->get('/merchant_orders/'.$merchantOrderId);
    }

    public function getApiPayment($order, $paymentId)
    {
        $api = $this->_coreHelper->getApiInstance($order);
        return $api->get('/api/payments/'.$paymentId);
    }
}
