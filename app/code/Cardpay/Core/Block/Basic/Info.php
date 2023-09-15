<?php

namespace Cardpay\Core\Block\Basic;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Payment;

class Info extends \Magento\Payment\Block\Info
{
    protected $_template = 'Cardpay_Core::basic/info.phtml';

    /**
     * @var Session
     */
    private $_checkoutSession;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        array   $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
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
     * @return Payment
     */
    public function getPayment()
    {
        $order = $this->getOrder();
        return $order->getPayment();
    }

    /**
     * @return string
     * @throws LocalizedException
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
        }

        return null;
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

        return $ret;
    }

    public function getStatusMessage($payment)
    {
        $additionInfo = $payment->getAdditionalInformation();
        return ($additionInfo['status_message']);
    }

    public function getPaymentInfo()
    {
        $payment = $this->getPayment();
        return $payment->getAdditionalInformation('paymentResponse');
    }
}