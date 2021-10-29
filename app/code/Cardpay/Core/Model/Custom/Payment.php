<?php

namespace Cardpay\Core\Model\Custom;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Response;
use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Cc;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Payment
 *
 * @package Cardpay\Core\Model\Custom
 */
class Payment extends Cc implements GatewayInterface
{
    /**
     * Define payment method code
     */
    const CODE = 'cardpay_custom';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true;

    protected $_isOffline = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canSaveCc = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isProxy = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canReviewPayment = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var \Cardpay\Core\Model\Core
     */
    protected $_coreModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Cardpay\Core\Helper\Data
     */
    protected $_helperData;

    /**
     *
     */
    const LOG_NAME = 'custom_payment';

    /**
     * @var string
     */
    protected $_accessToken;

    /**
     * @var string
     */
    protected $_publicKey;

    /**
     * @var array
     */
    public static $_excludeInputsOpc = ['issuer_id', 'card_expiration_month', 'card_expiration_year', 'card_holder_name', 'doc_type', 'doc_number'];

    /**
     * @var string
     */
    protected $_infoBlockType = 'Cardpay\Core\Block\Info';

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @param \Cardpay\Core\Helper\Data $helperData
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Cardpay\Core\Model\Core $coreModel
     */
    public function __construct(
        \Cardpay\Core\Helper\Data                            $helperData,
        \Magento\Checkout\Model\Session                      $checkoutSession,
        \Magento\Customer\Model\Session                      $customerSession,
        \Magento\Sales\Model\OrderFactory                    $orderFactory,
        \Magento\Framework\UrlInterface                      $urlBuilder,
        \Magento\Framework\Model\Context                     $context,
        \Magento\Framework\Registry                          $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory    $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory         $customAttributeFactory,
        \Magento\Payment\Helper\Data                         $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface   $scopeConfig,
        \Magento\Payment\Model\Method\Logger                 $logger,
        \Magento\Framework\Module\ModuleListInterface        $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Cardpay\Core\Model\Core                             $coreModel,
        \Magento\Framework\App\RequestInterface              $request)
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate
        );

        $this->_helperData = $helperData;
        $this->_coreModel = $coreModel;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_urlBuilder = $urlBuilder;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * {inheritdoc}
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        return '';
    }

    /**
     * @param DataObject $data
     * @return $this|Cc
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

            if (isset($additionalData['one_click_pay']) && $additionalData['one_click_pay'] == 1) {
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

            if (!empty($additionalData['card_expiration_month']) && !empty($additionalData['card_expiration_year'])) {
                $info->setAdditionalInformation(
                    'expiration_date',
                    str_pad($additionalData['card_expiration_month'],
                        2,
                        '0',
                        STR_PAD_LEFT) . "/" . $additionalData['card_expiration_year']
                );
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
     * @return $this|bool|Cc
     * @throws LocalizedException
     * @throws \Cardpay\Core\Model\Api\V1\Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        $requestParams = $this->createApiRequest();
        $this->maskCardData();
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

            $requestParams['payment_method'] = 'BANKCARD';

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
                if (isset($requestParams['recurring_data'])) {
                    $requestParams['recurring_data']['preauth'] = 'true';    // 2 phase installment
                } else {
                    $requestParams['payment_data']['preauth'] = 'true';      // 2 phase payment
                }
            }

            return $requestParams;

        } catch (Exception $e) {
            $this->_helperData->log('CustomPayment::initialize - There was an error retrieving the information to create the payment, more details: ' . $e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }
    }

    public function maskCardData()
    {
        $order = $this->getInfoInstance()->getOrder();
        $payment = $order->getPayment();

        $cardNumberMasked = preg_replace("/(\d{6})\d{0,9}(\d{4})/i", '${1}...${2}', $payment->getAdditionalInformation('card_number'));
        $payment->setAdditionalInformation('card_number', $cardNumberMasked);

        $payment->setAdditionalInformation('security_code', '...');
    }

    /**
     * @param $requestParams
     * @return bool
     * @throws LocalizedException
     */
    public function makeApiPayment($requestParams)
    {
        $response = $this->_coreModel->postPayment($requestParams);
        if (isset($response['status']) && ($response['status'] == 200 || $response['status'] == 201)) {
            $this->getInfoInstance()->setAdditionalInformation('paymentResponse', $response['response']);
            return true;
        }

        $messageErrorToClient = $this->_coreModel->getMessageError($response);
        $arrayLog = [
            'response' => $response,
            'message' => $messageErrorToClient
        ];

        $this->_helperData->log('CustomPayment::initialize - The API returned an error while creating the payment, more details: ' . json_encode($arrayLog));
        throw new LocalizedException(__($messageErrorToClient));
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function validate()
    {
        AbstractMethod::validate();

        return $this;
    }

    /**
     * Retrieves quote
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
     * @param $incrementId
     *
     * @return mixed
     */
    protected function _getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Return success page url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $url = 'cardpay/checkout/page';
        return $this->_urlBuilder->getUrl($url, ['_secure' => true]);
    }

    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (empty($isActive)) {
            return false;
        }

        $secure = $this->_request->isSecure();
        $sandbox = $this->_scopeConfig->getValue(ConfigData::PATH_SANDBOX, ScopeInterface::SCOPE_STORE);

        if (!$sandbox && !$secure) {
            $this->_helperData->log("CustomPayment::isAvailable - Module not available because it has production credentials in non HTTPS environment.");
            return false;
        }

        return $this->isPaymentMethodAvailable();
    }

    public function isPaymentMethodAvailable()
    {
        $terminalCode = $this->_scopeConfig->getValue(ConfigData::PATH_TERMINAL_CODE, ScopeInterface::SCOPE_STORE);
        if (empty($terminalCode)) {
            $this->_helperData->log("CustomPayment::isAvailable - Module not available because terminal code has not been configured.");
            return false;
        }

        $terminalPassword = $this->_scopeConfig->getValue(ConfigData::PATH_TERMINAL_PASSWORD, ScopeInterface::SCOPE_STORE);
        if (empty($terminalPassword)) {
            $this->_helperData->log("CustomPayment::isAvailable - Module not available because terminal password has not been configured.");
            return false;
        }

        return true;
    }

    /**
     * @param $info
     *
     * @return mixed
     */
    protected function cleanFieldsOcp($info)
    {
        foreach (self::$_excludeInputsOpc as $field) {
            $info[$field] = '';
        }

        return $info;
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
     * Refund specified amount for payment
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     * @api
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        }

        $this->_coreModel->refund($payment, $amount);

        return $this;
    }

    /**
     * Void specified amount for payment
     *
     * @param DataObject|InfoInterface $payment
     * @return $this
     * @throws LocalizedException
     * @api
     */
    public function void(InfoInterface $payment)
    {
        if (!$this->canVoid()) {
            throw new LocalizedException(__('The void action is not available.'));
        }

        $info = $payment->getAdditionalInformation();

        $paymentId = $this->_helperData->getPaymentId($info);

        $this->_coreModel->postRefund($paymentId);

        return $this;
    }
}