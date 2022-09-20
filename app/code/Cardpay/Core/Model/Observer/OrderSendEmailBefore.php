<?php

namespace Cardpay\Core\Model\Observer;

use Cardpay\Core\Model\Payment\BankCardPayment;
use Cardpay\Core\Model\Payment\BoletoPayment;
use Cardpay\Core\Model\Payment\PixPayment;
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

        $method = (string)$transport->getOrder()->getPayment()->getMethod();
        if ($method === BankCardPayment::CODE || $method === BoletoPayment::CODE || $method === PixPayment::CODE) {
            $payment_html = preg_replace('#<(' . implode('|', ["tr"]) . ')(?:[^>]+)?>.*?</\1>#s', '', $transport->getPaymentHtml());
            $transport->setPaymentHtml($payment_html);
            $observer->setTransport($transport);
        }
    }
}
