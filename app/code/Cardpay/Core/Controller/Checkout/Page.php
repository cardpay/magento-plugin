<?php

namespace Cardpay\Core\Controller\Checkout;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Topics\Payment;
use Exception;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Page
 * @package Cardpay\Core\Controller\Checkout
 */
class Page extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Cardpay\Core\Helper\Data
     */
    protected $_helperData;

    /**
     * @var Core
     */
    protected $_core;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_catalogSession;

    /**
     * @var
     */
    protected $_configData;

    /**
     * @var
     */
    protected $_paymentNotification;

    /**
     * Page constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param LoggerInterface $logger
     * @param Data $helperData
     * @param ScopeConfigInterface $scopeConfig
     * @param Core $core
     * @param CatalogSession $catalogSession
     * @param Payment $paymentNotification
     */

    public function __construct(
        Context              $context,
        Session              $checkoutSession,
        OrderFactory         $orderFactory,
        OrderSender          $orderSender,
        LoggerInterface      $logger,
        Data                 $helperData,
        ScopeConfigInterface $scopeConfig,
        Core                 $core,
        CatalogSession       $catalogSession,
        Payment              $paymentNotification
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_logger = $logger;
        $this->_helperData = $helperData;
        $this->_scopeConfig = $scopeConfig;
        $this->_core = $core;
        $this->_catalogSession = $catalogSession;
        $this->_paymentNotification = $paymentNotification;

        parent::__construct($context);
    }

    /**
     * Controller action
     * <magento_url>/cardpay/checkout/page
     */
    public function execute()
    {
        $order = $this->_getOrder();
        $payment = $order->getPayment();
        $paymentResponse = $payment->getAdditionalInformation("paymentResponse");

        $id = null;
        $url = '';

        //checkout Custom Credit Card
        if (isset($paymentResponse['redirect_url'])) {
            $url = $paymentResponse['redirect_url'];
        }

        if (empty($url)) {
            $type = isset($paymentResponse['payment_data']) ? 'payment_data' : 'recurring_data';

            if (isset($paymentResponse[$type]) && isset($paymentResponse[$type]['id'])) {
                $id = $paymentResponse[$type]['id'];
            }

            if ($id != null) {
                $this->approvedValidation($paymentResponse);
                $url = 'checkout/onepage/success';
            } else {
                $url = 'checkout/onepage/failure/';
            }
        }

        $this->_redirect($url);
    }

    /**
     * @return mixed
     */
    protected function _getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
    }

    /**
     * @param $payment
     * @throws Exception
     */
    public function approvedValidation($payment)
    {
        $type = isset($payment['payment_data']) ? 'payment_data' : 'recurring_data';

        if (isset($payment[$type]) && isset($payment[$type]['id'])) {
            if ($this->_scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_BINARY_MODE, ScopeInterface::SCOPE_STORE)) {
                $type = isset($payment['payment_data']) ? 'payment_data' : 'recurring_data';

                $id = $payment[$type]['id'];
                $paymentResponse = $this->_core->getPaymentV1($id);
                if ($paymentResponse['status'] == 200) {
                    $this->_paymentNotification->updateStatusOrderByPayment($paymentResponse['response']);
                }
            }

            $this->dispatchSuccessActionObserver();
        }
    }

    /**
     * Dispatch checkout_onepage_controller_success_action
     */
    public function dispatchSuccessActionObserver()
    {
        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            [
                'order_ids' => [$this->_getOrder()->getId()],
                'order' => $this->_getOrder()
            ]
        );
    }
}