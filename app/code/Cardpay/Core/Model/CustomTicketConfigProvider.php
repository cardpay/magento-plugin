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
class CustomTicketConfigProvider
    implements ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = CustomTicket\Payment::CODE;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

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
     * @var UrlInterface
     */
    protected $_urlBuilder;
    protected $_coreHelper;
    protected $_productMetaData;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context                  $context,
        PaymentHelper            $paymentHelper,
        Session                  $checkoutSession,
        ScopeConfigInterface     $scopeConfig,
        StoreManagerInterface    $storeManager,
        Repository               $assetRepo,
        Data                     $coreHelper,
        ProductMetadataInterface $productMetadata
    )
    {
        $this->_request = $context->getRequest();
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $context->getUrl();
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
        $this->_coreHelper = $coreHelper;
        $this->_productMetaData = $productMetadata;
    }

    /**
     * @return array
     */
    public function getConfig()
    {

        if (!$this->methodInstance->isAvailable()) {

            return [];
        }

        $paymentMethods = $this->methodInstance->getTicketsOptions();

        return [
            'payment' => [
                $this->methodCode => [
                    'country' => strtoupper($this->_scopeConfig->getValue(ConfigData::PATH_SITE_ID, ScopeInterface::SCOPE_STORE)),
                    'bannerUrl' => $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_TICKET_BANNER, ScopeInterface::SCOPE_STORE),
                    'discount_coupon' => $this->_scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_TICKET_COUPON, ScopeInterface::SCOPE_STORE),
                    'logEnabled' => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE),
                    'options' => $paymentMethods,
                    'grand_total' => $this->_checkoutSession->getQuote()->getGrandTotal(),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route' => $this->_request->getRouteName(),
                    'base_url' => $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK),
                    'loading_gif' => $this->_assetRepo->getUrl('Cardpay_Core::images/loading.gif'),
                    'logoUrl' => $this->_assetRepo->getUrl("Cardpay_Core::images/cp_logo.jpg"),
                    'platform_version' => $this->_productMetaData->getVersion(),
                    'module_version' => $this->_coreHelper->getModuleVersion(),
                    'boleto_logo_url' => $this->_assetRepo->getUrl("Cardpay_Core::images/boleto.png"),
                ]
            ]
        ];
    }
}