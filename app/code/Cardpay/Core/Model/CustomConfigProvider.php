<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Return configs to Standard Method
 *
 * Class StandardConfigProvider
 *
 * @package Cardpay\Core\Model
 */
class CustomConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = Custom\Payment::CODE;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var Repository
     */
    protected $_assetRepo;

    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetaData;

    protected $_composerInformation;

    protected $_coreHelper;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context                  $context,
        PaymentHelper            $paymentHelper,
        ScopeConfigInterface     $scopeConfig,
        Session                  $checkoutSession,
        StoreManagerInterface    $storeManager,
        Repository               $assetRepo,
        ProductMetadataInterface $productMetadata,
        Data                     $coreHelper
    )
    {
        $this->_request = $context->getRequest();
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
        $this->_context = $context;
        $this->_productMetaData = $productMetadata;
        $this->_coreHelper = $coreHelper;
    }

    /**
     * Gather information to be sent to javascript method renderer
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->methodInstance->isAvailable()) {
            return [];
        }

        return [
            'payment' => [
                $this->methodCode => [
                    'bannerUrl' => $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_BANNER, ScopeInterface::SCOPE_STORE),
                    'country' => strtoupper($this->_scopeConfig->getValue(ConfigData::PATH_SITE_ID, ScopeInterface::SCOPE_STORE)),
                    'terminal_code' => $this->_scopeConfig->getValue(ConfigData::PATH_TERMINAL_CODE, ScopeInterface::SCOPE_STORE),
                    'logEnabled' => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE),
                    'discount_coupon' => $this->_scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_COUPON, ScopeInterface::SCOPE_STORE),
                    'grand_total' => $this->_checkoutSession->getQuote()->getGrandTotal(),
                    'base_url' => $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route' => $this->_request->getRouteName(),
                    'customer' => null,
                    'loading_gif' => $this->_assetRepo->getUrl('Cardpay_Core::images/loading.gif'),
                    'text-currency' => __('$'),
                    'text-choice' => __('Select'),
                    'default-issuer' => __('Default issuer'),
                    'text-installment' => __('Enter the card number'),
                    'logoUrl' => $this->_assetRepo->getUrl("Cardpay_Core::images/cp_logo.jpg"),
                    'platform_version' => $this->_productMetaData->getVersion(),
                    'module_version' => $this->_coreHelper->getModuleVersion(),
                    'mp_gateway_mode' => $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_GATEWAY_MODE, ScopeInterface::SCOPE_STORE),
                    'is_cpf_required' => $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_ASK_CPF, ScopeInterface::SCOPE_STORE),
                    'card_brands_logo_url' => $this->_assetRepo->getUrl("Cardpay_Core::images/card_brands.png"),
                ],
            ],
        ];
    }
}