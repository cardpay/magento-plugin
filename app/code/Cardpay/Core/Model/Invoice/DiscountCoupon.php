<?php

namespace Cardpay\Core\Model\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class DiscountCoupon
 *
 * @package Cardpay\Core\Model\Invoice
 */
class DiscountCoupon extends AbstractTotal
{
    /**
     * @param Invoice $invoice
     *
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $amount = $order->getDiscountCouponAmount();
        $baseAmount = $order->getBaseDiscountCouponAmount();
        if ($amount) {
            $invoice->setDiscountCouponAmount($amount);
            $invoice->setDiscountCouponAmount($baseAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}