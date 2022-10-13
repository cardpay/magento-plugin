<?php

namespace Cardpay\Core\Model\Quote;

use Magento\Checkout\Model\Session;
use Magento\Customer\Helper\Address;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class FinanceCost
 *
 * @package Cardpay\Core\Model\Quote
 */
class FinanceCost extends AbstractTotal
{

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Request object
     *
     * @var RequestInterface
     */
    protected $_request;

    /**
     * FinanceCost constructor.
     *
     * @param Registry $registry
     */
    public function __construct(
        Registry         $registry,
        Session          $checkoutSession,
        RequestInterface $request
    )
    {
        $this->setCode('finance_cost');
        $this->_registry = $registry;
        $this->_checkoutSession = $checkoutSession;
        $this->_request = $request;
    }

    /**
     * Determine if should apply subtotal
     *
     * @param $address
     * @param $shippingAssignment
     *
     * @return bool
     */
    protected function _getFinancingCondition($address, $shippingAssignment)
    {
        $items = $shippingAssignment->getItems();

        return ((string)$address->getAddressType() === Address::TYPE_SHIPPING
            && count($items)
            && $this->_request->getModuleName() === 'cardpay'
        );
    }

    /**
     * Return subtotal quote
     *
     * @return float
     */
    protected function _getSubtotalAmount()
    {
        $quote = $this->_checkoutSession->getQuote();

        return $quote->getSubtotalWithDiscount() + $quote->getShippingAddress()->getShippingAmount();
    }

    /**
     * Return subtotal quote
     *
     * @return float
     */
    protected function _getTaxAmount()
    {
        $totals = $this->_checkoutSession->getQuote()->getTotals();
        $tax = 0;
        if (isset($totals['tax'])) {
            $tax = ($totals['tax']->getValue() > 0) ? $totals['tax']->getValue() : 0;

        }

        return $tax;
    }

    /**
     * Return mp discount
     *
     * @return float|int
     */
    protected function _getDiscountAmount()
    {
        $quote = $this->_checkoutSession->getQuote();
        $totals = $quote->getShippingAddress()->getTotals();

        return (isset($totals['discount_coupon'])) ? $totals['discount_coupon']['value'] : 0;
    }

    /**
     * Caluclate finance cost amount
     *
     * @return int|mixed
     */
    protected function _getFinanceCostAmount()
    {
        $totalAmount = $this->_registry->registry('cardpay_total_amount');
        if (empty($totalAmount)) {
            return 0;
        }

        $initAmount = $this->_getSubtotalAmount();
        $discountAmount = $this->_getDiscountAmount();
        $taxAmount = $this->_getTaxAmount();

        return $totalAmount - $initAmount - $discountAmount - $taxAmount;
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
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        $address = $shippingAssignment->getShipping()->getAddress();

        if ($this->_getFinancingCondition($address, $shippingAssignment)) {
            parent::collect($quote, $shippingAssignment, $total);

            $balance = $this->_getFinanceCostAmount();

            $address->setFinanceCostAmount($balance);
            $address->setBaseFinanceCostAmount($balance);

            $total->setFinanceCostDescription($this->getCode());
            $total->setFinanceCostAmount($balance);
            $total->setBaseFinanceCostAmount($balance);

        }

        $total->addTotalAmount($this->getCode(), $address->getFinanceCostAmount());
        $total->addBaseTotalAmount($this->getCode(), $address->getBaseFinanceCostAmount());

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
        $amount = $total->getFinanceCostAmount();

        return [
            'code' => $this->getCode(),
            'title' => __('Financing Cost'),
            'value' => $amount
        ];
    }
}
