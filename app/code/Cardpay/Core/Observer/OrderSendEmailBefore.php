<?php

namespace Cardpay\Core\Observer;

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

        $methodCode = $transport->getOrder()->getPayment()->getMethod();
        if ($methodCode == \Cardpay\Core\Model\Custom\Payment::CODE || $methodCode == \Cardpay\Core\Model\CustomTicket\Payment::CODE) {
            $paymentHtml = preg_replace('#<(' . implode('|', ["tr"]) . ')(?:[^>]+)?>.*?</\1>#s', '', $transport->getPaymentHtml());
            $transport->setPaymentHtml($paymentHtml);
            $observer->setTransport($transport);
        }
    }
}