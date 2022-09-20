<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Payment\PixPayment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PixConfigProvider extends BasicConfigProvider
{
    protected $methodCode = PixPayment::CODE;

    /**
     * @return array
     */
    public function getConfig()
    {
        if (is_null($this->methodInstance) || !$this->methodInstance->isAvailable()) {
            return [];
        }

        /**
         * @var array
         */
        $paymentMethods = $this->methodInstance->getPixOptions();

        return [
            'payment' => [
                $this->methodCode => [
                    'country' => strtoupper($this->getConfigWithDefault(ConfigData::PATH_SITE_ID, ScopeInterface::SCOPE_STORE)),
                    'bannerUrl' => $this->getConfigWithDefault(ConfigData::PATH_PIX_BANNER, ScopeInterface::SCOPE_STORE),
                    'discount_coupon' => $this->scopeConfig->isSetFlag(ConfigData::PATH_PIX_COUPON, ScopeInterface::SCOPE_STORE),
                    'logEnabled' => $this->getConfigWithDefault(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE),
                    'options' => $paymentMethods,
                    'grand_total' => $this->checkoutSession->getQuote()->getGrandTotal(),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route' => !is_null($this->request) ? $this->request->getRouteName() : null,
                    'base_url' => $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK),
                    'loading_gif' => $this->assetRepo->getUrl('Cardpay_Core::images/loading.gif'),
                    'logoUrl' => $this->assetRepo->getUrl('Cardpay_Core::images/cp_logo.jpg'),
                    'platform_version' => $this->productMetaData->getVersion(),
                    'module_version' => $this->coreHelper->getModuleVersion(),
                    'pix_logo_url' => $this->assetRepo->getUrl('Cardpay_Core::images/pix.png'),
                ]
            ]
        ];
    }
}
