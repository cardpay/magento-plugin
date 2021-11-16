<?php

namespace Cardpay\Core\Model\Quote;

use Cardpay\Core\Helper\ConfigData;
use Magento\Checkout\Model\Session;
use Magento\Customer\Helper\Address;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Store\Model\ScopeInterface;

/**
 * Class DiscountCoupon
 *
 * @package Cardpay\Core\Model\Quote
 */
class DiscountCoupon extends AbstractTotal
{
    /**
     * @var Registry
     */
    protected $_registry;

    protected $scopeConfig;

    protected $checkoutSession;

    /**
     * DiscountCoupon constructor.
     *
     * @param Registry $registry
     */
    public function __construct(
        Session              $checkoutSession,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->setCode('discount_coupon');
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Determine if should apply subtotal
     *
     * @param $address
     * @param $shippingAssignment
     *
     * @return bool
     */
    protected function _getDiscountCondition($address)
    {

        $condition = true;

        $showDiscountAvailable = $this->scopeConfig->isSetFlag(ConfigData::PATH_ADVANCED_CONSIDER_DISCOUNT, ScopeInterface::SCOPE_STORE);

        if ($showDiscountAvailable === false) {
            $condition = false;
        }

        if ((string)$address->getAddressType() !== Address::TYPE_SHIPPING) {
            $condition = false;
        }

        return $condition;
    }

    /**
     * Return discount amount stored
     *
     * @return mixed
     */
    protected function _getDiscountAmount()
    {
        $amount = $this->checkoutSession->getData("cardpay_discount_amount");
        return $amount * -1;
    }

    /**
     * Collect address discount amount
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     *
     * @return $this
     */
    public function collect(
        Quote                       $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total                       $total
    )
    {
        $address = $shippingAssignment->getShipping()->getAddress();

        $balance = 0;

        if ($this->_getDiscountCondition($address)) {
            parent::collect($quote, $shippingAssignment, $total);
            $balance = $this->_getDiscountAmount();
        }

        //sets
        $address->setDiscountCouponAmount($balance);
        $address->setBaseDiscountCouponAmount($balance);

        //sets totals
        $total->setDiscountCouponDescription($this->getCode());
        $total->setDiscountCouponAmount($balance);
        $total->setBaseDiscountCouponAmount($balance);

        $total->addTotalAmount($this->getCode(), $address->getDiscountCouponAmount());
        $total->addBaseTotalAmount($this->getCode(), $address->getBaseDiscountCouponAmount());

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     *
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $showDiscountAvailable = $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_CONSIDER_DISCOUNT, ScopeInterface::SCOPE_STORE);
        $result = null;

        if ($showDiscountAvailable) {
            $amount = $total->getDiscountCouponAmount();

            $result = [
                'code' => $this->getCode(),
                'title' => __('Coupon discount of the Cardpay'),
                'value' => $amount
            ];

        }

        return $result;
    }
}