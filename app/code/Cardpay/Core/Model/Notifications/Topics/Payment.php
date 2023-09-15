<?php

namespace Cardpay\Core\Model\Notifications\Topics;

use Cardpay\Core\Exceptions\UnlimitBaseException;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\ApiManager;
use Cardpay\Core\Model\Core;
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
     * @var ApiManager
     */
    protected $apiModel;

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @param  cpHelper  $cpHelper
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  ApiManager  $apiModel
     * @param  OrderFactory  $orderFactory
     * @param  CreditmemoFactory  $creditmemoFactory
     * @param  MessageInterface  $messageInterface
     * @param  StatusFactory  $statusFactory
     * @param  OrderSender  $orderSender
     * @param  OrderCommentSender  $orderCommentSender
     * @param  TransactionFactory  $transactionFactory
     * @param  InvoiceSender  $invoiceSender
     * @param  InvoiceService  $invoiceService
     * @param  BuilderInterface  $builderInterface
     */
    public function __construct( //NOSONAR
        cpHelper $cpHelper, //NOSONAR
        ScopeConfigInterface $scopeConfig, //NOSONAR
        ApiManager $apiModel, //NOSONAR
        OrderFactory $orderFactory, //NOSONAR
        CreditmemoFactory $creditmemoFactory, //NOSONAR
        MessageInterface $messageInterface, //NOSONAR
        StatusFactory $statusFactory, //NOSONAR
        OrderSender $orderSender, //NOSONAR
        OrderCommentSender $orderCommentSender, //NOSONAR
        TransactionFactory $transactionFactory, //NOSONAR
        InvoiceSender $invoiceSender, //NOSONAR
        InvoiceService $invoiceService, //NOSONAR
        BuilderInterface $transactionBuilder //NOSONAR

    ) {
        $this->cpHelper = $cpHelper;
        $this->scopeConfig = $scopeConfig;
        $this->apiModel = $apiModel;
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
     * @throws UnlimitBaseException
     */
    public function refund($requestParams)
    {
        $orderId = $requestParams['merchant_order']['id'];
        $order = $this->getOrderByIncrementId($orderId);

        // does not repeat the return of payment, if it is done through the Unlimit
        if ($order->getExternalRequest()) {
            return;
        }

        // get payment order object
        $paymentOrder = $order->getPayment();
        $paymentMethod = $paymentOrder->getMethodInstance()->getCode();
        if (!in_array(
            $paymentMethod,
            [
                'cardpay_custom',
                ConfigData::BOLETO_PAYMENT_METHOD,
                ConfigData::PIX_PAYMENT_METHOD,
                ConfigData::BANKCARD_PAYMENT_METHOD,
                ConfigData::PAYPAL_PAYMENT_METHOD,
                'cardpay_basic'
            ]
        )) {
            return;
        }

        // get amount refund
        $amount = $requestParams['refund_data']['amount'];
        $amountRefund = $amount;
        if ($amountRefund <= 0) {
            throw new UnlimitBaseException(__('The refunded amount must be greater than 0.'));
        }

        $creditMemo = $this->generateCreditMemo($requestParams, $order);

        if (!is_null($creditMemo)) {
            $successMessageRefund = 'Unlimit - '.__('Refund of %1 was processed successfully.', $amountRefund);
            $this->cpHelper->log(__FUNCTION__.' - '.$successMessageRefund, ConfigData::CUSTOM_LOG_PREFIX, $creditMemo);
        } else {
            $this->cpHelper->log(
                __FUNCTION__.' - '.__('Could not process the refund'),
                ConfigData::CUSTOM_LOG_PREFIX,
                $creditMemo
            );
            throw new UnlimitBaseException(
                __('Could not process the refund, The Unlimit API returned an unexpected error. Check the log files.')
            );
        }
    }

    /**
     * @param $requestParams
     * @return array
     * @throws UnlimitBaseException
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
            $message = 'Unlimit - The order was not found in Magento.'.
                'You will not be able to follow the process without this information.';

            $this->cpHelper->log('updateStatusOrderByPayment', self::LOG_NAME, $message);

            return ['httpStatus' => Response::HTTP_NOT_FOUND, 'message' => $message, 'data' => $orderId];
        }

        $currentOrderStatus = $order->getState();
        $this->cpHelper->log('currentOrderStatus', self::LOG_NAME, $currentOrderStatus);

        $message = '';
        if (isset($paymentData['id'])) {
            $message = $this->getMessage('Unlimit payment id: '.$paymentData['id']);
        }

        if (isset($paymentData['status'])) {
            $newOrderStatus = $this->getConfigStatus($paymentData, $requestParams['payment_method']);

            $order = $this->setStatusAndComment($order, $newOrderStatus, $message);
            $this->sendEmailCreateOrUpdate($order, $message);

            $paymentObj = $order->getPayment();
            $paymentObj->setAdditionalInformation('paymentResponse', $requestParams);
            $paymentObj->save();

            $responseInvoice = false;
            if ($paymentData['status'] === 'COMPLETED') {
                $responseInvoice = $this->createInvoice($order, $paymentData);
            }

            $messageHttp = 'Unlimit - Status successfully updated.';
            return [
                'httpStatus' => Response::HTTP_OK,
                'message' => $messageHttp,
                'data' => [
                    'message' => $message,
                    'order_id' => $order->getIncrementId(),
                    'new_order_status' => $newOrderStatus[0],
                    'old_order_status' => $currentOrderStatus,
                    'created_invoice' => $responseInvoice
                ]
            ];
        }

        $order = $this->setStatusAndComment($order, $currentOrderStatus, $message);


        return [
            'httpStatus' => Response::HTTP_OK,
            'message' => 'Unlimit - Notification Received.',
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
        $emailOrderCreate = $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_CREATE,
            ScopeInterface::SCOPE_STORE);
        $emailAlreadySent = false;
        if ($emailOrderCreate && !$order->getEmailSent()) {
            $this->orderSender->send($order, true);
            $emailAlreadySent = true;
        }

        if ($emailAlreadySent === false) {
            $statusEmail = $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_UPDATE,
                ScopeInterface::SCOPE_STORE);
            $statusEmailList = explode(',', $statusEmail);
            if (in_array($order->getStatus(), $statusEmailList)) {
                $this->orderCommentSender->send($order, true, str_replace('<br/>', '', $message));
            }
        }
    }

    /**
     * @param  Order  $order
     * @param  array  $paymentData
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
            } catch (UnlimitBaseException $e) {
                $this->cpHelper->log("We can't send the invoice email right now.", self::LOG_NAME);
            }

            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @param  null  $type
     * @return array
     */
    public function getPaymentData($id)
    {
        try {
            $response = $this->apiModel->getPayment($id);
            $this->cpHelper->log('Response API CP Get Payment', self::LOG_NAME, $response);

            if (!$this->isValidResponse($response)) {
                throw new UnlimitBaseException(__('CP API Invalid Response'), null, 400);
            }

            $payments = [];
            $payments[] = $response['response'];

            return ['merchantOrder' => null, 'payments' => $payments, 'shipmentData' => null];

        } catch (UnlimitBaseException $e) {
            $this->cpHelper->log(__('ERROR - Notifications Payment getPaymentData'), self::LOG_NAME, $e->getMessage());
            return null;
        }
    }

    /**
     * @param  cpHelper  $cpHelper
     */
    public function setCpHelper(cpHelper $cpHelper): void
    {
        $this->cpHelper = $cpHelper;
    }

    /**
     * @param  Transaction  $transaction
     */
    public function setTransaction(Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @param  InvoiceSender  $invoiceSender
     */
    public function setInvoiceSender(InvoiceSender $invoiceSender): void
    {
        $this->invoiceSender = $invoiceSender;
    }
}
