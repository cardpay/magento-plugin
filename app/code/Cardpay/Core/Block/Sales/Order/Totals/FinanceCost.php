<?php

namespace Cardpay\Core\Block\Sales\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

/**
 * Class FinanceCost
 *
 * @package Cardpay\Core\Block\Sales\Order\Totals
 */
class FinanceCost extends Template
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
        if ((float)$this->getSource()->getFinanceCostAmount() == 0
            || !$this->_scopeConfig->isSetFlag('payment/cardpay/financing_cost', ScopeInterface::SCOPE_STORE)) {
            return $this;
        }

        $total = new DataObject([
            'code' => 'finance_cost',
            'field' => 'finance_cost_amount',
            'value' => $this->getSource()->getFinanceCostAmount(),
            'label' => __('Financing Cost'),
        ]);

        $this->getParentBlock()->addTotalBefore($total, 'shipping');

        return $this;
    }
}
