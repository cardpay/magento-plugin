<?php

namespace Cardpay\Core\Observer;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\ApiManager;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Topics\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class PaymentStatusHandler
{
    public const ERROR_MESSAGE = 'Operation is not available';

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var ApiManager
     */
    protected $apiModel;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @param  Data  $helperData
     * @param  ApiManager  $apiModel
     */
    public function __construct(
        Data $helperData,
        ApiManager $apiModel,
        BuilderInterface $transactionBuilder
    ) {
        $this->helperData = $helperData;
        $this->apiModel = $apiModel;
        $this->transaction = new Transaction($helperData, $transactionBuilder);
    }

    /**
     * @param  Observer  $observer
     * @param  string  $statusTo
     * @param  string  $expectedResponseStatus
     * @return bool
     * @throws LocalizedException
     */
    public function changePaymentStatus($observer, $statusTo, $expectedResponseStatus)
    {
        /**
         * @var Order
         */
        $order = $observer->getOrder();

        /**
         * @var Payment $payment
         */
        $payment = $order->getPayment();

        if (!isset($payment['additional_information']['paymentResponse'])) {
            throw new LocalizedException(__(self::ERROR_MESSAGE));
        }

        $canPaymentStatusBeChanged = $this->canPaymentStatusBeChanged($payment);
        if (!$canPaymentStatusBeChanged) {
            throw new LocalizedException(__(self::ERROR_MESSAGE));
        }

        $paymentResponse = $payment['additional_information']['paymentResponse'];

        if ('cardpay_custom' === $payment['additional_information']['method'] &&
            !empty($payment['additional_information']['installments'])) {
            $paymentResponse['installments'] = $payment['additional_information']['installments'];
        }

        if (isset($paymentResponse['payment_data'])) {
            $apiStructure = 'payment_data';
            $apiEndpoint = '/api/payments/';
        } else {
            throw new LocalizedException(__(self::ERROR_MESSAGE));
        }

        // only 2-phase payment can be updated
        if ('AUTHORIZED' !== $paymentResponse[$apiStructure]['status']) {
            throw new LocalizedException(__(self::ERROR_MESSAGE));
        }

        $url = $apiEndpoint.$paymentResponse[$apiStructure]['id'];
        $requestParams = $this->getChangeStatusParams($observer, $statusTo, $apiStructure);

        $api = $this->apiModel->getApiInstance($order);

        $this->helperData->log('PaymentStatusHandler::changePaymentStatus, request: '.print_r($requestParams, true));
        $response = $api->patch($url, $requestParams);
        $this->helperData->log('PaymentStatusHandler::changePaymentStatus, response: '.print_r($response, true));

        if ($response !== null && isset($response['status']) &&
            (Response::METHOD_NOT_ALLOWED === $response['status'])) {
            throw new LocalizedException(__(self::ERROR_MESSAGE));
        }

        if (isset($response['response'][$apiStructure]['status'])
            && $expectedResponseStatus === $response['response'][$apiStructure]['status']) {
            $this->createTransactionForInvoice($observer, $paymentResponse[$apiStructure]);
            return true;
        }


        return false;
    }

    /**
     * @param  Observer  $observer
     * @param  string  $paymentId
     */
    private function createTransactionForInvoice($observer, $paymentData)
    {
        if (is_null($observer->getEvent()) || is_null($observer->getEvent()->getInvoice())) {
            return;
        }

        $paymentId = $paymentData['id'];

        $invoice = $observer->getEvent()->getInvoice();
        $invoice->setTransactionId($paymentId);
        $invoice->save();

        $order = $observer->getOrder();
        $this->transaction->createTransaction($paymentData, $order);
        $order->save();

        $this->helperData->log('Transaction created, id = '.$paymentId);
    }

    private function canPaymentStatusBeChanged($payment): bool
    {
        if ($payment === null
            || !isset($payment['additional_information'])
            || empty($payment['additional_information']['method'])
            || !isset($payment['additional_information']['paymentResponse'])
        ) {
            return false;
        }

        // only BANKCARD payment method is allowed (payment or installment)
        if ('cardpay_custom' !== $payment['additional_information']['method']) {
            return false;
        }

        $paymentResponse = $payment['additional_information']['paymentResponse'];

        return (isset($paymentResponse['payment_data']) && !empty($paymentResponse['payment_data']['id']));
    }

    /**
     * @param  Observer  $observer
     * @param  string  $statusTo
     * @param  string  $apiStructure
     * @return array
     */
    private function getChangeStatusParams($observer, $statusTo, $apiStructure)
    {
        $requestParams = [];

        $requestParams['request']['id'] = time();
        $requestParams['request']['time'] = date('c');
        $requestParams['operation'] = 'CHANGE_STATUS';

        $requestParams[$apiStructure]['status_to'] = $statusTo;

        if (InvoiceRegisterObserver::COMPLETE_STATUS === $statusTo
            && !is_null($observer->getEvent())
            && !is_null($observer->getEvent()->getInvoice())
        ) {
            $invoice = $observer->getEvent()->getInvoice();   // capture the partial amount, if needed
            $requestParams[$apiStructure]['amount'] = $invoice->getGrandTotal();
        }

        return $requestParams;
    }
}
