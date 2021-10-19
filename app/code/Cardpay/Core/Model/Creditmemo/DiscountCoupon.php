<?php

namespace Cardpay\Core\Model\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class DiscountCoupon
 *
 * @package Cardpay\Core\Model\Creditmemo
 */
class DiscountCoupon extends AbstractTotal
{
    /**
     * @param Creditmemo $creditmemo
     *
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $amount = $order->getDiscountCouponAmount();
        $baseAmount = $order->getBaseDiscountCouponAmount();
        if ($amount) {
            $creditmemo->setDiscountCouponAmount($amount);
            $creditmemo->setBaseDiscountCouponAmount($baseAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}