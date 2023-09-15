<?php

namespace Cardpay\Core\Observer;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\ApiManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class InvoiceRegisterObserver implements ObserverInterface
{
    public const COMPLETE_STATUS = 'COMPLETE';

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
    public function __construct(Data $helperData, ApiManager $apiManager, BuilderInterface $transactionBuilder)
    {
        $this->helperData = $helperData;
        $this->paymentStatusHandler = new PaymentStatusHandler($helperData, $apiManager, $transactionBuilder);
    }

    public function execute(Observer $observer)
    {
        $this->helperData->log('InvoiceRegisterObserver, execute');

        $isUnlimitPaymentCompleted = $this->paymentStatusHandler->changePaymentStatus(
            $observer,
            self::COMPLETE_STATUS,
            $this->getExpectedResponseStatus($observer)
        );
        if (!$isUnlimitPaymentCompleted) {
            throw new LocalizedException(
                __('An error occurred while completing Unlimit payment. Please try again later.')
            );
        }
    }

    /**
     * @param  Observer  $observer
     * @throws LocalizedException
     */
    private function getExpectedResponseStatus($observer)
    {
        $payment = $observer->getOrder()->getPayment();

        if (!isset($payment['additional_information']['paymentResponse'])) {
            throw new LocalizedException(__(PaymentStatusHandler::ERROR_MESSAGE));
        }

        $paymentResponse = $payment['additional_information']['paymentResponse'];
        if (isset($paymentResponse['payment_data'])) {
            return 'COMPLETED';
        }

        throw new LocalizedException(__(PaymentStatusHandler::ERROR_MESSAGE));
    }
}
