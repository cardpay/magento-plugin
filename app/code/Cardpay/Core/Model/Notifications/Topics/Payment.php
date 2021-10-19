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
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;

class Payment extends TopicsAbstract
{
    const LOG_NAME = 'notification_payment';

    protected $_cpHelper;
    protected $_scopeConfig;
    protected $_coreModel;
    protected $_transactionBuilder;

    /**
     * Payment constructor.
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
        $this->_cpHelper = $cpHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_coreModel = $coreModel;
        $this->_transactionBuilder = $transactionBuilder;

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
     * @param $payment
     * @throws Exception
     */
    public function refund($payment)
    {
        $refundData = $payment['refund_data'];
        $amount = $refundData['amount'];
        $orderId = $payment['merchant_order']['id'];

        $order = $this->getOrderByIncrementId($orderId);

        // Does not repeat the return of payment, if it is done through the Unlimint
        if ($order->getExternalRequest()) {
            return;
        }

        //get payment order object
        $paymentOrder = $order->getPayment();
        $paymentMethod = $paymentOrder->getMethodInstance()->getCode();
        if (!($paymentMethod == 'cardpay_custom'
            || $paymentMethod == 'cardpay_customticket'
            || $paymentMethod == 'cardpay_custom_bank_transfer'
            || $paymentMethod == 'cardpay_basic')
        ) {
            return;
        }

        //Check refund available
        $refundAvailable = $this->_scopeConfig->getValue(ConfigData::PATH_ORDER_REFUND_AVAILABLE, ScopeInterface::SCOPE_STORE);
        if ($refundAvailable == 0) {
            $this->_cpHelper->log(__FUNCTION__ . ' - Refund is disabled', ConfigData::CUSTOM_LOG_PREFIX);
            throw new Exception(__("Refund is disabled"));
        }

        //Get amount refund
        $amountRefund = $amount;
        if ($amountRefund <= 0) {
            throw new Exception(__('The refunded amount must be greater than 0.'));
        }

        $creditMemo = $this->generateCreditMemo($payment, $order);

        if (!is_null($creditMemo)) {
            $successMessageRefund = "Unlimint - " . __("Refund of %1 was processed successfully.", $amountRefund);
            $this->_cpHelper->log(__FUNCTION__ . " - " . $successMessageRefund, ConfigData::CUSTOM_LOG_PREFIX, $creditMemo);
        } else {
            $this->_cpHelper->log(__FUNCTION__ . " - " . __('Could not process the refund'), ConfigData::CUSTOM_LOG_PREFIX, $creditMemo);
            throw new Exception(__("Could not process the refund, The Unlimint API returned an unexpected error. Check the log files."));
        }
    }

    /**
     * @param $payment
     * @return array
     * @throws Exception
     */
    public function updateStatusOrderByPayment($payment)
    {
        $orderId = $payment['merchant_order']['id'];
        $type = isset($payment['payment_data']) ? 'payment_data' : 'recurring_data';
        $paymentData = $payment[$type];

        $order = parent::getOrderByIncrementId($orderId);

        if (!$order->getId()) {
            $message = "Unlimint - The order was not found in Magento. You will not be able to follow the process without this information.";
            $this->_cpHelper->log('updateStatusOrderByPayment', self::LOG_NAME, $message);
            return ["httpStatus" => Response::HTTP_NOT_FOUND, "message" => $message, "data" => $orderId];
        }

        $currentOrderStatus = $order->getState();
        $this->_cpHelper->log('currentOrderStatus', self::LOG_NAME, $currentOrderStatus);

        $message = parent::getMessage($paymentData);

        if (isset($paymentData['status'])) {
            $statusAlreadyUpdated = $this->checkStatusAlreadyUpdated($paymentData, $order);

            $newOrderStatus = parent::getConfigStatus($paymentData);

            if ($statusAlreadyUpdated) {
                $orderPayment = $order->getPayment();
                $orderPayment->setAdditionalInformation("paymentResponse", $payment);
                $order->save();

                $messageHttp = "Unlimint - Status has already been updated.";
                return [
                    "httpStatus" => Response::HTTP_OK,
                    "message" => $messageHttp,
                    "data" => [
                        "message" => $message,
                        "order_id" => $order->getIncrementId(),
                        "current_order_status" => $currentOrderStatus,
                        "new_order_status" => $newOrderStatus
                    ]
                ];
            }

            $order = self::setStatusAndComment($order, $newOrderStatus, $message);
            $this->sendEmailCreateOrUpdate($order, $message);

            $paymentObj = $order->getPayment();
            $paymentObj->setAdditionalInformation("paymentResponse", $payment);
            $paymentObj->save();

            $responseInvoice = false;
            if ($paymentData['status'] == 'COMPLETED') {
                $responseInvoice = $this->createInvoice($order, $message, $paymentData);
            }

            $messageHttp = "Unlimint - Status successfully updated.";
            return [
                "httpStatus" => Response::HTTP_OK,
                "message" => $messageHttp,
                "data" => [
                    "message" => $message,
                    "order_id" => $order->getIncrementId(),
                    "new_order_status" => $newOrderStatus,
                    "old_order_status" => $currentOrderStatus,
                    "created_invoice" => $responseInvoice
                ]
            ];

        } else {
            $order = self::setStatusAndComment($order, $currentOrderStatus, $message);

            $messageHttp = 'Unlimint - Notification Received.';
            return [
                'httpStatus' => Response::HTTP_OK,
                'message' => $messageHttp,
                'data' => [
                    'message' => $message,
                    'order_id' => $order->getIncrementId(),
                ]
            ];
        }
    }

    /**
     * @param $paymentResponse
     * @param $order
     * @return bool
     */
    public function checkStatusAlreadyUpdated($paymentResponse, $order)
    {
        $orderUpdated = false;
        $statusToUpdate = parent::getConfigStatus($paymentResponse);
        $commentsObject = $order->getStatusHistoryCollection(true);
        foreach ($commentsObject as $commentObj) {
            if ($commentObj->getStatus() == $statusToUpdate) {
                $orderUpdated = true;
            }
        }

        return $orderUpdated;
    }

    /**
     * @param $order
     * @param $message
     */
    public function sendEmailCreateOrUpdate($order, $message)
    {
        $emailOrderCreate = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_CREATE, ScopeInterface::SCOPE_STORE);
        $emailAlreadySent = false;
        if ($emailOrderCreate && !$order->getEmailSent()) {
            $this->_orderSender->send($order, true);
            $emailAlreadySent = true;
        }

        if ($emailAlreadySent === false) {
            $statusEmail = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_UPDATE, ScopeInterface::SCOPE_STORE);
            $statusEmailList = explode(",", $statusEmail);
            if (in_array($order->getStatus(), $statusEmailList)) {
                $this->_orderCommentSender->send($order, true, str_replace("<br/>", "", $message));
            }
        }
    }

    /**
     * @param $order
     * @param $message
     * @return bool
     * @throws Exception
     */
    public function createInvoice($order, $message, $paymentData)
    {
        if (!$order->hasInvoices()) {
            $this->_cpHelper->log('Create Invoice', self::LOG_NAME, $paymentData);

            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();

            $invoice->addComment(str_replace("<br/>", "", $message), false, true);

            $invoice->setTransactionId($paymentData['id']);
            $invoice->save();

            $this->createTransaction($order, $paymentData);
            $order->save();

            try {
                $this->_invoiceSender->send($invoice, true, $message);
            } catch (Exception $e) {
                $this->_cpHelper->log('We can\'t send the invoice email right now.', self::LOG_NAME, $message);
            }

            return true;
        }

        return false;
    }

    public function createTransaction($order = null, $paymentData)
    {
        try {
            $this->_cpHelper->log('Create Transaction', self::LOG_NAME, $paymentData);

            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setAdditionalInformation(
                [Transaction::RAW_DETAILS => (array)$paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __("The authorized amount is %1.", $formatedPrice);

            //get the object of builder class
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    [Transaction::RAW_DETAILS => (array)$paymentData]
                )
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build(Transaction::TYPE_CAPTURE);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );

            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();

        } catch (Exception $e) {
            $this->_cpHelper->log("Create transaction error", self::LOG_NAME, $e);
            return null;
        }
    }

    /**
     * @param $id
     * @param null $type
     * @return array
     */
    public function getPaymentData($id, $type = null)
    {
        try {
            $response = $this->_coreModel->getPayment($id);
            $this->_cpHelper->log("Response API CP Get Payment", self::LOG_NAME, $response);

            if (!$this->isValidResponse($response)) {
                throw new Exception(__('CP API Invalid Response'), 400);
            }

            $payments = [];
            $payments[] = $response['response'];

            return ['merchantOrder' => null, 'payments' => $payments, 'shipmentData' => null];

        } catch (Exception $e) {
            $this->_cpHelper->log(__("ERROR - Notifications Payment getPaymentData"), self::LOG_NAME, $e->getMessage());
            return null;
        }
    }
}
