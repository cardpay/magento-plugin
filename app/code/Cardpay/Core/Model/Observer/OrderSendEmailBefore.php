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
        if ($transport->getOrder()->getPayment()->getMethod() == \Cardpay\Core\Model\Custom\Payment::CODE ||
            $transport->getOrder()->getPayment()->getMethod() == \Cardpay\Core\Model\CustomTicket\Payment::CODE) {
            $payment_html = preg_replace('#<(' . implode('|', ["tr"]) . ')(?:[^>]+)?>.*?</\1>#s', '', $transport->getPaymentHtml());
            $transport->setPaymentHtml($payment_html);
            $observer->setTransport($transport);
        }
    }
}