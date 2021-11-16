<?php

namespace Cardpay\Core\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;

class AbstractSuccess extends Template
{
    /**
     * @var \Cardpay\Core\Model\Factory
     */
    protected $_coreFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Cardpay\Core\Model\CoreFactory $coreFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context   $context,
        \Cardpay\Core\Model\CoreFactory                    $coreFactory,
        \Magento\Sales\Model\OrderFactory                  $orderFactory,
        \Magento\Checkout\Model\Session                    $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array                                              $data = []
    )
    {
        $this->_coreFactory = $coreFactory;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;

        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        $order = $this->getOrder();
        return $order->getPayment();
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
    }

    /**
     * @return float|string
     */
    public function getTotal()
    {
        $order = $this->getOrder();
        $total = $order->getBaseGrandTotal();

        if (!$total) {
            $total = $order->getBasePrice() + $order->getBaseShippingAmount();
        }

        return number_format($total, 2, '.', '');
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getPaymentMethod()
    {
        return $this->getPayment()->getMethodInstance()->getCode();
    }

    /**
     * @return array
     */
    public function getInfoPayment()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_coreFactory->create()->getInfoPaymentByOrder($orderId);
    }

    /**
     * Return a message to show in success page
     *
     * @param object $payment
     *
     * @return string
     */
    public function getMessageByStatus($payment)
    {
        $status = !empty($payment['status']) ? $payment['status'] : '';
        $status_detail = !empty($payment['status_detail']) ? $payment['status_detail'] : '';
        $payment_method = !empty($payment['payment_method_id']) ? $payment['payment_method_id'] : '';
        $amount = !empty($payment['transaction_amount']) ? $payment['transaction_amount'] : '';
        $installments = !empty($payment['installments']) ? $payment['installments'] : '';

        return $this->_coreFactory->create()->getMessageByStatus($status, $status_detail, $payment_method, $installments, $amount);
    }

    /**
     * Return a url to go to order detail page
     *
     * @return string
     */
    public function getOrderUrl()
    {
        $params = [
            'order_id' => $this->_checkoutSession->getLastRealOrder()->getId()
        ];

        return $this->_urlBuilder->getUrl('sales/order/view', $params);
    }

    public function getReOrderUrl()
    {
        $params = [
            'order_id' => $this->_checkoutSession->getLastRealOrder()->getId()
        ];

        return $this->_urlBuilder->getUrl('sales/order/reorder', $params);
    }
}