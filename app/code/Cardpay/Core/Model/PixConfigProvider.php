<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Model\Payment\PixPayment;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

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
                    'country' => strtoupper($this->getConfigWithDefault(
                        ConfigData::PATH_SITE_ID,
                        ScopeInterface::SCOPE_STORE
                    )
                    ),
                    'bannerUrl' => $this->getConfigWithDefault(
                        ConfigData::PATH_PIX_BANNER,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'logEnabled' => $this->getConfigWithDefault(
                        ConfigData::PATH_ADVANCED_LOG,
                        ScopeInterface::SCOPE_STORE
                    ),
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
