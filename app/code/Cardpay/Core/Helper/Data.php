<?php

namespace Cardpay\Core\Helper;

use Cardpay\Core\Lib\Api;
use Cardpay\Core\Lib\RestClient;
use Cardpay\Core\Logger\Logger;
use Exception;
use Magento\Backend\Block\Store\Switcher;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 *
 * @package Cardpay\Core\Helper
 */
class Data extends \Magento\Payment\Helper\Data
{
    /**
     *sandbox url
     */
    const SANDBOX_URL = 'https://sandbox.cardpay.com';
    const PRODUCTION_URL = 'https://cardpay.com';

    /**
     *api platform openplatform
     */
    const PLATFORM_OPENPLATFORM = 'openplatform';

    /**
     *api platform stdplatform
     */
    const PLATFORM_STD = 'std';

    /**
     *type
     */
    const TYPE = 'magento';
    //end const platform

    /**
     * payment calculator
     */
    const STATUS_ACTIVE = 'active';
    const PAYMENT_TYPE_CREDIT_CARD = 'credit_card';

    /**
     * @var \Cardpay\Core\Helper\Message\MessageInterface
     */
    protected $_messageInterface;

    /**
     * Unlimint Logging instance
     *
     * @var Logger
     */
    protected $_mpLogger;

    /**
     * @var Cache
     */
    protected $_mpCache;

    /**
     * @var Collection
     */
    protected $_statusFactory;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Switcher
     */
    protected $_switcher;
    protected $_composerInformation;

    /**
     * @var ResourceInterface $moduleResource
     */
    protected $_moduleResource;

    protected $_timezoneInterface;

    /**
     * Data constructor.
     * @param Message\MessageInterface $messageInterface
     * @param Cache $cpCache
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param Logger $logger
     * @param Collection $statusFactory
     * @param OrderFactory $orderFactory
     * @param Switcher $switcher
     * @param ComposerInformation $composerInformation
     * @param ResourceInterface $moduleResource
     */
    public function __construct(
        Message\MessageInterface $messageInterface,
        Cache                    $cpCache,
        Context                  $context,
        LayoutFactory            $layoutFactory,
        Factory                  $paymentMethodFactory,
        Emulation                $appEmulation,
        Config                   $paymentConfig,
        Initial                  $initialConfig,
        Logger                   $logger,
        Collection               $statusFactory,
        OrderFactory             $orderFactory,
        Switcher                 $switcher,
        ComposerInformation      $composerInformation,
        ResourceInterface        $moduleResource,
        TimezoneInterface        $timezone
    )
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_messageInterface = $messageInterface;
        $this->_mpLogger = $logger;
        $this->_mpCache = $cpCache;
        $this->_statusFactory = $statusFactory;
        $this->_orderFactory = $orderFactory;
        $this->_switcher = $switcher;
        $this->_composerInformation = $composerInformation;
        $this->_moduleResource = $moduleResource;
        $this->_timezone = $timezone;
    }

    /**
     * Log custom message using Unlimint logger instance
     *
     * @param        $message
     * @param string $name
     * @param null $extraDataForLog
     */
    public function log($message, $name = 'cardpay', $extraDataForLog = null)
    {
        $isLogEnabled = $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE);
        if (!$isLogEnabled) {
            return;
        }

        if (!is_object($extraDataForLog) && !empty($extraDataForLog)) {
            $message .= ' - ' . $this->maskSensitiveInfo(print_r($extraDataForLog, true));
        }

        $this->_mpLogger->setName($name);
        $this->_mpLogger->debug($message);
    }

    public function maskSensitiveInfo($string)
    {
        if (empty($string)) {
            return $string;
        }

        // mask PANs
        $stringPansMasked = preg_replace("/(\d{6})\d{0,9}(\d{4})/i", '${1}...${2}', $string);

        // mask password
        return preg_replace("/password=.+&/i", 'password=***&', $stringPansMasked);
    }

    /**
     * @param null $accessToken
     * @return Api
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApiInstance($terminalCode = null, $terminalPassword = null)
    {
        if (is_null($terminalCode)) {
            $terminalCode = $this->getTerminalCode();
        }

        if (is_null($terminalPassword)) {
            $terminalPassword = $this->getTerminalPassword();
        }

        $api = new Api($terminalCode, $terminalPassword);

        $api->setHelperData($this);
        $api->set_platform(self::PLATFORM_OPENPLATFORM);
        $api->set_type(self::TYPE);

        $api->set_host($this->getHost());
        $api->setTerminalCode($this->scopeConfig->getValue(ConfigData::PATH_TERMINAL_CODE, ScopeInterface::SCOPE_STORE));
        $api->setTerminalPassword($this->scopeConfig->getValue(ConfigData::PATH_TERMINAL_PASSWORD, ScopeInterface::SCOPE_STORE));

        RestClient::setHelperData($this);
        RestClient::setModuleVersion((string)$this->getModuleVersion());
        RestClient::setUrlStore($this->getUrlStore());
        RestClient::setEmailAdmin($this->scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE));
        RestClient::setCountryInitial($this->getCountryInitial());
        RestClient::setSponsorID($this->scopeConfig->getValue('payment/cardpay/sponsor_id', ScopeInterface::SCOPE_STORE));

        return $api;
    }

    public function getTerminalCode($scopeCode = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(ConfigData::PATH_TERMINAL_CODE, $scopeCode);
    }

    public function getTerminalPassword($scopeCode = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(ConfigData::PATH_TERMINAL_PASSWORD, $scopeCode);
    }

    public function getCallbackSecret($scopeCode = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(ConfigData::PATH_CALLBACK_SECRET, $scopeCode);
    }

    public function getHost($scopeCode = ScopeInterface::SCOPE_STORE)
    {
        $sandbox = $this->scopeConfig->getValue(ConfigData::PATH_SANDBOX, $scopeCode);

        if (!$sandbox) {
            return self::PRODUCTION_URL;
        } else {
            return self::SANDBOX_URL;
        }
    }

    /**
     * Calculate and set order Unlimint specific subtotals based on data values
     *
     * @param $data
     * @param $order
     */
    public function setOrderSubtotals($data, $order)
    {
        $couponAmount = $this->_getMultiCardValue($data, 'coupon_amount');

        if (isset($data['total_paid_amount'])) {
            $paidAmount = $this->_getMultiCardValue($data, 'total_paid_amount');
        } else {
            $paidAmount = $data['transaction_details']['total_paid_amount'];
        }

        $shippingCost = $this->_getMultiCardValue($data, 'shipping_cost');

        if ($couponAmount
            && $this->scopeConfig->isSetFlag(ConfigData::PATH_ADVANCED_CONSIDER_DISCOUNT, ScopeInterface::SCOPE_STORE)
        ) {
            $order->setDiscountCouponAmount($couponAmount * -1);
            $order->setBaseDiscountCouponAmount($couponAmount * -1);
        } else {
            //if a discount was applied and should not be considered
            $paidAmount += $couponAmount;
        }

        if ($shippingCost > 0) {
            $order->setBaseShippingAmount($shippingCost);
            $order->setShippingAmount($shippingCost);
        }

        $order->setTotalPaid($paidAmount);
        $order->save();
    }

    /**
     * Modify payment array adding specific fields
     *
     * @param $payment
     *
     * @return mixed
     */
    public function setPayerInfo(&$payment)
    {
        $this->log("setPayerInfo", ConfigData::CUSTOM_LOG_PREFIX, $payment);

        if ($payment['payment_method_id']) {
            $payment["payment_method"] = $payment['payment_method_id'];
        }

        if ($payment['installments']) {
            $payment["installments"] = $payment['installments'];
        }
        if ($payment['id']) {
            $payment["payment_id_detail"] = $payment['id'];
        }
        if (isset($payment['trunc_card'])) {
            $payment["trunc_card"] = $payment['trunc_card'];
        } else if (isset($payment['card']) && isset($payment['card']['last_four_digits'])) {
            $payment["trunc_card"] = "xxxx xxxx xxxx " . $payment['card']["last_four_digits"];
        }

        if (isset($payment['card']["cardholder"]["name"])) {
            $payment["cardholder_name"] = $payment['card']["cardholder"]["name"];
        }

        if (isset($payment['payer']['first_name'])) {
            $payment['payer_first_name'] = $payment['payer']['first_name'];
        }

        if (isset($payment['payer']['last_name'])) {
            $payment['payer_last_name'] = $payment['payer']['last_name'];
        }

        if (isset($payment['payer']['email'])) {
            $payment['payer_email'] = $payment['payer']['email'];
        }

        return $payment;
    }

    /**
     * Return sum of fields separated with |
     *
     * @param $fullValue
     *
     * @return int
     */
    protected function _getMultiCardValue($data, $field)
    {
        $finalValue = 0;
        if (!isset($data[$field])) {
            return $finalValue;
        }
        $amountValues = explode('|', $data[$field]);
        $statusValues = explode('|', $data['status']);
        foreach ($amountValues as $key => $value) {
            $value = (float)str_replace(' ', '', $value);
            if (str_replace(' ', '', $statusValues[$key]) == 'approved') {
                $finalValue = $finalValue + $value;
            }
        }

        return $finalValue;
    }

    public function getCountryInitial()
    {
        try {
            $objectManager = ObjectManager::getInstance();
            $store = $objectManager->get('Magento\Framework\Locale\Resolver');
            $locale = $store->getLocale();
            $locale = explode("_", $locale);
            $locale = $locale[1];

            return $locale;

        } catch (Exception $e) {
            return "US";
        }
    }

    public function getUrlStore()
    {
        try {
            $objectManager = ObjectManager::getInstance();
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $currentStore = $storeManager->getStore();

            return $currentStore->getBaseUrl();
        } catch (Exception $e) {
            return "";
        }
    }

    public function getModuleVersion()
    {
        return $this->_moduleResource->getDbVersion('Cardpay_Core');
    }

    /**
     * Summary: Get client id from access token.
     * Description: Get client id from access token.
     *
     * @param String $at
     *
     * @return String client id.
     */
    public static function getClientIdFromAccessToken($at)
    {
        $t = explode('-', $at);
        if (!empty($t)) {
            return $t[1];
        }

        return '';
    }

    /**
     * @param $additionalInfo
     * @return string|null
     */
    public function getPaymentId($additionalInfo)
    {
        if (isset($additionalInfo['paymentResponse'])) {
            $additionalInfo = $additionalInfo['paymentResponse'];
        }

        $type = isset($additionalInfo['payment_data']) ? 'payment_data' : 'recurring_data';

        if (isset($additionalInfo[$type]) && isset($additionalInfo[$type]['id'])) {
            return $additionalInfo[$type]['id'];
        }

        return null;
    }

    public function getTimezone()
    {
        return ($this->_timezoneInterface);
    }

    /**
     * @param string $dateTime
     * @return string $dateTime as time zone
     */
    public function getTimeAccordingToTimeZone()
    {
        // for get current time according to time zone
        return $this->_timezoneInterface->date()->format('y-m-dTH:m:iZ');
    }

    /**
     * Return array with request params by default to refund method
     *
     * @param int $paymentId
     * @param $order
     * @param $amountToRefund
     *
     * @return array
     */
    public function getRefundRequestParams($paymentId, $order, $amountToRefund)
    {
        $requestParams = [];

        $requestParams['request']['id'] = time();
        $requestParams['request']['time'] = date('c');

        $requestParams['merchant_order']['id'] = $order->getIncrementId();
        $requestParams['merchant_order']['description'] = __(
            "Refund Order # %1",
            $order->getIncrementId(),
            $this->getStoreManager()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK)
        );

        $requestParams['payment_data']['id'] = $paymentId;

        $requestParams['customer']['email'] = $order->getCustomerEmail();

        $requestParams['refund_data']['amount'] = $amountToRefund;
        $requestParams['refund_data']['currency'] = $order->getOrderCurrencyCode();

        return $requestParams;
    }

    public function getStoreManager()
    {
        $objectManager = ObjectManager::getInstance();

        return $objectManager->get('Magento\Store\Model\StoreManagerInterface');
    }
}