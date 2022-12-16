<?php

namespace Cardpay\Core\Observer;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

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
     * @var RequestInterface
     */
    protected $request;

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
        $this->request = $context->getRequest();
    }

    /**
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $cmRequestParams = $this->request->getParam('creditmemo');
        if (!empty($cmRequestParams)
            && isset($cmRequestParams['do_offline'])
            && 1 === (int)$cmRequestParams['do_offline']
        ) {
            // return offline
            return;
        }

        $creditMemo = $observer->getData('creditmemo');
        $order = $creditMemo->getOrder();
        $this->creditMemoRefundBeforeSave($order, $creditMemo);
    }

    /**
     * @param $order      \Magento\Sales\Model\Order
     * @param $creditMemo \Magento\Sales\Model\Order\Creditmemo
     * @throws LocalizedException
     */
    protected function creditMemoRefundBeforeSave($order, $creditMemo)
    {
        // Do not repeat the return of payment, if it is done through Unlimint
        if ($order->getExternalRequest()) {
            return;
        }

        // get payment order object
        $paymentOrder = $order->getPayment();
        if (!$this->isPaymentMethodValid($order)) {
            return;
        }

        $payment = $order->getPayment();
        if ($payment !== null) {
            $additionalInformation = $payment->getAdditionalInformation();
            if (!empty($payment->getAdditionalInformation()) && (isset($additionalInformation['raw_details_info']['filing']) && !empty($additionalInformation['raw_details_info']['filing']['id']))) {
                {
                    $this->throwRefundException(__("Refund is not available for installment payment"));
                }
            }
        }

        // check is refund enabled
        $refundAvailable = (int)$this->scopeConfig->getValue(ConfigData::PATH_ORDER_REFUND_AVAILABLE, ScopeInterface::SCOPE_STORE);
        if ($refundAvailable === 0) {
            $this->dataHelper->log('Core, RefundObserverBeforeSave::creditMemoRefundBeforeSave - Refund is disabled', ConfigData::CUSTOM_LOG_PREFIX);
            $this->throwRefundException(__('Refund is disabled'));
        }

        // get amount refund
        $amountToRefund = $creditMemo->getGrandTotal();
        if ($amountToRefund <= 0) {
            $this->throwRefundException(__('The refunded amount must be greater than 0.'));
        }

        // get Payment Id
        $paymentID = $this->getPaymentId($paymentOrder);
        $this->dataHelper->log('Core, RefundObserverBeforeSave::creditMemoRefundBeforeSave paymentId', ConfigData::CUSTOM_LOG_PREFIX, $paymentID);
        if (empty($paymentID)) {
            $this->throwRefundException(__('Refund can not be executed because the payment id was not found.'));
        }

        $this->performRefund($paymentID, $order, $amountToRefund);
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function isPaymentMethodValid($order)
    {
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethodInstance()->getCode();

        return $paymentMethod === ConfigData::BANKCARD_PAYMENT_METHOD
            || $paymentMethod === ConfigData::BOLETO_PAYMENT_METHOD
            || $paymentMethod === ConfigData::PIX_PAYMENT_METHOD
            || $paymentMethod === 'cardpay_basic';
    }

    /**
     * @throws LocalizedException
     */
    private function performRefund($paymentID, $order, $amountToRefund)
    {
        // get API Instance
        $api = $this->dataHelper->getApiInstance($order);

        $refundRequestParams = $this->dataHelper->getRefundRequestParams($paymentID, $order, $amountToRefund);
        $this->dataHelper->log('Core, RefundObserverBeforeSave::creditMemoRefundBeforeSave data', ConfigData::CUSTOM_LOG_PREFIX, $refundRequestParams);

        $refundResponse = $api->refund($refundRequestParams);
        $this->dataHelper->log('Core, RefundObserverBeforeSave::creditMemoRefundBeforeSave responseRefund', ConfigData::CUSTOM_LOG_PREFIX, $refundResponse);

        if (!is_null($refundResponse) || (int)$refundResponse['status'] === 200 || (int)$refundResponse['status'] === 201) {

            if ($refundResponse['response']['payment_data']['remaining_amount'] === 0) {
                $order->setCustomOrderAttribute('REFUNDED');
            }

            $successMessageRefund = 'Unlimint - ' . __('Refund of %1 was processed successfully.', $amountToRefund);
            $this->messageManager->addSuccessMessage($successMessageRefund);
            $this->dataHelper->log('Core, RefundObserverBeforeSave::creditMemoRefundBeforeSave - ' . $successMessageRefund, ConfigData::CUSTOM_LOG_PREFIX, $refundResponse);
        } else {
            $this->throwRefundException(__('Could not process the refund, The Unlimint API returned an unexpected error. Check the log files.'), $refundResponse);
        }
    }

    /**
     * @throws LocalizedException
     */
    protected function throwRefundException($message, $data = [])
    {
        $this->dataHelper->log('Core, RefundObserverBeforeSave::sendRefundRequest - ' . $message, ConfigData::CUSTOM_LOG_PREFIX, $data);
        throw new LocalizedException(new Phrase($message));
    }

    private function getPaymentId($paymentOrder)
    {
        $id = $paymentOrder->getTransactionId();

        return substr($id, 0, strpos($id, '-'));
    }
}
