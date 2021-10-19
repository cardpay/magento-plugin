<?php

namespace Cardpay\Core\Controller\Api;

use Magento\Framework\App\Action\Action;

/**
 * Class Coupon
 *
 * @package Cardpay\Core\Controller\Api
 */
class Subtotals extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * Coupon constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context      $context,
        \Magento\Checkout\Model\Session            $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Registry                $registry
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->_registry = $registry;
    }

    /**
     * Fetch coupon info
     *
     * Controller Action
     */
    public function execute()
    {
        $total = $this->getRequest()->getParam('cost');
        $quote = $this->_checkoutSession->getQuote();

        //save value to DiscountCoupon collect
        $this->_registry->register('cardpay_total_amount', $total);
        $this->quoteRepository->save($quote->collectTotals());
    }
}