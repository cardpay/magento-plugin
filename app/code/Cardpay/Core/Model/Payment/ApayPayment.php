<?php

namespace Cardpay\Core\Model\Payment;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Response;
use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class ApayPayment extends UnlimitPayment
{
    /**
     * Define payment method code
     */
    const CODE = ConfigData::APAY_PAYMENT_METHOD;

    const APAY_MESSAGE = 'ApayPayment::initialize';

    protected $_code = self::CODE; //NOSONAR

    protected $fieldsFebraban = [
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
     * @param  DataObject  $data
     * @return $this|ApayPayment
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        $infoForm = $data->getData();

        if (!isset($infoForm['additional_data']) || empty($infoForm['additional_data'])) {
            return $this;
        }

        $additionalData = $infoForm['additional_data'];

        $info = $this->getInfoInstance();

        if (!empty($infoForm['method'])) {
            $info->setAdditionalInformation('method', $infoForm['method']);
        }

        if (!empty($additionalData['payment_method_apay'])) {
            $info->setAdditionalInformation('payment_method', $additionalData['payment_method_apay']);
            $info->setAdditionalInformation('payment_method_id', $additionalData['payment_method_apay']);
        }

        if (!empty($additionalData['encrypted_data'])) {
            $info->setAdditionalInformation('encrypted_data', $additionalData['encrypted_data']);
        }

        foreach ($this->fieldsFebraban as $key) {
            if (isset($additionalData[$key])) {
                $info->setAdditionalInformation($key, $additionalData[$key]);
            }
        }

        return $this;
    }

    /**
     * @param  string  $paymentAction
     * @param  object  $stateObject
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log(self::APAY_MESSAGE." - Apay: init prepare post payment", self::LOG_NAME);

            /**
             * @var Quote
             */
            $quote = $this->_getQuote();

            /**
             * @var Order
             */
            $order = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $paymentInfo = [];

            if (!empty($payment->getAdditionalInformation('encrypted_data'))) {
                $paymentInfo['encrypted_data'] = base64_encode($payment->getAdditionalInformation('encrypted_data'));
            }

            $paymentInfo['payment_method'] = ConfigData::APAY_API_PAYMENT_METHOD;
            $requestParams = $this->_coreModel->getDefaultRequestParams($paymentInfo, $quote, $order, []);
            $requestParams['customer']['full_name'] = trim($order->getCustomerName());

            $requestParams['payment_method'] = ConfigData::APAY_API_PAYMENT_METHOD;
            $requestParams['customer']['full_name'] = trim($order->getCustomerName());

            $this->_helperData->log(self::APAY_MESSAGE." - Preference to POST", 'cardpay.log', $requestParams);
        } catch (Exception $e) {
            $this->_helperData->log(
                self::APAY_MESSAGE.
                " - There was an error retrieving the information to create the payment, more details: ".
                $e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }

        $response = $this->_apiModel->postPayment($requestParams, $order);
        $this->_helperData->log(self::APAY_MESSAGE." - POST RESPONSE", self::LOG_NAME, $response);

        return $this->handleApiResponse($response, self::APAY_MESSAGE);
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
     * @param  CartInterface|null  $quote
     *
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = (int)$this->_scopeConfig->getValue(ConfigData::PATH_APAY_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (0 === $isActive) {
            return false;
        }

        return $this->isPaymentMethodAvailable(
            ConfigData::PATH_APAY_TERMINAL_CODE,
            ConfigData::PATH_APAY_TERMINAL_PASSWORD
        );
    }

    /**
     * @param  Order  $order
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

        return ConfigData::APAY_PAYMENT_METHOD === $paymentMethod;
    }
}
