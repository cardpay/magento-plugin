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

class BoletoPayment extends UnlimintPayment
{
    /**
     * Define payment method code
     */
    const CODE = ConfigData::BOLETO_PAYMENT_METHOD;

    protected $_isOffline = true;

    protected $_code = self::CODE;

    protected $fields_febraban = [
        'firstName', 'lastName', 'docType', 'docNumber', 'address', 'addressNumber',
        'addressCity', 'addressState', 'addressZipcode'
    ];

    /**
     * @param DataObject $data
     * @return $this|BoletoPayment
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        $infoForm = $data->getData();
        if (empty($infoForm['additional_data'])) {
            return $this;
        }

        $additionalData = $infoForm['additional_data'];

        $info = $this->getInfoInstance();

        if (!empty($infoForm['method'])) {
            $info->setAdditionalInformation('method', $infoForm['method']);
        }

        if (!empty($additionalData['payment_method_ticket'])) {
            $info->setAdditionalInformation('payment_method', $additionalData['payment_method_ticket']);
            $info->setAdditionalInformation('payment_method_id', $additionalData['payment_method_ticket']);
        }

        if (!empty($additionalData['cpf'])) {
            $info->setAdditionalInformation('cpf', preg_replace('/[^\d]+/', '', $additionalData['cpf']));   // leave only digits
        }
        if (!empty($additionalData['zip'])) {
            $info->setAdditionalInformation('zip', preg_replace('/[^\d]+/', '', $additionalData['zip']));   // leave only digits
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
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log("BoletoPayment::initialize - Ticket: init prepare post payment", self::LOG_NAME);

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
            if (!empty($payment->getAdditionalInformation('cpf'))) {
                $paymentInfo['cpf'] = $payment->getAdditionalInformation('cpf');
            }
            if (!empty($payment->getAdditionalInformation('zip'))) {
                $paymentInfo['zip'] = $payment->getAdditionalInformation('zip');
            }

            $paymentInfo['payment_method'] = ConfigData::BOLETO_API_PAYMENT_METHOD;
            $requestParams = $this->_coreModel->getDefaultRequestParams($paymentInfo, $quote, $order, []);
            $requestParams['customer']['full_name'] = trim($order->getCustomerName());

            $this->_helperData->log("BoletoPayment::initialize - Preference to POST", 'cardpay.log', $requestParams);
        } catch (Exception $e) {
            $this->_helperData->log("BoletoPayment::initialize - There was an error retrieving the information to create the payment, more details: " . $e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }

        $response = $this->_coreModel->postPayment($requestParams, $order);
        $this->_helperData->log("BoletoPayment::initialize - POST RESPONSE", self::LOG_NAME, $response);

        if (isset($response['status']) && ((int)$response['status'] === 200 || (int)$response['status'] === 201)) {
            $payment = $response['response'];
            $this->getInfoInstance()->setAdditionalInformation("paymentResponse", $payment);
            return true;
        }

        $messageErrorToClient = $this->_coreModel->getMessageError($response);

        $arrayLog = [
            'response' => $response,
            'message' => $messageErrorToClient
        ];

        $this->_helperData->log('BoletoPayment::initialize - The API returned an error while creating the payment, more details: ' . json_encode($arrayLog));

        throw new LocalizedException(__($messageErrorToClient));
    }

    /**
     * Return tickets options
     *
     * @return array
     */
    public function getTicketsOptions()
    {
        $pm['id'] = 1;
        $pm['payment_type_id'] = 'boleto';

        $tickets[] = $pm;

        return $tickets;
    }

    /**
     * @throws LocalizedException
     */
    function setOrderSubtotals($data)
    {
        $total = $data['transaction_details']['total_paid_amount'];

        $order = $this->getInfoInstance()->getOrder();
        $order->setGrandTotal($total);
        $order->setBaseGrandTotal($total);

        $couponAmount = $data['coupon_amount'];
        if ($couponAmount) {
            $order->setDiscountCouponAmount($couponAmount * -1);
            $order->setBaseDiscountCouponAmount($couponAmount * -1);
        }

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
        $isActive = (int)$this->_scopeConfig->getValue(ConfigData::PATH_TICKET_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (0 === $isActive) {
            return false;
        }

        return $this->isPaymentMethodAvailable(ConfigData::PATH_BOLETO_TERMINAL_CODE, ConfigData::PATH_BOLETO_TERMINAL_PASSWORD);
    }

    /**
     * @param Order $order
     * @throws LocalizedException
     */
    public static function isBoletoPaymentMethod($order)
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

        return ConfigData::BOLETO_PAYMENT_METHOD === $paymentMethod;
    }
}
