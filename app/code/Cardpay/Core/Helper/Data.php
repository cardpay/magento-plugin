<?php

namespace Cardpay\Core\Helper;

use Cardpay\Core\Exceptions\UnlimitBaseException;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Lib\Api;
use Cardpay\Core\Lib\RestClient;
use Cardpay\Core\Logger\Logger;
use Cardpay\Core\Model\Payment\BankCardPayment;
use Cardpay\Core\Model\Payment\ApayPayment;
use Cardpay\Core\Model\Payment\BoletoPayment;
use Cardpay\Core\Model\Payment\GpayPayment;
use Cardpay\Core\Model\Payment\MbWayPayment;
use Cardpay\Core\Model\Payment\MultibancoPayment;
use Cardpay\Core\Model\Payment\OxxoPayment;
use Cardpay\Core\Model\Payment\PaypalPayment;
use Cardpay\Core\Model\Payment\PixPayment;
use Cardpay\Core\Model\Payment\SepaInstantPayment;
use Cardpay\Core\Model\Payment\SpeiPayment;
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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Monolog\Logger as LoggerAlias;

/**
 * Class Data
 *
 * @package Cardpay\Core\Helper
 */
class Data extends \Magento\Payment\Helper\Data
{
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

    /**
     * payment calculator
     */
    const STATUS_ACTIVE = 'active';
    const PAYMENT_DATA = 'payment_data';

    /**
     * @var MessageInterface
     */
    protected $_messageInterface;

    /**
     * Unlimit Logging instance
     *
     * @var Logger
     */
    protected $_mpLogger;

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

    /**
     * @var TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var ComposerInformation
     */
    protected $_composerInformation;

    /**
     * @var ResourceInterface $moduleResource
     */
    protected $_moduleResource;

    /**
     * @var TimezoneInterface
     */
    protected $_timezoneInterface;

    /**
     * Data constructor.
     * @param Message\MessageInterface $messageInterface
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
    public function __construct( //NOSONAR
        Message\MessageInterface $messageInterface, //NOSONAR
        Context $context, //NOSONAR
        LayoutFactory $layoutFactory, //NOSONAR
        Factory $paymentMethodFactory, //NOSONAR
        Emulation $appEmulation, //NOSONAR
        Config $paymentConfig, //NOSONAR
        Initial $initialConfig, //NOSONAR
        Logger $logger, //NOSONAR
        Collection $statusFactory, //NOSONAR
        OrderFactory $orderFactory, //NOSONAR
        Switcher $switcher, //NOSONAR
        ComposerInformation $composerInformation, //NOSONAR
        ResourceInterface $moduleResource, //NOSONAR
        TimezoneInterface $timezone //NOSONAR
    )
    {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig
        );

        $this->_messageInterface = $messageInterface;
        $this->_mpLogger = $logger;
        $this->_statusFactory = $statusFactory;
        $this->_orderFactory = $orderFactory;
        $this->_switcher = $switcher;
        $this->_composerInformation = $composerInformation;
        $this->_moduleResource = $moduleResource;
        $this->_timezone = $timezone;
    }

    /**
     * Log custom message using Unlimit logger instance
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
        $this->_mpLogger->log(LoggerAlias::DEBUG, $message);
    }

    public function maskSensitiveInfo($string)
    {
        if (empty($string)) {
            return $string;
        }

        // mask PANs
        $stringPansMasked = preg_replace("/(\d{6})\d{0,9}(\d{4})/", '${1}...${2}', $string);

        // mask password
        return preg_replace("/password=.+&/i", 'password=***&', $stringPansMasked);    //NOSONAR
    }

    /**
     * @param Order $order
     * @param string $terminalCode
     * @param string $terminalPassword
     * @return Api
     * @throws UnlimitBaseException
     */
    public function getApiInstance($order = null, $terminalCode = null, $terminalPassword = null)
    {
        $paymentTerminalMapping = [
            BankCardPayment::class => [
                ConfigData::PATH_BANKCARD_TERMINAL_CODE,
                ConfigData::PATH_BANKCARD_TERMINAL_PASSWORD
            ],
            ApayPayment::class => [ConfigData::PATH_APAY_TERMINAL_CODE, ConfigData::PATH_APAY_TERMINAL_PASSWORD],
            BoletoPayment::class => [ConfigData::PATH_BOLETO_TERMINAL_CODE, ConfigData::PATH_BOLETO_TERMINAL_PASSWORD],
            PixPayment::class => [ConfigData::PATH_PIX_TERMINAL_CODE, ConfigData::PATH_PIX_TERMINAL_PASSWORD],
            PaypalPayment::class => [ConfigData::PATH_PAYPAL_TERMINAL_CODE, ConfigData::PATH_PAYPAL_TERMINAL_PASSWORD],
            GpayPayment::class => [ConfigData::PATH_GPAY_TERMINAL_CODE, ConfigData::PATH_GPAY_TERMINAL_PASSWORD],
            SepaInstantPayment::class => [ConfigData::PATH_SEPA_TERMINAL_CODE, ConfigData::PATH_SEPA_TERMINAL_PASSWORD],
            SpeiPayment::class => [ConfigData::PATH_SPEI_TERMINAL_CODE, ConfigData::PATH_SPEI_TERMINAL_PASSWORD],
            MultibancoPayment::class => [
                ConfigData::PATH_MULTIBANCO_TERMINAL_CODE,
                ConfigData::PATH_MULTIBANCO_TERMINAL_PASSWORD
            ],
            MbWayPayment::class => [ConfigData::PATH_MBWAY_TERMINAL_CODE, ConfigData::PATH_MBWAY_TERMINAL_PASSWORD],
            OxxoPayment::class => [ConfigData::PATH_OXXO_TERMINAL_CODE, ConfigData::PATH_OXXO_TERMINAL_PASSWORD],
        ];

        if (!is_null($order) && is_null($terminalCode) && is_null($terminalPassword)) {
            foreach ($paymentTerminalMapping as $paymentClass => $terminalData) {
                if ($paymentClass::isPaymentMethod($order)) {
                    [$terminalCode, $terminalPassword] = $terminalData;
                    break;
                }
            }
        }

        if (is_null($terminalCode) || is_null($terminalPassword)) {
            throw new UnlimitBaseException('Invalid API credentials');
        }

        $api = new Api($terminalCode, $terminalPassword);

        $api->setHelperData($this);
        $api->setPlatform(self::PLATFORM_OPENPLATFORM);
        $api->setType(self::TYPE);

        $api->setHost($this->getApiHost($terminalCode));
        $api->setTerminalCode($this->scopeConfig->getValue($terminalCode, ScopeInterface::SCOPE_STORE));
        $api->setTerminalPassword($this->scopeConfig->getValue($terminalPassword, ScopeInterface::SCOPE_STORE));

        RestClient::setHelperData($this);
        RestClient::setModuleVersion((string)$this->getModuleVersion());
        RestClient::setUrlStore($this->getUrlStore());
        RestClient::setEmailAdmin(
            $this->scopeConfig->getValue(
                'trans_email/ident_sales/email',
                ScopeInterface::SCOPE_STORE
            )
        );
        RestClient::setCountryInitial($this->getCountryInitial());
        RestClient::setSponsorID(
            $this->scopeConfig->getValue(
                'payment/cardpay/sponsor_id',
                ScopeInterface::SCOPE_STORE
            )
        );

        return $api;
    }

    /**
     * Calculate and set order Unlimit specific subtotals based on data values
     *
     * @param $data
     * @param $order
     */
    public function setOrderSubtotals($data, $order)
    {
        if (isset($data['total_paid_amount'])) {
            $paidAmount = $this->_getMultiCardValue($data, 'total_paid_amount');
        } else {
            $paidAmount = $data['transaction_details']['total_paid_amount'];
        }

        $shippingCost = $this->_getMultiCardValue($data, 'shipping_cost');

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
    public function setPayerInfo($payment)
    {
        $this->log('setPayerInfo', ConfigData::CUSTOM_LOG_PREFIX, $payment);

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
        } elseif (isset($payment['card']) && isset($payment['card']['last_four_digits'])) {
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
     * @param $data
     * @param $field
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
            if ('approved' === trim($statusValues[$key])) {
                $finalValue += (float)trim($value);
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
            $locale = explode('_', $locale);

            return $locale[1];
        } catch (UnlimitBaseException $e) {
            return 'US';
        }
    }

    public function getUrlStore()
    {
        try {
            $objectManager = ObjectManager::getInstance();
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $currentStore = $storeManager->getStore();

            return $currentStore->getBaseUrl();
        } catch (UnlimitBaseException $e) {
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

        if (isset($additionalInfo[self::PAYMENT_DATA]['id'])) {
            return $additionalInfo[self::PAYMENT_DATA]['id'];
        }

        return null;
    }

    /**
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
        $requestParams['merchant_order']['description'] = "APIREFUND - " . __(
                "Refund Order # %1",
                $order->getIncrementId(),
                $this->getStoreManager()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK)
            );

        $requestParams[self::PAYMENT_DATA]['id'] = $paymentId;

        $requestParams['customer']['email'] = $order->getCustomerEmail();

        $requestParams['refund_data']['amount'] = $amountToRefund;
        $requestParams['refund_data']['currency'] = $order->getOrderCurrencyCode();

        return $requestParams;
    }

    /**
     * @return TimezoneInterface
     */
    public function getTimezone()
    {
        return $this->_timezoneInterface;
    }

    public function getStoreManager()
    {
        $objectManager = ObjectManager::getInstance();

        return $objectManager->get('Magento\Store\Model\StoreManagerInterface');
    }

    /**
     * @throws UnlimitBaseException
     */
    public function getApiHost($terminalCode)
    {
        if (ConfigData::PATH_BANKCARD_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_BANKCARD_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_APAY_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_APAY_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_BOLETO_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_BOLETO_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_PIX_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_PIX_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_PAYPAL_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_PAYPAL_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_GPAY_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_GPAY_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_SEPA_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_SEPA_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_SPEI_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_SPEI_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_MULTIBANCO_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_MULTIBANCO_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_MBWAY_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                    ConfigData::PATH_MBWAY_SANDBOX,
                    ScopeInterface::SCOPE_STORE
                ));
        } elseif (ConfigData::PATH_OXXO_TERMINAL_CODE === $terminalCode) {
            $isSandbox = (1 === (int)$this->scopeConfig->getValue(
                ConfigData::PATH_OXXO_SANDBOX,
                ScopeInterface::SCOPE_STORE
            ));
        } else {
            throw new UnlimitBaseException('Unable to get API host');
        }

        if ($isSandbox) {
            return self::SANDBOX_URL;
        }

        return self::PRODUCTION_URL;
    }
}
