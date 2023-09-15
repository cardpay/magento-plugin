<?php

namespace Cardpay\Core\Observer;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\ApiManager;
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
     * @param  Data  $helperData
     * @param  ApiManager  $apiManager
     */
    public function __construct(
        Data $helperData,
        ApiManager  $apiManager,
        BuilderInterface $transactionBuilder
    ) {
        $this->helperData = $helperData;
        $this->paymentStatusHandler = new PaymentStatusHandler($helperData, $apiManager, $transactionBuilder);
    }

    /**
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->helperData->log('OrderCancelObserver, execute');

        $isUnlimitPaymentCancelled = $this->paymentStatusHandler->changePaymentStatus($observer, 'REVERSE', 'VOIDED');
        if (!$isUnlimitPaymentCancelled) {
            throw new LocalizedException(
                __('An error occurred while cancelling Unlimit payment. Please try again later.')
            );
        }
    }
}
