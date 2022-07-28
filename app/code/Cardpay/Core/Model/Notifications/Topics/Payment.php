<?php

namespace Cardpay\Core\Model\Notifications\Topics;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\Core;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;

class Payment extends TopicsAbstract
{
    const LOG_NAME = 'notification_payment';

    /**
     * @var cpHelper
     */
    protected $cpHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Core
     */
    protected $coreModel;

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @param cpHelper $cpHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Core $coreModel
     * @param OrderFactory $orderFactory
     * @param CreditmemoFactory $creditmemoFactory
     * @param MessageInterface $messageInterface
     * @param StatusFactory $statusFactory
     * @param OrderSender $orderSender
     * @param OrderCommentSender $orderCommentSender
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param BuilderInterface $builderInterface
     */
    public function __construct(
        cpHelper             $cpHelper,
        ScopeConfigInterface $scopeConfig,
        Core                 $coreModel,
        OrderFactory         $orderFactory,
        CreditmemoFactory    $creditmemoFactory,
        MessageInterface     $messageInterface,
        StatusFactory        $statusFactory,
        OrderSender          $orderSender,
        OrderCommentSender   $orderCommentSender,
        TransactionFactory   $transactionFactory,
        InvoiceSender        $invoiceSender,
        InvoiceService       $invoiceService,
        BuilderInterface     $transactionBuilder

    )
    {
        $this->cpHelper = $cpHelper;
        $this->scopeConfig = $scopeConfig;
        $this->coreModel = $coreModel;
        $this->transactionBuilder = $transactionBuilder;
        $this->transaction = new Transaction($cpHelper, $transactionBuilder);

        parent::__construct(
            $scopeConfig,
            $cpHelper,
            $orderFactory,
            $creditmemoFactory,
            $messageInterface,
            $statusFactory,
            $orderSender,
            $orderCommentSender,
            $transactionFactory,
            $invoiceSender,
            $invoiceService
        );
    }

    /**
     * @param $requestParams
     * @throws Exception
     */
    public function refund($requestParams)
    {
        $refundData = $requestParams['refund_data'];
        $amount = $refundData['amount'];
        $orderId = $requestParams['merchant_order']['id'];

        $order = $this->getOrderByIncrementId($orderId);

        // does not repeat the return of payment, if it is done through the Unlimint
        if ($order->getExternalRequest()) {
            return;
        }

        // get payment order object
        $paymentOrder = $order->getPayment();
        $paymentMethod = $paymentOrder->getMethodInstance()->getCode();
        if (!in_array($paymentMethod, ['cardpay_custom', ConfigData::BOLETO_PAYMENT_METHOD, ConfigData::BANKCARD_PAYMENT_METHOD, 'cardpay_basic'])) {
            return;
        }

        // check refund available
        $refundAvailable = $this->scopeConfig->getValue(ConfigData::PATH_ORDER_REFUND_AVAILABLE, ScopeInterface::SCOPE_STORE);
        if (0 === (int)$refundAvailable) {
            $this->cpHelper->log(__FUNCTION__ . ' - Refund is disabled', ConfigData::CUSTOM_LOG_PREFIX);
            throw new Exception(__('Refund is disabled'));
        }

        // get amount refund
        $amountRefund = $amount;
        if ($amountRefund <= 0) {
            throw new Exception(__('The refunded amount must be greater than 0.'));
        }

        $creditMemo = $this->generateCreditMemo($requestParams, $order);

        if (!is_null($creditMemo)) {
            $successMessageRefund = 'Unlimint - ' . __('Refund of %1 was processed successfully.', $amountRefund);
            $this->cpHelper->log(__FUNCTION__ . ' - ' . $successMessageRefund, ConfigData::CUSTOM_LOG_PREFIX, $creditMemo);
        } else {
            $this->cpHelper->log(__FUNCTION__ . ' - ' . __('Could not process the refund'), ConfigData::CUSTOM_LOG_PREFIX, $creditMemo);
            throw new Exception(__('Could not process the refund, The Unlimint API returned an unexpected error. Check the log files.'));
        }
    }

    /**
     * @param $requestParams
     * @return array
     * @throws Exception
     */
    public function updateStatusOrderByPayment($requestParams)
    {
        $orderId = $requestParams['merchant_order']['id'];
        $paymentData = $requestParams['payment_data'];

        /**
         * @var Order
         */
        $order = $this->getOrderByIncrementId($orderId);

        if (!$order->getId()) {
            $message = 'Unlimint - The order was not found in Magento. You will not be able to follow the process without this information.';
            $this->cpHelper->log('updateStatusOrderByPayment', self::LOG_NAME, $message);

            return ['httpStatus' => Response::HTTP_NOT_FOUND, 'message' => $message, 'data' => $orderId];
        }

        $currentOrderStatus = $order->getState();
        $this->cpHelper->log('currentOrderStatus', self::LOG_NAME, $currentOrderStatus);

        $message = '';
        if (isset($paymentData['id'])) {
            $message = $this->getMessage('Unlimint payment id: ' . $paymentData['id']);
        }

        if (isset($paymentData['status'])) {
            $newOrderStatus = $this->getConfigStatus($paymentData);
            $order = $this->setStatusAndComment($order, $newOrderStatus, $message);
            $this->sendEmailCreateOrUpdate($order, $message);

            $paymentObj = $order->getPayment();
            $paymentObj->setAdditionalInformation('paymentResponse', $requestParams);
            $paymentObj->save();

            $responseInvoice = false;
            if ($paymentData['status'] === 'COMPLETED') {
                $responseInvoice = $this->createInvoice($order, $paymentData);
            }

            $messageHttp = 'Unlimint - Status successfully updated.';
            return [
                'httpStatus' => Response::HTTP_OK,
                'message' => $messageHttp,
                'data' => [
                    'message' => $message,
                    'order_id' => $order->getIncrementId(),
                    'new_order_status' => $newOrderStatus,
                    'old_order_status' => $currentOrderStatus,
                    'created_invoice' => $responseInvoice
                ]
            ];
        }

        $order = $this->setStatusAndComment($order, $currentOrderStatus, $message);

        return [
            'httpStatus' => Response::HTTP_OK,
            'message' => 'Unlimint - Notification Received.',
            'data' => [
                'message' => $message,
                'order_id' => $order->getIncrementId(),
            ]
        ];
    }

    /**
     * @param $order
     * @param $message
     */
    public function sendEmailCreateOrUpdate($order, $message)
    {
        $emailOrderCreate = $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_CREATE, ScopeInterface::SCOPE_STORE);
        $emailAlreadySent = false;
        if ($emailOrderCreate && !$order->getEmailSent()) {
            $this->orderSender->send($order, true);
            $emailAlreadySent = true;
        }

        if ($emailAlreadySent === false) {
            $statusEmail = $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_UPDATE, ScopeInterface::SCOPE_STORE);
            $statusEmailList = explode(',', $statusEmail);
            if (in_array($order->getStatus(), $statusEmailList)) {
                $this->orderCommentSender->send($order, true, str_replace('<br/>', '', $message));
            }
        }
    }

    /**
     * @param Order $order
     * @param array $paymentData
     * @return bool
     * @throws LocalizedException
     */
    public function createInvoice($order, $paymentData)
    {
        if (!$order->hasInvoices()) {
            $this->cpHelper->log('Create invoice', self::LOG_NAME, $paymentData);

            /**
             * @var Invoice
             */
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();

            $invoice->setTransactionId($paymentData['id']);
            $invoice->save();

            $this->transaction->createTransaction($paymentData, $order);
            $order->save();

            try {
                $this->invoiceSender->send($invoice, true);
            } catch (Exception $e) {
                $this->cpHelper->log("We can't send the invoice email right now.", self::LOG_NAME);
            }

            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @param null $type
     * @return array
     */
    public function getPaymentData($id, $type = null)
    {
        try {
            $response = $this->coreModel->getPayment($id);
            $this->cpHelper->log('Response API CP Get Payment', self::LOG_NAME, $response);

            if (!$this->isValidResponse($response)) {
                throw new Exception(__('CP API Invalid Response'), 400);
            }

            $payments = [];
            $payments[] = $response['response'];

            return ['merchantOrder' => null, 'payments' => $payments, 'shipmentData' => null];

        } catch (Exception $e) {
            $this->cpHelper->log(__('ERROR - Notifications Payment getPaymentData'), self::LOG_NAME, $e->getMessage());
            return null;
        }
    }

    /**
     * @param cpHelper $cpHelper
     */
    public function setCpHelper(cpHelper $cpHelper): void
    {
        $this->cpHelper = $cpHelper;
    }

    /**
     * @param Transaction $transaction
     */
    public function setTransaction(Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @param InvoiceSender $invoiceSender
     */
    public function setInvoiceSender(InvoiceSender $invoiceSender): void
    {
        $this->invoiceSender = $invoiceSender;
    }
}
