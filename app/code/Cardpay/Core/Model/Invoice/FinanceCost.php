<?php

namespace Cardpay\Core\Model\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class FinanceCost
 *
 * @package Cardpay\Core\Model\Invoice
 */
class FinanceCost extends AbstractTotal
{
    /**
     * @param Invoice $invoice
     *
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $amount = $order->getFinanceCostAmount();
        $baseAmount = $order->getBaseFinanceCostAmount();

        if ($amount) {
            $invoice->setFinanceCostAmount($amount);
            $invoice->setBaseFinanceCostAmount($baseAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}