<?php

namespace Cardpay\Core\Observer;

use Cardpay\Core\Model\Payment\BankCardPayment;
use Cardpay\Core\Model\Payment\BoletoPayment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderSendEmailBefore implements ObserverInterface
{
    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        $transport = $observer->getTransport();
        if (is_array($transport)) {
            return;
        }

        $methodCode = (string)$transport->getOrder()->getPayment()->getMethod();
        if ($methodCode === BankCardPayment::CODE || $methodCode === BoletoPayment::CODE) {
            $paymentHtml = preg_replace('#<(' . implode('|', ["tr"]) . ')(?:[^>]+)?>.*?</\1>#s', '', $transport->getPaymentHtml());
            $transport->setPaymentHtml($paymentHtml);
            $observer->setTransport($transport);
        }
    }
}