<?php

namespace Cardpay\Core\Observer;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
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
     * @var \Magento\Framework\Message\ManagerInterface
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

    public function execute(Observer $observer)
    {
        $creditMemo = $observer->getData('creditmemo');
        $order = $creditMemo->getOrder();
        $this->creditMemoRefundBeforeSave($order, $creditMemo);
    }

    /**
     * @param $order      \Magento\Sales\Model\Order
     * @param $creditMemo \Magento\Sales\Model\Order\Creditmemo
     */
    protected function creditMemoRefundBeforeSave($order, $creditMemo)
    {
        // Do not repeat the return of payment, if it is done through the Cardpay
        if ($order->getExternalRequest()) {
            return;
        }

        // get payment order object
        $paymentOrder = $order->getPayment();
        if (!$this->isPaymentMethodValid($order)) {
            return;
        }

        $payment = $order->getPayment();
        if ($payment != null) {
            if (!empty($payment->getAdditionalInformation())) {
                $additionalInformation = $payment->getAdditionalInformation();
                if (isset($additionalInformation['raw_details_info'])
                    && isset($additionalInformation['raw_details_info']['filing'])
                    && !empty($additionalInformation['raw_details_info']['filing']['id'])
                ) {
                    $this->throwRefundException(__("Refund is not available for installment payment"));
                }
            }

            if (!empty($payment->getMethod() && ('cardpay_customticket' == $payment->getMethod()))) {
                $this->throwRefundException(__("Refund is not available for Boleto"));
            }
        }

        // check is refund enabled
        $refundAvailable = $this->scopeConfig->getValue(ConfigData::PATH_ORDER_REFUND_AVAILABLE, ScopeInterface::SCOPE_STORE);
        if ($refundAvailable == 0) {
            $this->dataHelper->log("RefundObserverBeforeSave::creditMemoRefundBeforeSave - Refund is disabled", ConfigData::CUSTOM_LOG_PREFIX);
            $this->throwRefundException(__("Refund is disabled"));
            return;
        }

        // get amount refund
        $amountToRefund = $creditMemo->getGrandTotal();
        if ($amountToRefund <= 0) {
            $this->throwRefundException(__("The refunded amount must be greater than 0."));
            return;
        }

        // get Payment Id
        $paymentID = $this->getPaymentId($paymentOrder);
        $this->dataHelper->log("RefundObserverBeforeSave::creditMemoRefundBeforeSave paymentId", ConfigData::CUSTOM_LOG_PREFIX, $paymentID);
        if (empty($paymentID)) {
            $this->throwRefundException(__("Refund can not be executed because the payment id was not found."));
            return;
        }

        $this->performRefund($paymentID, $order, $amountToRefund);
    }

    private function isPaymentMethodValid($order)
    {
        // get payment order object
        $paymentOrder = $order->getPayment();
        $paymentMethod = $paymentOrder->getMethodInstance()->getCode();

        return $paymentMethod == 'cardpay_custom'
            || $paymentMethod == 'cardpay_customticket'
            || $paymentMethod == 'cardpay_custom_bank_transfer'
            || $paymentMethod == 'cardpay_basic';
    }

    /**
     * @throws LocalizedException
     */
    private function performRefund($paymentID, $order, $amountToRefund)
    {
        // get API Instance
        $api = $this->dataHelper->getApiInstance();

        $refundRequestParams = $this->dataHelper->getRefundRequestParams($paymentID, $order, $amountToRefund);
        $this->dataHelper->log("RefundObserverBeforeSave::creditMemoRefundBeforeSave data", ConfigData::CUSTOM_LOG_PREFIX, $refundRequestParams);

        $refundResponse = $api->performRefund($refundRequestParams);
        $this->dataHelper->log("RefundObserverBeforeSave::creditMemoRefundBeforeSave responseRefund", ConfigData::CUSTOM_LOG_PREFIX, $refundResponse);

        if (!is_null($refundResponse) || $refundResponse['status'] == 200 || $refundResponse['status'] == 201) {
            $successMessageRefund = "Unlimint - " . __("Refund of %1 was processed successfully.", $amountToRefund);
            $this->messageManager->addSuccessMessage($successMessageRefund);
            $this->dataHelper->log("RefundObserverBeforeSave::creditMemoRefundBeforeSave - " . $successMessageRefund, ConfigData::CUSTOM_LOG_PREFIX, $refundResponse);
        } else {
            $this->throwRefundException(__("Could not process the refund, The Unlimint API returned an unexpected error. Check the log files."), $refundResponse);
        }
    }

    /**
     * @throws LocalizedException
     */
    protected function throwRefundException($message, $data = array())
    {
        $this->dataHelper->log("RefundObserverBeforeSave::sendRefundRequest - " . $message, ConfigData::CUSTOM_LOG_PREFIX, $data);
        throw new LocalizedException(new Phrase('Unlimint - ' . $message));
    }

    private function getPaymentId($paymentOrder)
    {
        $id = $paymentOrder->getTransactionId();

        return substr($id, 0, strpos($id, '-'));
    }
}