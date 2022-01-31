<?php

namespace Cardpay\Core\Model\Payment;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Response;
use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Cc;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class BankCardPayment extends UnlimintPayment
{
    /**
     * Define payment method code
     */
    const CODE = ConfigData::BANKCARD_PAYMENT_METHOD;

    protected $_code = self::CODE;

    protected $fields = [
        'payment_method_id', 'identification_type', 'identification_number', 'financial_institution', 'entity_type'
    ];

    /**
     * @param DataObject $data
     * @return $this|Cc
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        $infoForm = $data->getData();

        if (isset($infoForm['additional_data'])) {
            if (empty($infoForm['additional_data'])) {
                return $this;
            }
            $additionalData = $infoForm['additional_data'];

            if (isset($additionalData['one_click_pay']) && (int)$additionalData['one_click_pay'] === 1) {
                $additionalData = $this->cleanFieldsOcp($additionalData);
            }

            $info = $this->getInfoInstance();

            $info->setAdditionalInformation($additionalData);
            $info->setAdditionalInformation('method', $infoForm['method']);
            $info->setAdditionalInformation('payment_type_id', "credit_card");
            $info->setAdditionalInformation('payment_method', $additionalData['payment_method_id']);
            $info->setAdditionalInformation('cardholder_name', $additionalData['card_holder_name']);
            $info->setAdditionalInformation('card_number', $additionalData['card_number']);
            $info->setAdditionalInformation('security_code', $additionalData['security_code']);
            $info->setAdditionalInformation('cpf', preg_replace('/[^0-9]+/', '', $additionalData['cpf']));   // leave only digits

            if (!empty($additionalData['card_expiration_date'])) {
                $info->setAdditionalInformation('expiration_date', $additionalData['card_expiration_date']);
            }

            if (isset($additionalData['gateway_mode'])) {
                $info->setAdditionalInformation('gateway_mode', $additionalData['gateway_mode']);
            }

            if (!empty($additionalData['coupon_code'])) {
                $info->setAdditionalInformation('coupon_code', $additionalData['coupon_code']);
            }
        }

        return $this;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return bool
     * @throws LocalizedException
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        $requestParams = $this->createApiRequest();
        return $this->makeApiPayment($requestParams);
    }

    /**
     * @throws LocalizedException
     */
    public function createApiRequest()
    {
        try {
            $order = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();
            $paymentInfo = $this->getPaymentInfo($payment);

            $requestParams = $this->_coreModel->getDefaultRequestParams($paymentInfo, $this->_getQuote(), $order);

            $requestParams['payment_method'] = ConfigData::BANK_CARD_API_PAYMENT_METHOD;

            $requestParams['card_account']['card']['pan'] = $payment->getAdditionalInformation('card_number');
            $requestParams['card_account']['card']['expiration'] = $payment->getAdditionalInformation('expiration_date');
            $requestParams['card_account']['card']['security_code'] = $payment->getAdditionalInformation('security_code');
            $requestParams['card_account']['card']['holder'] = $payment->getAdditionalInformation('cardholder_name');

            $requestParams['customer']['identity'] = $payment->getAdditionalInformation('cpf');

            $requestMasked = $requestParams;

            $pan = $requestMasked['card_account']['card']['pan'];
            $requestMasked['card_account']['card']['pan'] = substr($pan, 0, 6) . '...' . substr($pan, -4);
            $requestMasked['card_account']['card']['security_code'] = '...';

            $this->_helperData->log('CustomPayment::initialize - Credit Card: POST', self::LOG_NAME, $requestMasked);

            $isOnePhasePayment = (1 === (int)$this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_CAPTURE, ScopeInterface::SCOPE_STORE));
            if (!$isOnePhasePayment) {
                $sectionName = isset($requestParams['recurring_data']) ? 'recurring_data' : 'payment_data';     // 2 phase installment or payment
                $requestParams[$sectionName]['preauth'] = 'true';
            }

            return $requestParams;

        } catch (Exception $e) {
            $this->_helperData->log('CustomPayment::initialize - There was an error retrieving the information to create the payment, more details: ' . $e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }
    }

    /**
     * Set info to payment object
     *
     * @param $payment
     *
     * @return array
     */
    protected function getPaymentInfo($payment)
    {
        $paymentInfo = [];

        if (!empty($payment->getAdditionalInformation('cpf'))) {
            $paymentInfo['cpf'] = $payment->getAdditionalInformation('cpf');
        }

        if (!empty($payment->getAdditionalInformation('installments'))) {
            $paymentInfo['installments'] = $payment->getAdditionalInformation('installments');
        }

        return $paymentInfo;
    }

    /**
     * @param $requestParams
     * @return bool
     * @throws LocalizedException|\Cardpay\Core\Model\Api\V1\Exception
     */
    public function makeApiPayment($requestParams)
    {
        $order = $this->getInfoInstance()->getOrder();
        $response = $this->_coreModel->postPayment($requestParams, $order);
        if (isset($response['status']) && ((int)$response['status'] === 200 || (int)$response['status'] === 201)) {
            $this->getInfoInstance()->setAdditionalInformation('paymentResponse', $response['response']);
            return true;
        }

        $messageErrorToClient = $this->_coreModel->getMessageError($response);
        $arrayLog = [
            'response' => $response,
            'message' => $messageErrorToClient
        ];

        $this->_helperData->log('BankCardPayment::initialize - The API returned an error while creating the payment, more details: ' . json_encode($arrayLog));
        throw new LocalizedException(__($messageErrorToClient));
    }

    /**
     * @return mixed|string
     */
    public function getIpAddress()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        if (empty($ip) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (is_array($ip)) {
            return $ip[0];
        }

        if (strpos($ip, ',') !== false) {
            $exploded_ip = explode(',', $ip);
            $ip = $exploded_ip[0];
        }

        return $ip;
    }

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
     * @param CartInterface|null $quote
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = (int)$this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (0 === $isActive) {
            return false;
        }

        return $this->isPaymentMethodAvailable(ConfigData::PATH_BANKCARD_TERMINAL_CODE, ConfigData::PATH_BANKCARD_TERMINAL_PASSWORD);
    }

    /**
     * @param Order $order
     * @throws LocalizedException
     */
    public static function isBankCardPaymentMethod($order)
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

        return ConfigData::BANKCARD_PAYMENT_METHOD === $paymentMethod;
    }
}