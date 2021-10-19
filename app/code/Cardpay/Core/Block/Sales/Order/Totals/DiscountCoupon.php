<?php

namespace Cardpay\Core\Block\Sales\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Class DiscountCoupon
 *
 * @package Cardpay\Core\Block\Sales\Order\Totals
 */
class DiscountCoupon extends Template
{
    /**
     * @var DataObject
     */
    protected $_source;

    /**
     * Get data (totals) source model
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Add this total to parent
     */
    public function initTotals()
    {
        //this flow is a order page
        //if exist value in discount display in order
        if ((float)$this->getSource()->getDiscountCouponAmount() == 0) {
            return $this;
        }

        $total = new DataObject([
            'code' => 'discount_coupon',
            'field' => 'discount_coupon_amount',
            'value' => $this->getSource()->getDiscountCouponAmount(),
            'label' => __('Coupon discount of the Cardpay'),
        ]);

        $this->getParentBlock()->addTotalBefore($total, 'shipping');

        return $this;
    }
}
