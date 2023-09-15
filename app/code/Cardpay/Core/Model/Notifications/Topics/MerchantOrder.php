<?php

namespace Cardpay\Core\Model\Notifications\Topics;

use Cardpay\Core\Exceptions\UnlimitBaseException;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\ApiManager;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Magento\Sales\Model\Service\InvoiceService;

class MerchantOrder extends TopicsAbstract
{
    const LOG_NAME = 'notification_merchant_order';

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
     * MerchantOrder constructor.
     *
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
        InvoiceService $invoiceService //NOSONAR

    ) {
        $this->cpHelper = $cpHelper;
        $this->scopeConfig = $scopeConfig;
        $this->apiModel = $apiModel;

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
     * @param $id
     * @param  null  $type
     * @return array
     */
    public function getPaymentData($id, $type = null)
    {
        try {
            if ((string)$type === Notifications::TYPE_NOTIFICATION_WEBHOOK) {
                $response = $this->apiModel->getPayment($id);
                if (empty($response) || ((int)$response['status'] !== 200 && (int)$response['status'] !== 201)) {
                    throw new UnlimitBaseException(__('CP API PAYMENT Invalid Response'), null, 400);
                }
                $id = $response['order']['id'];
            }

            $response = $this->apiModel->getMerchantOrder($id);
            $this->cpHelper->log('Response API CP merchant_order', self::LOG_NAME, $response);
            if (!$this->isValidResponse($response)) {
                throw new UnlimitBaseException(__('CP API Invalid Response'), null, 400);
            }

            $merchantOrder = $response['response'];
            if (count((int)$merchantOrder['payments']) === 0) {
                throw new UnlimitBaseException(__('CP API Payments Not Found'), null, 400);
            }

            if ($merchantOrder['status'] !== 'closed') {
                throw new UnlimitBaseException(__('Payments Not Finalized'), null, 400);
            }

            $payments = [];
            foreach ($merchantOrder['payments'] as $payment) {
                $response = $this->apiModel->getPayment($payment['id']);
                if (empty($response) || !isset($response['response'])) {
                    throw new UnlimitBaseException(__('CP API Payments Not Found in API'), null, 400);
                }
                $payments[] = $response['response'];
            }

            $shipmentData = (isset($merchantOrder['shipments'][0])) ? $merchantOrder['shipments'][0] : [];
            return ['merchantOrder' => $merchantOrder, 'payments' => $payments, 'shipmentData' => $shipmentData];
        } catch (UnlimitBaseException $e) {
            $this->cpHelper->log(__('ERROR - Notifications MerchantOrder getPaymentData'),
                self::LOG_NAME, $e->getMessage());
        }

        return [];
    }

    /**
     * @param $payments
     * @param $merchantOrder
     * @return array
     */
    public function getStatusFinal($merchantOrder)
    {
        if (isset($merchantOrder['payments']) && count($merchantOrder['payments']) === 1) {
            return ['key' => "0", 'status' => $merchantOrder['payments'][0]['status'], 'final' => false];
        }

        $totalApproved = 0;
        $totalPending = 0;
        $merchantOrderPayments = $merchantOrder['payments'];
        $totalOrder = $merchantOrder['total_amount'];
        foreach ($merchantOrderPayments as $payment) {
            $status = $payment['status'];

            if ($status === 'approved') {
                $totalApproved += $payment['transaction_amount'];
            } elseif ($status === 'in_process' || $status === 'pending' || $status === 'authorized') {
                $totalPending += $payment['transaction_amount'];
            }
        }

        $arrayLog = [
            'totalApproved' => $totalApproved,
            'totalOrder' => $totalOrder,
            'totalPending' => $totalPending
        ];

        // validate order state
        if ($totalApproved >= $totalOrder) {
            $statusList = ['approved'];
            $lastPaymentIndex = $this->_getLastPaymentIndex($merchantOrderPayments, $statusList);

            $response = ['key' => $lastPaymentIndex, 'status' => 'approved', 'final' => true];
            $this->dataHelper->log(
                'Order Setted Approved: '.json_encode($arrayLog),
                ConfigData::LOG_FILENAME, $response);

        } elseif ($totalPending >= $totalOrder) {
            // return last status inserted
            $statusList = ['pending', 'in_process'];
            $lastPaymentIndex = $this->_getLastPaymentIndex($merchantOrderPayments, $statusList);

            $response = ['key' => $lastPaymentIndex, 'status' => 'pending', 'final' => false];
            $this->dataHelper->log(
                'Order Setted Pending: '.
                json_encode($arrayLog),
                ConfigData::LOG_FILENAME, $response
            );

        } else {
            // return last status inserted
            $statusList = ['cancelled', 'refunded', 'charged_back', 'in_mediation', 'rejected'];
            $lastPaymentIndex = $this->_getLastPaymentIndex($merchantOrderPayments, $statusList);
            $statusReturned = $merchantOrderPayments[$lastPaymentIndex]['status'];

            $response = [
                'key' => $lastPaymentIndex,
                'status' => $merchantOrderPayments[$lastPaymentIndex]['status'],
                'final' => true
            ];

            $this->dataHelper->log(
                'Order set other status: '.
                $statusReturned,
                ConfigData::LOG_FILENAME,
                $response
            );
        }

        return $response;
    }

    /**
     * @param $payments
     * @param $status
     * @return int
     */
    protected function _getLastPaymentIndex($payments, $status)
    {
        $class = 'Cardpay\Core\Model\Notifications\Topics\MerchantOrder';
        $dates = [];
        foreach ($payments as $key => $payment) {

            if (in_array($payment['status'], $status)) {
                $dates[] = ['key' => $key, 'value' => $payment['last_modified']];
            }
        }
        usort($dates, [$class, '_dateCompare']);
        if ($dates) {
            $lastModified = array_pop($dates);
            return $lastModified['key'];
        }

        return 0;
    }

    /**
     * @param $order
     * @param $data
     * @return array
     * @throws Exception
     */
    public function updateOrder($order, $data)
    {
        $payment = $data['payments'][$data['statusFinal']['key']];
        $orderPayment = $order->getPayment();
        $orderPayment->setAdditionalInformation('paymentResponse', $payment);
        $orderPayment->save();

        if ($this->checkStatusAlreadyUpdated($order, $data)) {
            $message = '[Already updated] '.$this->getMessage($payment);
            $this->dataHelper->log($message);
            return ['text' => $message, 'code' => Response::HTTP_OK];
        }

        $this->updatePaymentInfo($order, $data);

        return $this->changeStatusOrder($order, $data);
    }

    public function checkStatusAlreadyUpdated($order, $data)
    {
        $paymentResponse = $data['payments'][$data['statusFinal']['key']];
        return parent::checkStatusAlreadyUpdated($paymentResponse, $order);
    }

    /**
     * @param $a
     * @param $b
     * @return false|int
     */
    public static function _dateCompare($a, $b)
    {
        $t1 = strtotime($a['value']);
        $t2 = strtotime($b['value']);
        return $t2 - $t1;
    }
}
