<?php

namespace Cardpay\Core\Controller\Paypal;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Success
 *
 * @package Cardpay\Core\Controller\Paypal
 */
class Success extends Action
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * Success constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context      $context,
        Session      $checkoutSession,
        OrderFactory $orderFactory
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;

        parent::__construct(
            $context
        );
    }

    /**
     * Controller action
     */
    public function execute()
    {
        $this->_view->loadLayout(['default', 'cardpay_paypal_success']);

        $this->_view->renderLayout();
    }
}
