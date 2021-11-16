<?php

namespace Cardpay\Core\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddMpSubtotalsToOrderObserver implements ObserverInterface
{
    /**
     * Add subtotals to order data
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $order = $observer->getOrder();
        $quote = $observer->getQuote();

        $discountCoupon = $quote->getShippingAddress()->getDiscountCouponAmount();
        $baseDiscountCoupon = $quote->getShippingAddress()->getBaseDiscountCouponAmount();

        $financeCost = $quote->getShippingAddress()->getFinanceCostAmount();
        $baseFinanceCost = $quote->getShippingAddress()->getBaseFinanceCostAmount();


        if (!empty($discountCoupon)) {
            $order->setDiscountCouponAmount($discountCoupon);
            $order->setBaseDiscountCouponAmount($baseDiscountCoupon);
        }

        if (!empty($financeCost)) {
            $order->setFinanceCostAmount($financeCost);
            $order->setBaseFinanceCostAmount($baseFinanceCost);
        }

        if ((string)$order->getPayment()->getMethod() === 'cardpay_standard') {
            $order->setFinanceCostAmount(0);
            $order->setBaseFinanceCostAmount(0);
        }

        return $this;
    }
}
