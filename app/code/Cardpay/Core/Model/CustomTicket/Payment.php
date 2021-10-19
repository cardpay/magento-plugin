<?php

namespace Cardpay\Core\Model\CustomTicket;

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
 * @package Cardpay\Core\Model\CustomTicket
 */
class Payment extends \Cardpay\Core\Model\Custom\Payment
{
    /**
     * Define payment method code
     */
    const CODE = 'cardpay_customticket';

    protected $_isOffline = true;

    protected $_code = self::CODE;

    protected $fields_febraban = array(
        "firstName", "lastName", "docType", "docNumber", "address", "addressNumber", "addressCity", "addressState", "addressZipcode"
    );

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
            $info->setAdditionalInformation('method', $infoForm['method']);
            $info->setAdditionalInformation('payment_method', $additionalData['payment_method_ticket']);
            $info->setAdditionalInformation('payment_method_id', $additionalData['payment_method_ticket']);
            $info->setAdditionalInformation('cpf', preg_replace('/[^0-9]+/', '', $additionalData['cpf']));   // leave only digits

            if (!empty($additionalData['coupon_code'])) {
                $info->setAdditionalInformation('coupon_code', $additionalData['coupon_code']);
            }

            foreach ($this->fields_febraban as $key) {
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
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log("CustomPaymentTicket::initialize - Ticket: init prepare post payment", self::LOG_NAME);

            $quote = $this->_getQuote();
            $order = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $paymentInfo = array();
            if (!empty($payment->getAdditionalInformation('cpf'))) {
                $paymentInfo['cpf'] = $payment->getAdditionalInformation('cpf');
            }

            $requestParams = $this->_coreModel->getDefaultRequestParams($paymentInfo, $quote, $order);
            $requestParams['payment_method'] = 'BOLETO';

            $customer = $this->_customerSession->getCustomer();
            if ($customer != null) {
                $requestParams['customer']['full_name'] = trim($customer->getFirstname() . ' ' . $customer->getLastname());
            }

            $this->_helperData->log("CustomPaymentTicket::initialize - Preference to POST", 'cardpay.log', $requestParams);
        } catch (Exception $e) {
            $this->_helperData->log("CustomPaymentTicket::initialize - There was an error retrieving the information to create the payment, more details: " . $e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }

        $response = $this->_coreModel->postPayment($requestParams);
        $this->_helperData->log("CustomPaymentTicket::initialize - POST RESPONSE", self::LOG_NAME, $response);

        if (isset($response['status']) && ($response['status'] == 200 || $response['status'] == 201)) {
            $payment = $response['response'];
            $this->getInfoInstance()->setAdditionalInformation("paymentResponse", $payment);
            return true;

        } else {
            $messageErrorToClient = $this->_coreModel->getMessageError($response);

            $arrayLog = array(
                "response" => $response,
                "message" => $messageErrorToClient
            );

            $this->_helperData->log("CustomPaymentTicket::initialize - The API returned an error while creating the payment, more details: " . json_encode($arrayLog));

            throw new LocalizedException(__($messageErrorToClient));
        }
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
        $isActive = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_TICKET_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (empty($isActive)) {
            return false;
        }

        return parent::isPaymentMethodAvailable();
    }
}
