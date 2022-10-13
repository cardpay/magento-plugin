<?php

namespace Cardpay\Core\Controller\Customtpix;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Success
 *
 * @package Cardpay\Core\Controller\Customticket
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
        $this->_view->loadLayout(['default', 'cardpay_custompix_success']);

        $this->_view->renderLayout();
    }
}
