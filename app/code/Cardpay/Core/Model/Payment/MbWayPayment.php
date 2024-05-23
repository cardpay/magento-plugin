<?php

namespace Cardpay\Core\Model\Payment;

use Cardpay\Core\Exceptions\UnlimitBaseException;
use Cardpay\Core\Helper\ConfigData;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class MbWayPayment extends UnlimitPayment
{
    /**
     * Define payment method code
     */
    const CODE = ConfigData::MBWAY_PAYMENT_METHOD;

    const MBWAY_MESSAGE = 'MBWAYPayment::initialize';

    protected $_code = self::CODE; //NOSONAR

    protected $fields_febraban = [
        'firstName',
        'lastName',
        'docType',
        'docNumber',
        'address',
        'addressNumber',
        'addressCity',
        'addressState',
        'addressZipcode'
    ];

    /**
     * @param DataObject $data
     *
     * @return $this|MbWayPayment
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        $infoForm = $data->getData();

        if ( ! isset($infoForm['additional_data']) || empty($infoForm['additional_data'])) {
            return $this;
        }

        $additionalData = $infoForm['additional_data'];

        $info = $this->getInfoInstance();

        if ( ! empty($infoForm['method'])) {
            $info->setAdditionalInformation('method', $infoForm['method']);
        }

        if ( ! empty($additionalData['payment_method_mbway'])) {
            $info->setAdditionalInformation('payment_method', $additionalData['payment_method_mbway']);
            $info->setAdditionalInformation('payment_method_id', $additionalData['payment_method_mbway']);
        }

        if ( ! empty($additionalData['phone_number'])) {
            $info->setAdditionalInformation('phone_number', $additionalData['phone_number']);
        }

        foreach ($this->fields_febraban as $key) {
            if (isset($additionalData[$key])) {
                $info->setAdditionalInformation($key, $additionalData[$key]);
            }
        }

        return $this;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log(
                self::MBWAY_MESSAGE . " - MBWAY: init prepare post payment",
                self::LOG_NAME
            );

            /**
             * @var Quote
             */
            $quote = $this->_getQuote();

            /**
             * @var Order
             */
            $order   = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $paymentInfo = [];

            $paymentInfo['payment_method'] = ConfigData::MBWAY_API_PAYMENT_METHOD;

            $requestParams                          = $this->_coreModel->getDefaultRequestParams(
                $paymentInfo,
                $quote,
                $order,
                []
            );
            $requestParams['customer']['full_name'] = trim($order->getCustomerName());

            if ( ! empty($payment->getAdditionalInformation('phone_number'))) {
                $requestParams['ewallet_account']['id'] = $payment->getAdditionalInformation('phone_number');
            }

            $requestParams['payment_method']        = ConfigData::MBWAY_API_PAYMENT_METHOD;
            $requestParams['customer']['full_name'] = trim($order->getCustomerName());

            $this->_helperData->log(
                self::MBWAY_MESSAGE . " - Preference to POST",
                'cardpay.log',
                $requestParams
            );
        } catch (UnlimitBaseException $e) {
            $this->_helperData->log(
                self::MBWAY_MESSAGE .
                " - There was an error retrieving the information to create the payment, more details: " .
                $e->getMessage()
            );
            throw new UnlimitBaseException($e->getMessage());
        }

        $response = $this->_apiModel->postPayment($requestParams, $order);
        $this->_helperData->log(self::MBWAY_MESSAGE . " - POST RESPONSE", self::LOG_NAME, $response);

        return $this->handleApiResponse($response, self::MBWAY_MESSAGE);
    }

    /**
     * @throws LocalizedException
     */
    public function setOrderSubtotals($data)
    {
        $total = $data['transaction_details']['total_paid_amount'];

        $order = $this->getInfoInstance()->getOrder();
        $order->setGrandTotal($total);
        $order->setBaseGrandTotal($total);

        $this->getInfoInstance()->setOrder($order);
    }

    /**
     * is payment method available?
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = (int)$this->_scopeConfig->getValue(ConfigData::PATH_MBWAY_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (0 === $isActive) {
            return false;
        }

        return $this->isPaymentMethodAvailable(
            ConfigData::PATH_MBWAY_TERMINAL_CODE,
            ConfigData::PATH_MBWAY_TERMINAL_PASSWORD
        );
    }

    /**
     * @param Order $order
     *
     * @throws LocalizedException
     */
    public static function isPaymentMethod($order)
    {
        if (is_null($order)) {
            return false;
        }

        /**
         * @var Order\Payment
         */
        $payment = $order->getPayment();
        if (is_null($payment) || is_null($payment->getMethodInstance())) {
            return false;
        }

        $paymentMethod = $payment->getMethodInstance()->getCode();

        return ConfigData::MBWAY_PAYMENT_METHOD === $paymentMethod;
    }
}
