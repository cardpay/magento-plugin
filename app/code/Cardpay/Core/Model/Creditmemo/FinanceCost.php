<?php

namespace Cardpay\Core\Model\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class FinanceCost
 *
 * @package Cardpay\Core\Model\Creditmemo
 */
class FinanceCost extends AbstractTotal
{
    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $amount = $order->getFinanceCostAmount();
        $baseAmount = $order->getBaseFinanceCostAmount();
        if ($amount) {
            $creditmemo->setFinanceCostAmount($amount);
            $creditmemo->setBaseFinanceCostAmount($baseAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}