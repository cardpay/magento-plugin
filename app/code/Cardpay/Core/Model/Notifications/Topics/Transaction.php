<?php

namespace Cardpay\Core\Model\Notifications\Topics;

use Cardpay\Core\Helper\Data;
use Exception;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class Transaction
{
    /**
     * @var Data
     */
    private $helpderData;

    /**
     * @var BuilderInterface
     */
    private $transactionBuilder;

    /**
     * @param Data $helperData
     * @param BuilderInterface $transactionBuilder
     */
    public function __construct(
        Data             $helperData,
        BuilderInterface $transactionBuilder
    )
    {
        $this->helpderData = $helperData;
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * @param $paymentData
     * @param Order $order
     */
    public function createTransaction($paymentData, $order = null)
    {
        try {
            $this->helpderData->log('Create Transaction', Payment::LOG_NAME, $paymentData);

            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setAdditionalInformation(
                [Order\Payment\Transaction::RAW_DETAILS => (array)$paymentData]
            );
            $formattedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __('The authorized amount is %1.', $formattedPrice);

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    ['raw_details_info' => (array)$paymentData]      // Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
                )
                ->setFailSafe(true)
                ->build(TransactionInterface::TYPE_CAPTURE);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );

            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();

        } catch (Exception $e) {
            $this->helpderData->log('Create transaction error', Payment::LOG_NAME, $e);
            return null;
        }
    }
}