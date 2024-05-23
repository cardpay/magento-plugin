<?php

namespace Cardpay\Core\Model\Payment;

use Cardpay\Core\Exceptions\UnlimitBaseException;
use Cardpay\Core\Helper\ConfigData;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Cc;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class BankCardPayment extends UnlimitPayment
{
    /**
     * Define payment method code
     */
    const CODE = ConfigData::BANKCARD_PAYMENT_METHOD;

    protected $ulCode = self::CODE;

    const BANCARD_MESSAGE = 'BankCardPayment::initialize';
    protected const MAX_ZIP_LENGTH = 12;

    protected $fields = [
        'payment_method_id',
        'identification_type',
        'identification_number',
        'financial_institution',
        'entity_type'
    ];

    protected function safeSetAdditionalInformation(InfoInterface $info, array $dataArray, array $keys)
    {
        foreach ($keys as $infoKey => $dataKey) {
            if (isset($dataArray[$dataKey])) {
                $info->setAdditionalInformation($infoKey, $dataArray[$dataKey]);
            }
        }
    }

    /**
     * @param DataObject $data
     *
     * @return $this|Cc
     * @throws UnlimitBaseException
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
            $this->safeSetAdditionalInformation(
                $info,
                $additionalData,
                [
                    'expiration_date' => 'card_expiration_date',
                    'gateway_mode'    => 'gateway_mode',
                    'payment_method'  => 'payment_method_id',
                    'cardholder_name' => 'card_holder_name',
                    'card_number'     => 'card_number',
                    'security_code'   => 'security_code',
                    'installments'    => 'installments',
                    'total_amount'    => 'total_amount',
                ]
            );

            $info->setAdditionalInformation('method', $infoForm['method']);
            $info->setAdditionalInformation('payment_type_id', "credit_card");
            $info->setAdditionalInformation(
                'cpf',
                preg_replace('/[\d]+/', '', $additionalData['cpf'] ?? '')
            );   // leave only digits
        }

        return $this;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return bool
     * @throws UnlimitBaseException
     */
    public function initialize($paymentAction, $stateObject)
    {
        $requestParams = $this->createApiRequest();

        return $this->makeApiPayment($requestParams);
    }

    /**
     * @throws UnlimitBaseException
     */
    public function createApiRequest()
    {
        try {
            $order                         = $this->getInfoInstance()->getOrder();
            $payment                       = $order->getPayment();
            $paymentInfo                   = $this->getPaymentInfo($payment);
            $paymentInfo['payment_method'] = ConfigData::BANK_CARD_API_PAYMENT_METHOD;

            $requestParams = $this->_coreModel->getDefaultRequestParams($paymentInfo, $this->_getQuote(), $order, []);

            if (strlen($requestParams['merchant_order']['shipping_address']['zip']) > self::MAX_ZIP_LENGTH) {
                throw new UnlimitBaseException(
                    __(
                        "Zip / Postal Code is invalid, must be " .
                        self::MAX_ZIP_LENGTH . " characters."
                    )
                );
            }

            if ($this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_ASK_CPF)) {
                $requestParams['customer']['identity'] = $payment->getAdditionalInformation('cpf');
            }
            $mode = $this->_scopeConfig->getValue(ConfigData::PATH_BANKCARD_API_ACCESS_MODE);

            if ($mode === 'gateway') {
                $requestParams['card_account']['card']['pan']           = $payment->getAdditionalInformation(
                    'card_number'
                );
                $requestParams['card_account']['card']['expiration']    = substr_replace(
                    $payment->getAdditionalInformation('expiration_date'),
                    '20',
                    3,
                    0
                );
                $requestParams['card_account']['card']['security_code'] =
                    $payment->getAdditionalInformation('security_code');
                $requestParams['card_account']['card']['holder']        =
                    $payment->getAdditionalInformation('cardholder_name');
                $requestParams['customer']['identity']                  = $payment->getAdditionalInformation('cpf');

                $requestMasked = $requestParams;

                $pan                                                    = $requestMasked['card_account']['card']['pan'];
                $requestMasked['card_account']['card']['pan']           = substr($pan, 0, 6) . '...' . substr($pan, -4);
                $requestMasked['card_account']['card']['security_code'] = '...';

                $this->_helperData->log(
                    'CustomPayment::initialize - Credit Card: POST',
                    self::LOG_NAME,
                    $requestMasked
                );
            }

            $numInstallments        = (int)$payment->getAdditionalInformation('installments');
            $installmentType        = $this->_scopeConfig->getValue(ConfigData::PATH_BANKCARD_INSTALLMENT_TYPE);
            $areInstallmentsEnabled = (1 === (int)$this->_scopeConfig->getValue(
                    ConfigData::PATH_CUSTOM_INSTALLMENT,
                    ScopeInterface::SCOPE_STORE
                ));

            $capturePayment = (int)$this->_scopeConfig->getValue(
                    ConfigData::PATH_CUSTOM_CAPTURE,
                    ScopeInterface::SCOPE_STORE
                ) === 1;

            if (( ! $areInstallmentsEnabled) || $numInstallments === 1) {
                $isPreAuth = ! $capturePayment;
            } else {
                $isPreAuth = (
                    ($installmentType !== 'IF') &&
                    ( ! $capturePayment)
                );
            }

            if ($isPreAuth) {
                $requestParams['payment_data']['preauth'] = 'true';
            }

            if ($numInstallments > 1) {
                $requestParams['payment_data']['installments'] = $payment->getAdditionalInformation('installments');
            }

            if (($installmentType === 'IF') && ($payment->getAdditionalInformation('installments') > 1)) {
                $requestParams['payment_data']['installment_amount'] = round(
                    $payment->getAdditionalInformation('total_amount') /
                    $payment->getAdditionalInformation('installments'),
                    2
                );
            }

            return $requestParams;
        } catch (UnlimitBaseException $e) {
            $this->_helperData->log(
                'CustomPayment::initialize - There was an error
                retrieving the information to create the payment, more details: ' .
                $e->getMessage()
            );
            throw new UnlimitBaseException($e->getMessage());
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

        if ( ! empty($payment->getAdditionalInformation('cpf'))) {
            $paymentInfo['cpf'] = $payment->getAdditionalInformation('cpf');
        }

        if ( ! empty($payment->getAdditionalInformation('installments'))) {
            $paymentInfo['installments'] = $payment->getAdditionalInformation('installments');
        }

        return $paymentInfo;
    }

    /**
     * @param $requestParams
     *
     * @return bool
     */
    public function makeApiPayment($requestParams)
    {
        $order    = $this->getInfoInstance()->getOrder();
        $response = $this->_apiModel->postPayment($requestParams, $order);

        return $this->handleApiResponse($response, self::BANCARD_MESSAGE);
    }

    /**
     * @return mixed|string
     */
    public function getIpAddress()
    {
        $ip = '';

        if ( ! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        if (empty($ip) && ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
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
            $ip          = $exploded_ip[0];
        }

        return $ip;
    }

    public function setOrderSubtotals($data)
    {
        $total = $data['transaction_details']['total_paid_amount'];

        $order = $this->getInfoInstance()->getOrder();
        $order->setGrandTotal($total);
        $order->setBaseGrandTotal($total);

        $this->getInfoInstance()->setOrder($order);
    }

    /**
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws UnlimitBaseException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = (int)$this->_scopeConfig->getValue(
            ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
        if (0 === $isActive) {
            return false;
        }

        return $this->isPaymentMethodAvailable(
            ConfigData::PATH_BANKCARD_TERMINAL_CODE,
            ConfigData::PATH_BANKCARD_TERMINAL_PASSWORD
        );
    }

    /**
     * @param Order $order
     *
     * @throws UnlimitBaseException
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

        return ConfigData::BANKCARD_PAYMENT_METHOD === $paymentMethod;
    }
}
