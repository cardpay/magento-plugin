<?php

namespace Cardpay\Core\Observer;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Core;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class OrderCancelObserver implements ObserverInterface
{
    /**
     * @var PaymentStatusHandler
     */
    private $paymentStatusHandler;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @param Data $helperData
     * @param Core $coreModel
     */
    public function __construct(
        Data             $helperData,
        Core             $coreModel,
        BuilderInterface $transactionBuilder
    )
    {
        $this->helperData = $helperData;
        $this->paymentStatusHandler = new PaymentStatusHandler($helperData, $coreModel, $transactionBuilder);
    }

    /**
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->helperData->log('OrderCancelObserver, execute');

        $isUnlimintPaymentCancelled = $this->paymentStatusHandler->changePaymentStatus($observer, 'REVERSE', 'VOIDED');
        if (!$isUnlimintPaymentCancelled) {
            throw new LocalizedException(__('An error occurred while cancelling Unlimint payment. Please try again later.'));
        }
    }
}