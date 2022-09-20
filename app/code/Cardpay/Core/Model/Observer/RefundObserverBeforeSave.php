<?php

namespace Cardpay\Core\Model\Observer;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Lib\Api;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Store\Model\ScopeInterface;

/**
 * Class RefundObserverBeforeSave
 *
 * @package Cardpay\Core\Observer
 */
class RefundObserverBeforeSave implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * RefundObserverBeforeSave constructor.
     *
     * @param Session $session
     * @param Context $context
     * @param Data $dataHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Session              $session,
        Context              $context,
        Data                 $dataHelper,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->session = $session;
        $this->messageManager = $context->getMessageManager();
        $this->dataHelper = $dataHelper;
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $creditMemo = $observer->getData('creditmemo');
        $order = $creditMemo->getOrder();
        $this->creditMemoRefundBeforeSave($order, $creditMemo);
    }

    /**
     * @param $order      Order
     * @param $creditMemo Creditmemo
     * @throws LocalizedException
     */
    protected function creditMemoRefundBeforeSave($order, $creditMemo)
    {
        // do not repeat the return of payment, if it is done through the Cardpay
        if ($order->getExternalRequest()) {
            return;
        }

        // get payment order object
        $paymentOrder = $order->getPayment();
        if (!$this->isPaymentMethodValid($order)) {
            return;
        }

        // check refund available
        $refundAvailable = (int)$this->scopeConfig->getValue(ConfigData::PATH_ORDER_REFUND_AVAILABLE, ScopeInterface::SCOPE_STORE);
        if ($refundAvailable === 0) {
            $this->dataHelper->log('RefundObserverBeforeSave::creditMemoRefundBeforeSave - Refund is disabled', ConfigData::CUSTOM_LOG_PREFIX);
            $this->throwRefundException(__('Refund is disabled'));
            return;
        }

        // get refund amount
        $amountToRefund = $creditMemo->getGrandTotal();
        if ($amountToRefund <= 0) {
            $this->throwRefundException(__('The refunded amount must be greater than 0.'));
            return;
        }

        // get payment info
        $paymentResponse = $paymentOrder->getAdditionalInformation('paymentResponse');
        if (!isset($paymentResponse['payment_data']['id'])) {
            $this->throwRefundException(__('Refund can not be executed because the payment id was not found.'));
            return;
        }

        // get payment Id
        $paymentID = $paymentResponse['payment_data']['id'];

        $this->performRefund($paymentID, $order, $amountToRefund);
    }

    private function isPaymentMethodValid($order)
    {
        // get payment order object
        $payment = $order->getPayment();
        $paymentMethod = (string)$payment->getMethodInstance()->getCode();

        return $paymentMethod === 'cardpay_custom'
            || $paymentMethod === ConfigData::BANKCARD_PAYMENT_METHOD
            || $paymentMethod === 'cardpay_basic';
    }

    /**
     * @param string $paymentID
     * @param Order $order
     * @param float $amountToRefund
     * @throws LocalizedException
     */
    private function performRefund(string $paymentID, Order $order, float $amountToRefund)
    {
        /**
         * @var Api
         */
        $api = $this->dataHelper->getApiInstance($order);

        $refundRequestParams = $this->dataHelper->getRefundRequestParams($paymentID, $order, $amountToRefund);
        $this->dataHelper->log('Refund request', ConfigData::CUSTOM_LOG_PREFIX, $refundRequestParams);

        $responseRefund = $api->refund($refundRequestParams);

        if (!is_null($responseRefund) || (int)$responseRefund['status'] === 200 || (int)$responseRefund['status'] === 201) {
            $successMessageRefund = 'Cardpay - ' . __('Refund of %1 was processed successfully.', $amountToRefund);
            $this->messageManager->addSuccessMessage($successMessageRefund);
            $this->dataHelper->log('RefundObserverBeforeSave::creditMemoRefundBeforeSave - ' . $successMessageRefund, ConfigData::CUSTOM_LOG_PREFIX, $responseRefund);
        } else {
            $this->throwRefundException(__('Could not process the refund, The Cardpay API returned an unexpected error. Check the log files.'), $responseRefund);
        }
    }

    /**
     * @throws LocalizedException
     */
    protected function throwRefundException($message, $data = [])
    {
        $this->dataHelper->log('RefundObserverBeforeSave::sendRefundRequest - ' . $message, ConfigData::CUSTOM_LOG_PREFIX, $data);
        throw new LocalizedException(new Phrase('Cardpay - ' . $message));
    }
}
