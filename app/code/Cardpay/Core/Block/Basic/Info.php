<?php

namespace Cardpay\Core\Block\Basic;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;

class Info extends \Magento\Payment\Block\Info
{
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;

    protected $_template = 'Cardpay_Core::basic/info.phtml';

    public function __construct(
        Context      $context,
        Session      $checkoutSession,
        OrderFactory $orderFactory,
        array        $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
    }


    // Use this method to get ID
    public function getRealOrderId()
    {
        return $this->_checkoutSession->getLastOrderId();
    }

    public function getOrder()
    {
        if ($this->_checkoutSession->getLastRealOrderId()) {
            return $this->_checkoutSession->getLastRealOrder();
        }
        return false;
    }


    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        $order = $this->getOrder();
        return $order->getPayment();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentMethod()
    {
        $payment = $this->getPayment();

        $payment_method = $payment->getMethod();

        if (!$payment_method) {
            $payment_method = $payment->getMethodInstance()->getCode();
        }

        return $payment_method;
    }

    public function getAdditionalInformation()
    {
        $payment = $this->getPayment();
        if ($payment) {
            return $payment->getAdditionalInformation();
        } else {
            return null;
        }
    }

    public function getTransactionId()
    {
        $payment = $this->getPayment();
        $ret = $payment->getTransactionId();

        if (!$ret) {
            $ret = $payment->getAdditionalInformation('transaction_id');
        }

        if (!$ret) {
            $ret = $this->getInfo()->getAdditionalInformation('transaction_id');
        }

        return ($ret);
    }

    public function getStatusMessage($payment)
    {
        $additionInfo = $payment->getAdditionalInformation();
        return ($additionInfo['status_message']);
    }

    public function getPaymentInfo()
    {
        $payment = $this->getPayment();
        return $payment->getAdditionalInformation("paymentResponse");
    }
}