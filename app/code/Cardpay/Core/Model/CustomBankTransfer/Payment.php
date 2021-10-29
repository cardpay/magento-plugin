<?php

namespace Cardpay\Core\Model\CustomBankTransfer;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Response;
use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Payment
 *
 * @package Cardpay\Core\Model\CustomBankTransfer
 */
class Payment extends \Cardpay\Core\Model\Custom\Payment
{
    /**
     * Define payment method code
     */
    const CODE = 'cardpay_custom_bank_transfer';

    protected $_code = self::CODE;

    protected $fields = [
        "payment_method_id", "identification_type", "identification_number", "financial_institution", "entity_type"
    ];

    /**
     * @param DataObject $data
     * @return $this|\Cardpay\Core\Model\Custom\Payment
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        if (!($data instanceof DataObject)) {
            $data = new DataObject($data);
        }

        $infoForm = $data->getData();

        if (isset($infoForm['additional_data'])) {
            if (empty($infoForm['additional_data'])) {
                return $this;
            }
            $additionalData = $infoForm['additional_data'];

            $info = $this->getInfoInstance();
            $info->setAdditionalInformation('method', $additionalData['method']);

            if (!empty($additionalData['coupon_code'])) {
                $info->setAdditionalInformation('coupon_code', $additionalData['coupon_code']);
            }

            foreach ($this->fields as $key) {
                if (isset($additionalData[$key])) {
                    $info->setAdditionalInformation($key, $additionalData[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this|bool|\Magento\Payment\Model\Method\Cc|\Cardpay\Core\Model\Custom\Payment
     * @throws LocalizedException
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log("CustomPaymentTicket::initialize - Ticket: init prepare post payment", self::LOG_NAME);
            $quote = $this->_getQuote();
            $order = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $payment_info = [];

            if ($payment->getAdditionalInformation("coupon_code") != "") {
                $payment_info['coupon_code'] = $payment->getAdditionalInformation("coupon_code");
            }

            $requestParams = $this->_coreModel->getDefaultRequestParams($payment_info, $quote, $order);

            $requestParams['payment_method_id'] = $payment->getAdditionalInformation("payment_method_id");

            if ($payment->getAdditionalInformation("identification_type") != "") {
                $requestParams['payer']['identification']['type'] = $payment->getAdditionalInformation("identification_type");
            }
            if ($payment->getAdditionalInformation("identification_number") != "") {
                $requestParams['payer']['identification']['number'] = $payment->getAdditionalInformation("identification_number");
            }
            if ($payment->getAdditionalInformation("entity_type") != "") {
                $requestParams['payer']['entity_type'] = $payment->getAdditionalInformation("entity_type");
            }
            if ($payment->getAdditionalInformation("financial_institution") != "") {
                $requestParams['transaction_details']['financial_institution'] = $payment->getAdditionalInformation("financial_institution");
            }

            //Get IP address
            $requestParams['additional_info']['ip_address'] = $this->getIpAddress();
            $requestParams['callback_url'] = $this->_urlBuilder->getUrl('cardpay/checkout/page?callback=' . $requestParams['payment_method_id']);

            $this->_helperData->log("CustomPaymentTicket::initialize - Preference to POST", ConfigData::CUSTOM_LOG_PREFIX, $requestParams);
        } catch (Exception $e) {
            $this->_helperData->log("CustomPaymentTicket::initialize - There was an error retrieving the information to create the payment, more details: " . $e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }

        $response = $this->_coreModel->postPayment($requestParams);
        $this->_helperData->log("CustomPaymentTicket::initialize - POST /api/payments RESPONSE", self::LOG_NAME, $response);

        if (isset($response['status']) && ($response['status'] == 200 || $response['status'] == 201)) {
            $payment = $response['response'];
            $this->getInfoInstance()->setAdditionalInformation("paymentResponse", $payment);
            return true;
        }

        $messageErrorToClient = $this->_coreModel->getMessageError($response);
        $arrayLog = [
            "response" => $response,
            "message" => $messageErrorToClient
        ];
        $this->_helperData->log("CustomPaymentTicket::initialize - The API returned an error while creating the payment, more details: " . json_encode($arrayLog));
        throw new LocalizedException(__($messageErrorToClient));
    }

    /**
     * @return mixed|string
     */
    public function getIpAddress()
    {
        $ip = "";

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
        $isActive = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (empty($isActive)) {
            return false;
        }

        return parent::isPaymentMethodAvailable();
    }
}
