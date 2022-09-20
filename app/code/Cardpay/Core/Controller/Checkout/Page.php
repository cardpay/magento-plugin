<?php

namespace Cardpay\Core\Controller\Checkout;

use Cardpay\Core\Controller\ParamsContainer\ParamContextContainer;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Topics\Payment as PaymentNotification;
use Exception;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Page extends Action
{
    const PAYMENT_DATA = 'payment_data';
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var Core
     */
    protected $_core;

    /**
     * @var Registry
     */
    protected $_catalogSession;

    /**
     * @var PaymentNotification
     */
    protected $_paymentNotification;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param LoggerInterface $logger
     * @param Data $helperData
     * @param ScopeConfigInterface $scopeConfig
     * @param Core $core
     * @param CatalogSession $catalogSession
     * @param PaymentNotification $paymentNotification
     */

    public function __construct(
        ParamContextContainer   $paramContainer,
        Session              $checkoutSession,
        OrderFactory         $orderFactory,
        OrderSender          $orderSender,
        LoggerInterface      $logger,
        CatalogSession       $catalogSession,
        PaymentNotification  $paymentNotification
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_logger = $logger;
        $this->_helperData = $paramContainer->getData();
        $this->_scopeConfig = $paramContainer->getScopeConfig();
        $this->_core = $paramContainer->getCore();
        $this->_catalogSession = $catalogSession;
        $this->_paymentNotification = $paymentNotification;

        parent::__construct($paramContainer->getContext());
    }

    /**
     * Controller action
     * <magento_url>/cardpay/checkout/page
     * @throws Exception
     */
    public function execute()
    {
        /**
         * @var Order
         */
        $order = $this->_getOrder();
        if (is_null($order) || is_null($order->getPayment())) {
            return null;
        }

        /**
         * @var Payment
         */
        $payment = $order->getPayment();
        $paymentResponse = $payment->getAdditionalInformation('paymentResponse');

        $id = null;
        $url = '';

        // checkout custom credit card
        if (isset($paymentResponse['redirect_url'])) {
            $url = $paymentResponse['redirect_url'];
        }

        if (empty($url)) {
            if (isset($paymentResponse[self::PAYMENT_DATA]['id'])) {
                $id = $paymentResponse[self::PAYMENT_DATA]['id'];
            }

            if (!is_null($id)) {
                $this->approvedValidation($order, $paymentResponse);
                $url = 'checkout/onepage/success';
            } else {
                $url = 'checkout/onepage/failure/';
            }
        }

        return $this->_redirect($url);
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
     * @param Order $order
     * @param array $paymentResponseArray
     * @throws LocalizedException|Exception
     */
    public function approvedValidation($order, $paymentResponseArray)
    {
        if (isset($paymentResponseArray[self::PAYMENT_DATA]['id'])) {
            if ($this->_scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_BINARY_MODE, ScopeInterface::SCOPE_STORE)) {
                $paymentId = $paymentResponseArray[self::PAYMENT_DATA]['id'];
                $paymentResponse = $this->_core->getApiPayment($order, $paymentId);
                if ((int)$paymentResponse['status'] === 200) {
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
