<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Block\Adminhtml\System\Config\Version;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as dataHelper;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Lib\Api;
use Cardpay\Core\Model\Api\V1\Exception;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
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
    const PAYMENT_DATA = 'payment_data';

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
    protected $_canUseInternal;

    /**
     * {@inheritdoc}
     */
    protected $_canUseCheckout;

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
     * @var dataHelper
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
     * @param Version $version
     * @param ProductMetadataInterface $productMetadata
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
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Retrieves Order
     *
     * @param integer $incrementId
     *
     * @return Order
     */
    public function getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Return array with data of payment of order loaded with order_id param
     *
     * @param $orderId
     *
     * @return array
     */
    public function getInfoPaymentByOrder($orderId)
    {
        $order = $this->getOrder($orderId);
        $payment = $order->getPayment();
        $info_payments = [];
        $fields = [
            ['field' => 'cardholder_name', 'title' => 'CardHolder Name: %1'],
            ['field' => 'trunc_card', 'title' => 'Card Number: %1'],
            ['field' => 'payment_method', 'title' => 'Payment Method: %1'],
            ['field' => 'expiration_date', 'title' => 'Expiration Date: %1'],
            ['field' => 'installments', 'title' => 'Installments: %1'],
            ['field' => 'statement_descriptor', 'title' => 'Statement Descriptor: %1'],
            ['field' => 'payment_id', 'title' => 'Payment id (Cardpay): %1'],
            ['field' => 'status', 'title' => 'Payment Status: %1'],
            ['field' => 'status_detail', 'title' => 'Payment Detail: %1'],
            ['field' => 'activation_uri', 'title' => 'Generate Ticket'],
            ['field' => 'payment_id_detail', 'title' => 'Unlimint Payment Id: %1'],
            ['field' => 'id', 'title' => 'Collection Id: %1'],
        ];

        foreach ($fields as $field) {
            $additionalInformation = $payment->getAdditionalInformation($field['field']);
            if (!empty($additionalInformation)) {
                $text = __($field['title'], $additionalInformation);
                $info_payments[$field['field']] = [
                    'text' => $text,
                    'value' => $additionalInformation
                ];
            }
        }

        $idType = $payment->getAdditionalInformation('payer_identification_type');
        if (!empty($idType)) {
            $text = __($idType);
            $info_payments[$idType] = [
                'text' => $text . ': ' . $payment->getAdditionalInformation('payer_identification_number')
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
        $arrayStatus = explode(' | ', $status);
        $isStatusVerified = true;
        $finalStatus = '';
        foreach ($arrayStatus as $statusExploded) {
            if (empty($finalStatus)) {
                $finalStatus = $statusExploded;
            } elseif ((string)$finalStatus !== (string)$statusExploded) {
                $isStatusVerified = false;
            }
        }

        if ($isStatusVerified === false) {
            $finalStatus = 'other';
        }

        return $finalStatus;
    }

    /**
     * Return array message depending on status
     *
     * @param $status
     * @param $statusDetail
     * @param $payment_method
     * @param $installment
     * @param $amount
     *
     * @return array
     */
    public function getMessageByStatus($status, $statusDetail, $payment_method, $installment, $amount)
    {
        $status = $this->validStatusTwoPayments($status);
        $statusDetail = $this->validStatusTwoPayments($statusDetail);

        $message = ['title' => '', 'message' => ''];

        $rawMessage = $this->_statusMessage->getMessage($status);
        $message['title'] = __($rawMessage['title']);

        if ($status === 'rejected') {
            if ((string)$statusDetail === 'cc_rejected_invalid_installments') {
                $message['message'] = __($this->_statusDetailMessage->getMessage($statusDetail), strtoupper($payment_method), $installment);
            } elseif ((string)$statusDetail === 'cc_rejected_call_for_authorize') {
                $message['message'] = __($this->_statusDetailMessage->getMessage($statusDetail), strtoupper($payment_method), $amount);
            } else {
                $message['message'] = __($this->_statusDetailMessage->getMessage($statusDetail), strtoupper($payment_method));
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
        $email = htmlentities($customer->getEmail() ?? '');
        if (empty($email)) {
            $email = $order['customer_email'];
        }

        $firstName = htmlentities($customer->getFirstname() ?? '');
        if (empty($firstName)) {
            $firstName = $order->getBillingAddress()->getFirstname();
        }

        $lastName = htmlentities($customer->getLastname() ?? '');
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
                    'name' => $product->getName(),
                    'description' => $product->getName(),
                    'count' => (int)number_format($item->getQtyOrdered(), 0, '.', ''),
                    'price' => (float)number_format($item->getPrice(), 2, '.', '')
                ];
            }
        }

        return $dataItems;
    }

    /**
     * Return array with request params data by default to custom method
     *
     * @param array $paymentInfo
     * @param null $quote
     * @param null $order
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getDefaultRequestParams($paymentInfo = [], $quote = null, $order = null, $requestParams = [])
    {
        $this->_coreHelper->log('getDefaultRequestParams -> start');

        if (!$quote) {
            $quote = $this->getQuote();
        }

        $orderId = $quote->getReservedOrderId();
        if (!$order) {
            $order = $this->getOrder($orderId);
        }

        $orderIncId = $order->getIncrementId();
        $customer = $this->_customerSession->getCustomer();
        $customerInfo = $this->getCustomerInfo($customer, $order);
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

        $this->_coreHelper->log('getDefaultRequestParams -> init');

        $requestParams['request']['id'] = time();
        $requestParams['request']['time'] = date('c');

        $requestParams['merchant_order']['id'] = $order->getIncrementId();
        $requestParams['merchant_order']['description'] = __(
            'Order # %1',
            $order->getIncrementId(),
            $this->_storeManager->getStore()->getBaseUrl()
        );

        $requestParams = $this->assignRequestData($paymentInfo, $currencyCode, $requestParams);

        $requestParams['merchant_order']['items'] = $this->getItemsInfo($order);

        $requestParams['customer']['id'] = uniqid('', true);
        $requestParams['customer']['email'] = $customerInfo['email'];

        if (isset($paymentInfo['cpf'])) {
            $requestParams['customer']['identity'] = $paymentInfo['cpf'];
        }

        $requestParams['payment_method'] = $paymentInfo['payment_method'];

        $billingAddress = $quote->getBillingAddress();
        $billingAddressData = $billingAddress->getData();
        if (!empty($billingAddressData['telephone'])) {
            $billingPhone = trim($billingAddressData['telephone']);
            if (strlen($billingPhone) < 5) {
                $billingPhone = str_pad($billingPhone, 5, '0');
            }

            $requestParams['customer']['phone'] = $billingPhone;
        }

        if ($order->canShip() && !is_null($order->getShippingAddress())) {
            $shipping = $order->getShippingAddress()->getData();
            $zipcode = $shipping['postcode'];
            if (isset($paymentInfo['zip'])) {
                $zipcode = $paymentInfo['zip'];
            }

            $shippingPhone = trim($shipping['telephone']);
            if (strlen($shippingPhone) < 5) {
                $shippingPhone = str_pad($shippingPhone, 5, '0');
            }

            $requestParams['merchant_order']['shipping_address'] = [
                'addr_line_1' => $shipping['street'],
                'city' => $shipping['city'],
                'country' => $shipping['country_id'],
                'phone' => $shippingPhone,
                'zip' => $zipcode,
            ];
        } elseif (isset($paymentInfo['zip'])) {
            $requestParams['merchant_order']['shipping_address']['zip'] = $paymentInfo['zip'];
        }

        $notificationUrl = $this->_urlBuilder->getUrl('cardpay/redirect/callback', ['_secure' => true]);

        $requestParams['return_urls'] = [
            'cancel_url' => $notificationUrl . 'action/cancel/orderId/' . $orderIncId,
            'decline_url' => $notificationUrl . 'action/decline/orderId/' . $orderIncId,
            'inprocess_url' => $notificationUrl . 'action/inprocess/orderId/' . $orderIncId,
            'success_url' => $notificationUrl . 'action/success/orderId/' . $orderIncId
        ];

        return $requestParams;
    }

    /**
     * @param Order $order
     *
     * @throws \Exception
     */
    public function getApiInstance($order = null)
    {
        if (is_null($order)) {
            $orderId = $this->getQuote()->getReservedOrderId();
            $order = $this->getOrder($orderId);
        }

        return $this->_coreHelper->getApiInstance($order);
    }

    /**
     * Return response of API to a preference
     *
     * @param $requestParams
     *
     * @return array
     * @throws Exception
     * @throws LocalizedException|\Exception
     */
    public function postPayment($requestParams, $order = null)
    {
        $url = "/api/payments";

        /**
         * @var Api
         */
        $api = $this->getApiInstance($order);
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
     * @throws Exception
     * @throws LocalizedException|\Exception
     */
    public function postRefund($idPayment)
    {
        /**
         * @var Api
         */
        $api = $this->getApiInstance();

        $response = $api->refund($idPayment);

        $this->_coreHelper->log('Core Cancel Payment Return', 'cardpay', $response);

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
        if ($unlimintApiErrorMessage !== null) {
            return $unlimintApiErrorMessage;
        }

        // set default error
        $messageErrorToClient = $errors['NOT_IDENTIFIED'];
        if (isset($response['response']['cause']) && count($response['response']['cause']) > 0) {

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
                } elseif (!$isPanValid) {
                    $apiError = __($errors['CARD_NUMBER_IS_INCORRECT']);
                } elseif (!$isExpirationValid) {
                    $apiError = __($errors['EXPIRATION_DATE_IS_INCORRECT']);
                }
            }
        }

        return $apiError;
    }

    /**
     *  Return info of payment returned by CP api
     *
     * @param $order
     * @param $paymentId
     *
     * @return array
     * @throws \Exception
     */
    public function getApiPayment($order, $paymentId)
    {
        $api = $this->_coreHelper->getApiInstance($order);
        return $api->get('/api/payments/' . $paymentId);
    }

    /**
     * @return mixed|string
     */
    public function getEmailCustomer()
    {
        $customer = $this->_customerSession->getCustomer();
        $email = $customer->getEmail();
        if (empty($email)) {
            $quote = $this->getQuote();
            $email = $quote->getBillingAddress()->getEmail();
        }

        return $email;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->getQuote()->getBaseGrandTotal();
    }

    /**
     * Return info of order returned by CP api
     *
     * @param $merchantOrderId
     *
     * @return array
     * @throws LocalizedException|\Exception
     */
    public function getMerchantOrder($merchantOrderId)
    {
        $api = $this->getApiInstance();

        return $api->get('/merchant_orders/' . $merchantOrderId);
    }

    public function getPayment($paymentId)
    {
        $api = $this->getApiInstance();

        return $api->get('/api/payments/' . $paymentId);
    }

    /**
     * Refund specified amount for payment
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @api
     */
    public function refund(InfoInterface $payment, $amount)
    {
        return $this;
    }

    private function assignRequestData($paymentInfo, $currencyCode, $requestParams)
    {
        if (isset($paymentInfo['payment_method']) && $paymentInfo['payment_method'] === ConfigData::BANK_CARD_API_PAYMENT_METHOD) {

            $areInstallmentsEnabled = (1 === (int)($this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_INSTALLMENT, ScopeInterface::SCOPE_STORE)));
            if ($areInstallmentsEnabled) {
                $numberOfInstallments = (int)$paymentInfo['installments'];

                $requestParams[self::PAYMENT_DATA] = [
                    'installment_type' => $this->_scopeConfig->getValue(ConfigData::PATH_BANKCARD_INSTALLMENT_TYPE),
                    'installments' => $numberOfInstallments,
                ];

            }
        }

        $requestParams[self::PAYMENT_DATA]['amount'] = $this->getAmount();
        $requestParams[self::PAYMENT_DATA]['currency'] = $currencyCode;

        $dynamicDescriptor = trim($this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_DESCRIPTOR, ScopeInterface::SCOPE_STORE));
        if (!empty($dynamicDescriptor)) {
            $requestParams[self::PAYMENT_DATA]['dynamic_descriptor'] = $dynamicDescriptor;
        }

        return $requestParams;
    }
}
