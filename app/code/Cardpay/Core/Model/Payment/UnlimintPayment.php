<?php

namespace Cardpay\Core\Model\Payment;

use Cardpay\Core\Helper\ConfigData;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Cc;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;

class UnlimintPayment extends Cc implements GatewayInterface
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
     * Can payment method be used on checkout
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
     * @var RequestInterface
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
        RequestInterface                                     $request)
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

    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        return '';
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
     * @return Quote
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
        $isActive = (int)$this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (0 === $isActive) {
            return false;
        }

        $secure = $this->_request->isSecure();
        $sandbox = $this->_scopeConfig->getValue(ConfigData::PATH_BANKCARD_SANDBOX, ScopeInterface::SCOPE_STORE);

        if (!$sandbox && !$secure) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because it has production credentials in non HTTPS environment.');
            return false;
        }

        return $this->isPaymentMethodAvailable(ConfigData::PATH_BANKCARD_TERMINAL_CODE, ConfigData::PATH_BANKCARD_TERMINAL_PASSWORD);
    }

    public function isPaymentMethodAvailable($terminalCodeConfigParam, $terminalPasswordConfigParam)
    {
        $terminalCode = $this->_scopeConfig->getValue($terminalCodeConfigParam, ScopeInterface::SCOPE_STORE);
        if (empty(trim($terminalCode))) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because terminal code has not been configured.');
            return false;
        }

        $terminalPassword = $this->_scopeConfig->getValue($terminalPasswordConfigParam, ScopeInterface::SCOPE_STORE);
        if (empty(trim($terminalPassword))) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because terminal password has not been configured.');
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