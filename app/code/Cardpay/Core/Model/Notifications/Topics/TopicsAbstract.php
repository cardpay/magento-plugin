<?php

namespace Cardpay\Core\Model\Notifications\Topics;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Helper\Response;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Magento\Sales\Model\Service\InvoiceService;

abstract class TopicsAbstract
{
    public $statusUpdatedFlag;
    protected $scopeConfig;
    protected $dataHelper;
    protected $orderFactory;
    protected $creditmemoFactory;
    protected $messageInterface;
    protected $statusFactory;
    protected $orderSender;
    protected $orderCommentSender;
    protected $transactionFactory;
    protected $invoiceSender;
    protected $invoiceService;

    /**
     * TopicsAbstract constructor.
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  Data  $dataHelper
     * @param  OrderFactory  $orderFactory
     * @param  CreditmemoFactory  $creditmemoFactory
     * @param  MessageInterface  $messageInterface
     * @param  StatusFactory  $statusFactory
     * @param  OrderSender  $orderSender
     * @param  OrderCommentSender  $orderCommentSender
     * @param  TransactionFactory  $transactionFactory
     * @param  InvoiceSender  $invoiceSender
     * @param  InvoiceService  $invoiceService
     */
    public function __construct( //NOSONAR
        ScopeConfigInterface $scopeConfig, //NOSONAR
        Data $dataHelper, //NOSONAR
        OrderFactory $orderFactory, //NOSONAR
        CreditmemoFactory $creditmemoFactory, //NOSONAR
        MessageInterface $messageInterface, //NOSONAR
        StatusFactory $statusFactory, //NOSONAR
        OrderSender $orderSender, //NOSONAR
        OrderCommentSender $orderCommentSender, //NOSONAR
        TransactionFactory $transactionFactory, //NOSONAR
        InvoiceSender $invoiceSender, //NOSONAR
        InvoiceService $invoiceService //NOSONAR
    ) {
        $this->dataHelper = $dataHelper;
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->messageInterface = $messageInterface;
        $this->statusFactory = $statusFactory;
        $this->orderSender = $orderSender;
        $this->orderCommentSender = $orderCommentSender;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
    }

    /**
     * @param $incrementId
     * @return Order
     */
    public function getOrderByIncrementId($incrementId)
    {
        return $this->orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * @param  string  $paymentResponse
     * @return Phrase|string
     */
    public function getMessage($paymentResponse)
    {
        $this->dataHelper->log(
            "getMessage",
            ConfigData::BASIC_LOG_PREFIX,
            $paymentResponse
        );
        return print_r($paymentResponse, true);
    }

    /**
     * @param $payment
     * @return mixed
     */
    public function getConfigStatus($payment, $paymentMethod)
    {
        switch ($payment['status']) {
            case 'AUTHORIZED':
                $status = 'pending_payment';
                break;
            case 'VOIDED':
            case 'DECLINED':
                $status = 'canceled';
                break;
            case 'REFUNDED':
                $status = 'closed';
                break;
            case 'CHARGED_BACK':
                $status = 'holded';
                break;
            case 'COMPLETED':
            default:
                $status = 'processing';
                break;
        }

        return [$status, $payment['status']];
    }

    /**
     * @param  Order  $order
     * @param $newStatusOrder
     * @param $message
     * @return mixed
     */
    public function setStatusAndComment($order, $newStatusOrder, $message)
    {
        $order->setCustomOrderAttribute($newStatusOrder[1]);

        if ($newStatusOrder[0] === 'canceled' && $order->getState() !== 'canceled') {
            $order->cancel();
        } else {
            $order->setState($this->_getAssignedState($newStatusOrder[0]));
        }
        $order->addStatusToHistory($newStatusOrder[0], $message, true);

        $order->save();

        return $order;
    }

    /**
     * @param $status
     * @return mixed
     */
    public function _getAssignedState($status)
    {
        $collection = $this->statusFactory->joinStates()->addFieldToFilter('main_table.status', $status);
        $collectionItems = $collection->getItems();
        return array_pop($collectionItems)->getState();
    }

    /**
     * @param  array  $response
     * @return bool
     */
    public function isValidResponse($response)
    {
        if (!isset($response['status'])) {
            return false;
        }

        if ((int)$response['status'] === 200 || (int)$response['status'] === 201) {
            return true;
        }

        return isset($response['response']);
    }

    /**
     * @param $order
     * @param $data
     * @return bool
     * @throws AlreadyExistsException
     */
    public function validateRefunded($order, $data)
    {
        $merchantOrder = $data['merchantOrder'];
        if (isset($merchantOrder['amount_refunded']) && $merchantOrder['amount_refunded'] > 0) {
            $creditMemo = $this->generateCreditMemo($data, $order);
            if ($creditMemo === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $payment
     * @param $order
     * @return Order\Creditmemo|null
     * @throws AlreadyExistsException
     */
    public function generateCreditMemo($payment, $order)
    {
        $creditMemo = null;
        $order->setExternalRequest(true);
        $creditMemos = $order->getCreditmemosCollection()->getItems();

        $previousRefund = 0;
        foreach ($creditMemos as $creditMemo) {
            $previousRefund += $creditMemo->getGrandTotal();
        }

        $amountRefunded = $payment['refund_data']['amount'];
        $amount = $amountRefunded - $previousRefund;
        if ($amount > 0) {
            $order->setExternalType('partial');

            $creditMemo = $this->creditmemoFactory->createByOrder($order, [-1]);
            if (count($creditMemos) > 0) {
                $creditMemo->setAdjustmentPositive($amount);
            } else {
                $creditMemo->setAdjustmentNegative($amount);
            }

            $creditMemo->setGrandTotal($amount);
            $creditMemo->setBaseGrandTotal($amount);
            $creditMemo->setTransactionId($payment['refund_data']['id']);
            $creditMemo->setState(2);
            $creditMemo->getResource()->save($creditMemo);

            $order->setTotalRefunded($amountRefunded);
            $order->getResource()->save($order);
        }

        if ($amountRefunded == $order->getGrandTotal()) {
            $order->setForcedCanCreditmemo(false);
            $order->setActionFlag('ship', false);
        }

        $order->save();

        return $creditMemo;
    }

    /**
     * @param $order
     * @param $data
     * @return bool
     */
    public function updateOrder($order, $data)
    {
        if ($this->checkStatusAlreadyUpdated($order, $data)) {
            $this->dataHelper->log(
                'Already updated',
                ConfigData::BASIC_LOG_PREFIX
            );
            return $order;
        }
        $this->updatePaymentInfo($order, $data);
        return $order->save();
    }

    /**
     * @param $order
     * @param $data
     */
    public function updatePaymentInfo($order, $data)
    {
        $paymentOrder = $order->getPayment();
        $paymentAdditionalInfo = $paymentOrder->getAdditionalInformation();
        $dataPayment = $data['payments'][$data['statusFinal']['key']];

        $additionalFields = [
            'status',
            'status_detail',
            'id',
            'transaction_amount',
            'cardholder_name',
            'installments',
            'statement_descriptor',
            'trunc_card',
            'payer_identification_type',
            'payer_identification_number'
        ];

        foreach ($additionalFields as $field) {
            if (isset($dataPayment[$field]) && empty($paymentAdditionalInfo['second_card_token'])) {
                $paymentOrder->setAdditionalInformation($field, $dataPayment[$field]);
            }
        }

        if (isset($dataPayment['id'])) {
            $paymentOrder->setAdditionalInformation('payment_id_detail', $dataPayment['id']);
        }

        if (isset($dataPayment['payer']['identification']['type']) &&
            isset($dataPayment['payer']['identification']['number'])) {
            $paymentOrder->setAdditionalInformation(
                $dataPayment['payer']['identification']['type'],
                $dataPayment['payer']['identification']['number']
            );
        }

        if (isset($dataPayment['payment_method_id'])) {
            $paymentOrder->setAdditionalInformation('payment_method', $dataPayment['payment_method_id']);
        }

        if (isset($dataPayment['order']['id'])) {
            $paymentOrder->setAdditionalInformation('merchant_order_id', $dataPayment['order']['id']);
        }

        $paymentStatus = $paymentOrder->save();

        $this->dataHelper->log(
            'Update Payment',
            ConfigData::BASIC_LOG_PREFIX,
            $paymentStatus->getData()
        );
    }

    /**
     * @param $order
     * @param $data
     */
    public function checkStatusAlreadyUpdated($paymentResponse, $order)
    {
        $orderUpdated = false;
        $statusToUpdate = $this->getConfigStatus($paymentResponse, $order['payment_method']);
        $commentsObject = $order->getStatusHistoryCollection(true);
        foreach ($commentsObject as $commentObj) {
            if ((string)$commentObj->getStatus() === (string)$statusToUpdate[0]) {
                $orderUpdated = true;
            }
        }

        return $orderUpdated;
    }

    /**
     * @param $order
     * @param $data
     * @return array
     */
    public function changeStatusOrder($order, $data)
    {
        $payment = $data['payments'][$data['statusFinal']['key']];
        $message = $this->getMessage($payment);

        if ($this->statusUpdatedFlag) {
            return ['text' => $message, 'code' => Response::HTTP_OK];
        }

        $this->updateStatus($order, $payment);

        try {
            $infoPayments = $order->getPayment()->getAdditionalInformation();
            if ($this->_getMulticardLastValue($payment['status']) === 'approved') {
                $this->_handleTwoCards($payment, $infoPayments);
                $this->dataHelper->setOrderSubtotals($payment, $order);
                $this->createInvoice($order, null);
            } elseif ($payment['status'] === 'refunded' || $payment['status'] === 'cancelled') {
                $order->setExternalRequest(true);
                $order->cancel();
            }

            return ['text' => $message, 'code' => Response::HTTP_OK];

        } catch (Exception $e) {
            $this->dataHelper->log(
                "Error in setOrderStatus: ".
                $e,
                ConfigData::BASIC_LOG_PREFIX
            );
            return ['text' => $e, 'code' => Response::HTTP_BAD_REQUEST];
        }
    }

    /**
     * @param $order
     * @param $payment
     * @param $message
     * @return mixed
     */
    public function updateStatus($order, $payment)
    {
        $this->dataHelper->log(
            $order['payment_method'],
            ConfigData::BASIC_LOG_PREFIX
        );
        $statusOrder = $this->getConfigStatus($payment, $order['payment_method']);
        $this->dataHelper->log(
            "PAYMENT UPDATE STATUS",
            ConfigData::BASIC_LOG_PREFIX,
            $statusOrder
        );
        $order->setCustomOrderAttribute($statusOrder[1]);

        if ($statusOrder[0] === 'canceled') {
            $order->cancel();
        } else {
            $order->setState($this->_getAssignedState($statusOrder[0]));
        }

        return $order->save();
    }

    /**
     * @param $value
     * @return array|string|string[]|null
     */
    public function _getMulticardLastValue($value)
    {
        $statuses = explode('|', $value);
        return str_replace(' ', '', array_pop($statuses));
    }

    /**
     * @param $payment
     * @param $infoPayments
     */
    public function _handleTwoCards(&$payment, $infoPayments)
    {
        if (isset($infoPayments['second_card_token']) && !empty($infoPayments['second_card_token'])) {
            $payment['total_paid_amount'] = $infoPayments['total_paid_amount'];
            $payment['transaction_amount'] = $infoPayments['transaction_amount'];
            $payment['status'] = $infoPayments['status'];
        }
    }

    /**
     * @param $order
     * @param $paymentData
     * @return bool
     * @throws LocalizedException
     */
    public function createInvoice($order, $paymentData)
    {
        if ($order->hasInvoices()) {
            return false;
        }

        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->pay();
        $invoice->save();

        $transaction = $this->transactionFactory->create();
        $transaction->addObject($invoice);
        $transaction->addObject($invoice->getOrder());
        $transaction->save();

        $this->invoiceSender->send($invoice);

        return true;
    }
}
