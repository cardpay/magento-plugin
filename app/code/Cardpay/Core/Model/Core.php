<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Block\Adminhtml\System\Config\Version;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as dataHelper;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Helper\Response;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Core Model of CP plugin, used by all payment methods
 *
 * Class Core
 *
 * @package Cardpay\Core\Model
 */
class Core extends AbstractMethod
{
    private const API_ERROR_MESSAGE_PREFIX = "Errors";
    private const API_ERROR_MESSAGE_POSTFIX = "are incorrect";
    private const PAN_API_FIELD = "card_account.card.pan";
    private const EXPIRATION_API_FIELD = "card_account.card.expiration";

    /**
     * @var string
     */
    protected $_code = 'cardpay';

    /**
     * {@inheritdoc}
     */
    protected $_isGateway = true;

    /**
     * {@inheritdoc}
     */
    protected $_canOrder = true;

    /**
     * {@inheritdoc}
     */
    protected $_canAuthorize = true;

    /**
     * {@inheritdoc}
     */
    protected $_canCapture = true;

    /**
     * {@inheritdoc}
     */
    protected $_canCapturePartial = true;

    /**
     * {@inheritdoc}
     */
    protected $_canRefund = true;

    /**
     * {@inheritdoc}
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * {@inheritdoc}
     */
    protected $_canVoid = true;

    /**
     * {@inheritdoc}
     */
    protected $_canUseInternal = true;

    /**
     * {@inheritdoc}
     */
    protected $_canUseCheckout = true;

    /**
     * {@inheritdoc}
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * {@inheritdoc}
     */
    protected $_canReviewPayment = true;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Cardpay\Core\Helper\
     */
    protected $_coreHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var
     */
    protected $_accessToken;
    /**
     * @var
     */
    protected $terminalCode;
    /**
     * @var
     */
    protected $terminalPassword;

    /**
     * @var MessageInterface
     */
    protected $_statusMessage;

    /**
     * @var MessageInterface
     */
    protected $_statusDetailMessage;

    /**
     * @var TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var Image
     */
    protected $_helperImage;

    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetaData;

    protected $_helperData;

    /**
     * @var Version
     */
    protected $_version;

    /**
     * Core constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param dataHelper $coreHelper
     * @param OrderFactory $orderFactory
     * @param MessageInterface $statusMessage
     * @param MessageInterface $statusDetailMessage
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Logger $logger
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param OrderSender $orderSender
     * @param Session $customerSession
     * @param UrlInterface $urlBuilder
     * @param Image $helperImage
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param dataHelper $helperData
     */
    public function __construct(
        StoreManagerInterface           $storeManager,
        dataHelper                      $coreHelper,
        OrderFactory                    $orderFactory,
        MessageInterface                $statusMessage,
        MessageInterface                $statusDetailMessage,
        Context                         $context,
        Registry                        $registry,
        ExtensionAttributesFactory      $extensionFactory,
        AttributeValueFactory           $customAttributeFactory,
        Logger                          $logger,
        Data                            $paymentData,
        ScopeConfigInterface            $scopeConfig,
        TransactionFactory              $transactionFactory,
        InvoiceSender                   $invoiceSender,
        OrderSender                     $orderSender,
        Session                         $customerSession,
        UrlInterface                    $urlBuilder,
        Image                           $helperImage,
        \Magento\Checkout\Model\Session $checkoutSession,
        Version                         $version,
        ProductMetadataInterface        $productMetadata
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            []
        );

        $this->_storeManager = $storeManager;
        $this->_coreHelper = $coreHelper;
        $this->_orderFactory = $orderFactory;
        $this->_statusMessage = $statusMessage;
        $this->_statusDetailMessage = $statusDetailMessage;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_orderSender = $orderSender;
        $this->_customerSession = $customerSession;
        $this->_urlBuilder = $urlBuilder;
        $this->_helperImage = $helperImage;
        $this->_checkoutSession = $checkoutSession;
        $this->_productMetaData = $productMetadata;
        $this->_version = $version;
    }

    /**
     * Retrieves Quote
     *
     * @param integer $quoteId
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function _getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Retrieves Order
     *
     * @param integer $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    public function _getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Return array with data of payment of order loaded with order_id param
     *
     * @param $order_id
     *
     * @return array
     */
    public function getInfoPaymentByOrder($order_id)
    {
        $order = $this->_getOrder($order_id);
        $payment = $order->getPayment();
        $info_payments = [];
        $fields = [
            ["field" => "cardholder_name", "title" => "CardHolder Name: %1"],
            ["field" => "trunc_card", "title" => "Card Number: %1"],
            ["field" => "payment_method", "title" => "Payment Method: %1"],
            ["field" => "expiration_date", "title" => "Expiration Date: %1"],
            ["field" => "installments", "title" => "Installments: %1"],
            ["field" => "statement_descriptor", "title" => "Statement Descriptor: %1"],
            ["field" => "payment_id", "title" => "Payment id (Cardpay): %1"],
            ["field" => "status", "title" => "Payment Status: %1"],
            ["field" => "status_detail", "title" => "Payment Detail: %1"],
            ["field" => "activation_uri", "title" => "Generate Ticket"],
            ["field" => "payment_id_detail", "title" => "Unlimint Payment Id: %1"],
            ["field" => "id", "title" => "Collection Id: %1"],
        ];

        foreach ($fields as $field) {
            if ($payment->getAdditionalInformation($field['field']) != "") {
                $text = __($field['title'], $payment->getAdditionalInformation($field['field']));
                $info_payments[$field['field']] = [
                    "text" => $text,
                    "value" => $payment->getAdditionalInformation($field['field'])
                ];
            }
        }

        if ($payment->getAdditionalInformation('payer_identification_type') != "") {
            $text = __($payment->getAdditionalInformation('payer_identification_type'));
            $info_payments[$payment->getAdditionalInformation('payer_identification_type')] = [
                "text" => $text . ': ' . $payment->getAdditionalInformation('payer_identification_number')
            ];
        }

        return $info_payments;
    }

    /**
     * Check if status is final in case of multiple card payment
     *
     * @param $status
     *
     * @return string
     */
    protected function validStatusTwoPayments($status)
    {
        $array_status = explode(" | ", $status);
        $status_verif = true;
        $status_final = "";
        foreach ($array_status as $status) {
            if ($status_final == "") {
                $status_final = $status;
            } else {
                if ($status_final != $status) {
                    $status_verif = false;
                }
            }
        }

        if ($status_verif === false) {
            $status_final = "other";
        }

        return $status_final;
    }

    /**
     * Return array message depending on status
     *
     * @param $status
     * @param $status_detail
     * @param $payment_method
     * @param $installment
     * @param $amount
     *
     * @return array
     */
    public function getMessageByStatus($status, $status_detail, $payment_method, $installment, $amount)
    {
        $status = $this->validStatusTwoPayments($status);
        $status_detail = $this->validStatusTwoPayments($status_detail);

        $message = [
            "title" => "",
            "message" => ""
        ];

        $rawMessage = $this->_statusMessage->getMessage($status);
        $message['title'] = __($rawMessage['title']);

        if ($status == 'rejected') {
            if ($status_detail == 'cc_rejected_invalid_installments') {
                $message['message'] = __($this->_statusDetailMessage->getMessage($status_detail), strtoupper($payment_method), $installment);
            } elseif ($status_detail == 'cc_rejected_call_for_authorize') {
                $message['message'] = __($this->_statusDetailMessage->getMessage($status_detail), strtoupper($payment_method), $amount);
            } else {
                $message['message'] = __($this->_statusDetailMessage->getMessage($status_detail), strtoupper($payment_method));
            }
        } else {
            $message['message'] = __($rawMessage['message']);
        }

        return $message;
    }

    /**
     * Return array with info of customer
     *
     * @param $customer
     * @param $order
     *
     * @return array
     */
    protected function getCustomerInfo($customer, $order)
    {
        $email = htmlentities($customer->getEmail());
        if (empty($email)) {
            $email = $order['customer_email'];
        }

        $firstName = htmlentities($customer->getFirstname());
        if (empty($firstName)) {
            $firstName = $order->getBillingAddress()->getFirstname();
        }

        $lastName = htmlentities($customer->getLastname());
        if (empty($lastName)) {
            $lastName = $order->getBillingAddress()->getLastname();
        }

        return ['email' => $email, 'first_name' => $firstName, 'last_name' => $lastName];
    }

    /**
     * Return info about items of order
     *
     * @param $order
     *
     * @return array
     */
    protected function getItemsInfo($order)
    {
        $dataItems = [];

        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getPrice() > 0) {
                $product = $item->getProduct();

                $dataItems[] = [
                    "name" => $product->getName(),
                    "description" => $product->getName(),
                    "count" => (int)number_format($item->getQtyOrdered(), 0, '.', ''),
                    "price" => (float)number_format($item->getPrice(), 2, '.', '')
                ];
            }
        }

        return $dataItems;
    }

    /**
     * Return array with request params data by default to custom method
     *
     * @param array $payment_info
     *
     * @return array
     */
    public function getDefaultRequestParams($paymentInfo = [], $quote = null, $order = null)
    {
        $this->_coreHelper->log("getDefaultRequestParams -> start");

        if (!$quote) {
            $quote = $this->_getQuote();
        }

        $orderId = $quote->getReservedOrderId();
        if (!$order) {
            $order = $this->_getOrder($orderId);
        }

        $orderIncId = $order->getIncrementId();
        $customer = $this->_customerSession->getCustomer();
        $billingAddress = $quote->getBillingAddress();
        $billingAddressData = $billingAddress->getData();
        $customerInfo = $this->getCustomerInfo($customer, $order);
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

        $this->_coreHelper->log("getDefaultRequestParams -> init");

        $requestParams = array();
        $requestParams['request']['id'] = time();
        $requestParams['request']['time'] = date("c");

        $requestParams['merchant_order']['id'] = $order->getIncrementId();
        $requestParams['merchant_order']['description'] = __(
            "Order # %1",
            $order->getIncrementId(),
            $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK)
        );

        $installments = 1;
        if (isset($paymentInfo["installments"])) {
            $installments = (int)$paymentInfo["installments"];
        }

        if ($installments > 1) {
            $requestParams['recurring_data'] = array(
                "installment_type" => "MF_HOLD",
                "amount" => (float)$order->getBaseGrandTotal(),
                "currency" => $currencyCode,
                "initiator" => "cit",
                "interval" => "30",
                "period" => "day",
                "payments" => $installments,
                "trans_type" => "01"           // Goods/Service Purchase
            );
        } else {
            $requestParams['payment_data']['amount'] = $this->getAmount();
            $requestParams['payment_data']['currency'] = $currencyCode;
        }

        $requestParams['merchant_order']['items'] = $this->getItemsInfo($order);

        $requestParams['customer']['id'] = uniqid('', true);
        $requestParams['customer']['email'] = $customerInfo['email'];
        if (isset($paymentInfo['cpf'])) {
            $requestParams['customer']['identity'] = $paymentInfo['cpf'];
        }

        $phone = trim($billingAddressData['telephone']);
        if (strlen($phone) < 8) {
            $phone = str_pad($phone, 8, '0');
        }
        $requestParams['customer']['phone'] = $phone;

        if ($order->canShip()) {
            $shipping = $order->getShippingAddress()->getData();

            $sPhone = trim($shipping['telephone']);
            if (strlen($sPhone) < 8) {
                $sPhone = str_pad($sPhone, 8, '0');
            }

            $requestParams['merchant_order']['shipping_address'] = [
                "addr_line_1" => $shipping['street'],
                "city" => $shipping['city'],
                "country" => $shipping['country_id'],
                "phone" => $sPhone,
                "zip" => $shipping['postcode'],
            ];
        }

        $notificationUrl = $this->_urlBuilder->getUrl('cardpay/redirect/callback', ['_secure' => true]);

        $requestParams['return_urls'] = array(
            "cancel_url" => $notificationUrl . 'action/cancel/orderId/' . $orderIncId,
            "decline_url" => $notificationUrl . 'action/decline/orderId/' . $orderIncId,
            "inprocess_url" => $notificationUrl . 'action/inprocess/orderId/' . $orderIncId,
            "success_url" => $notificationUrl . 'action/success/orderId/' . $orderIncId
        );

        return $requestParams;
    }

    public function getApiInstance()
    {
        if (!$this->terminalCode) {
            $this->terminalCode = $this->_coreHelper->getTerminalCode();
        }

        if (!$this->terminalPassword) {
            $this->terminalPassword = $this->_coreHelper->getTerminalPassword();
        }

        return $this->_coreHelper->getApiInstance($this->terminalCode, $this->terminalPassword);
    }

    /**
     * Return response of api to a preference
     *
     * @param $requestParams
     *
     * @return array
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postPayment($requestParams)
    {
        $url = "/api/payments";
        if (isset($requestParams['recurring_data'])) {
            $url = "/api/installments";
        }

        $api = $this->getApiInstance();

        $response = $api->post($url, $requestParams);

        $this->_coreHelper->log('Core Post Payment Return', 'cardpay', $response);

        return $response;
    }

    /**
     * Return response of api to a preference
     *
     * @param $preference
     *
     * @return array
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postRefund($idPayment)
    {
        $api = $this->getApiInstance();

        $response = $api->performRefund($idPayment);

        $this->_coreHelper->log("Core Cancel Payment Return", 'cardpay', $response);

        return $response;
    }

    /**
     * Get message error by response API
     *
     * @param $response
     *
     * @return string
     */
    public function getMessageError($response)
    {
        $errors = Response::PAYMENT_CREATION_ERRORS;

        $unlimintApiErrorMessage = $this->getUnlimintApiErrorMessage($response, $errors);
        if (!empty($unlimintApiErrorMessage)) {
            return $unlimintApiErrorMessage;
        }

        // set default error
        $messageErrorToClient = $errors['NOT_IDENTIFIED'];
        if (isset($response['response']) &&
            isset($response['response']['cause']) &&
            count($response['response']['cause']) > 0) {

            // get first error
            $cause = $response['response']['cause'][0];

            if (isset($errors[$cause['code']])) {
                $messageErrorToClient = $errors[$cause['code']];
            }
        }

        return $messageErrorToClient;
    }

    private function getUnlimintApiErrorMessage($response, $errors)
    {
        $apiError = null;

        if (!empty($response) && isset($response['response']) && isset($response['response']['message'])) {
            $errorMessage = trim($response['response']['message']);

            $isErrorPrefix = (strpos($errorMessage, self::API_ERROR_MESSAGE_PREFIX) !== false);
            $isErrorPostfix = (strpos($errorMessage, self::API_ERROR_MESSAGE_POSTFIX) !== false);
            if ($isErrorPrefix && $isErrorPostfix) {
                $isPanValid = (strpos($errorMessage, self::PAN_API_FIELD) === false);
                $isExpirationValid = (strpos($errorMessage, self::EXPIRATION_API_FIELD) === false);

                if (!$isPanValid && !$isExpirationValid) {
                    $apiError = __($errors['CARD_NUMBER_AND_EXPIRATION_DATE_ARE_INCORRECT']);
                } else if (!$isPanValid) {
                    $apiError = __($errors['CARD_NUMBER_IS_INCORRECT']);
                } else if (!$isExpirationValid) {
                    $apiError = __($errors['EXPIRATION_DATE_IS_INCORRECT']);
                }
            }
        }

        return $apiError;
    }

    /**
     *  Return info of payment returned by CP api
     *
     * @param $payment_id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentV1($payment_id)
    {
        if (!$this->_accessToken) {
            $this->_accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_TERMINAL_PASSWORD, ScopeInterface::SCOPE_STORE);
        }

        $api = $this->_coreHelper->getApiInstance($this->_accessToken);

        return $api->get("/api/payments/" . $payment_id);
    }

    /**
     * @return mixed|string
     */
    public function getEmailCustomer()
    {
        $customer = $this->_customerSession->getCustomer();
        $email = $customer->getEmail();
        if (empty($email)) {
            $quote = $this->_getQuote();
            $email = $quote->getBillingAddress()->getEmail();
        }

        return $email;
    }

    /**
     * @return float
     */
    public function getAmount($quote = null)
    {
        if (!$quote) {
            $quote = $this->_getQuote();
        }

        return $quote->getBaseGrandTotal();
    }

    /**
     * Check if an applied coupon is valid
     *
     * @param $coupon_id
     * @param $email
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validCoupon($coupon_id, $email = null)
    {
        $transaction_amount = $this->getAmount();
        $payer_email = $this->getEmailCustomer();
        $coupon_code = $coupon_id;

        if ($payer_email == "") {
            $payer_email = $email;
        }

        $api = $this->getApiInstance();

        $details_discount = $api->check_discount_campaigns($transaction_amount, $payer_email, $coupon_code);

        //add value on return api discount
        $details_discount['response']['transaction_amount'] = $transaction_amount;
        $details_discount['response']['params'] = [
            "transaction_amount" => $transaction_amount,
            "payer_email" => $payer_email,
            "coupon_code" => $coupon_code
        ];

        return $details_discount;
    }

    /**
     * Return info of order returned by CP api
     *
     * @param $merchant_order_id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMerchantOrder($merchant_order_id)
    {
        $api = $this->getApiInstance();

        return $api->get("/merchant_orders/" . $merchant_order_id);
    }

    public function getPayment($payment_id)
    {
        $api = $this->getApiInstance();

        return $api->get("/api/payments/" . $payment_id);
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     */
    public function refund(InfoInterface $payment, $amount)
    {
        return $this;
    }
}
