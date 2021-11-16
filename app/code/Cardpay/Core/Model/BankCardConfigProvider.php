<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Payment\BankCardPayment;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class BankCardConfigProvider implements ConfigProviderInterface
{
    /**
     * @var MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = BankCardPayment::CODE;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetaData;

    protected $coreHelper;

    /**
     * @param PaymentHelper $paymentHelper
     * @throws LocalizedException
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
        $this->request = $context->getRequest();
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->assetRepo = $assetRepo;
        $this->context = $context;
        $this->productMetaData = $productMetadata;
        $this->coreHelper = $coreHelper;
    }

    /**
     * Gather information to be sent to javascript method renderer
     *
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getConfig()
    {
        if (is_null($this->methodInstance) || !$this->methodInstance->isAvailable()) {
            return [];
        }

        return [
            'payment' => [
                $this->methodCode => [
                    'bannerUrl' => $this->scopeConfig->getValue(ConfigData::PATH_CUSTOM_BANNER, ScopeInterface::SCOPE_STORE),
                    'country' => strtoupper($this->scopeConfig->getValue(ConfigData::PATH_SITE_ID, ScopeInterface::SCOPE_STORE)),
                    'terminal_code' => $this->scopeConfig->getValue(ConfigData::PATH_BANKCARD_TERMINAL_CODE, ScopeInterface::SCOPE_STORE),
                    'logEnabled' => $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE),
                    'discount_coupon' => $this->scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_COUPON, ScopeInterface::SCOPE_STORE),
                    'grand_total' => $this->checkoutSession->getQuote()->getGrandTotal(),
                    'base_url' => $this->storeManager->getStore()->getBaseUrl(),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route' => !is_null($this->request) ? $this->request->getRouteName() : null,
                    'customer' => null,
                    'loading_gif' => $this->assetRepo->getUrl('Cardpay_Core::images/loading.gif'),
                    'text-currency' => __('$'),
                    'text-choice' => __('Select'),
                    'default-issuer' => __('Default issuer'),
                    'text-installment' => __('Enter the card number'),
                    'logoUrl' => $this->assetRepo->getUrl('Cardpay_Core::images/cp_logo.jpg'),
                    'platform_version' => $this->productMetaData->getVersion(),
                    'module_version' => $this->coreHelper->getModuleVersion(),
                    'mp_gateway_mode' => $this->scopeConfig->getValue(ConfigData::PATH_CUSTOM_GATEWAY_MODE, ScopeInterface::SCOPE_STORE),
                    'is_cpf_required' => $this->scopeConfig->getValue(ConfigData::PATH_CUSTOM_ASK_CPF, ScopeInterface::SCOPE_STORE),
                    'are_installments_enabled' => $this->scopeConfig->getValue(ConfigData::PATH_CUSTOM_INSTALLMENT, ScopeInterface::SCOPE_STORE),
                    'card_brands_logo_url' => $this->assetRepo->getUrl('Cardpay_Core::images/card_brands.png'),
                ],
            ],
        ];
    }
}