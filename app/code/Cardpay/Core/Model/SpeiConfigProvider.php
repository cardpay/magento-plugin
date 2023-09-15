<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Model\Payment\SpeiPayment;
use Magento\Store\Model\ScopeInterface;

class SpeiConfigProvider extends BasicConfigProvider
{
    protected $methodCode = SpeiPayment::CODE;

    /**
     * @return array
     */
    public function getConfig()
    {
        if (is_null($this->methodInstance) || !$this->methodInstance->isAvailable()) {
            return [];
        }
        return [
            'payment' => [
                $this->methodCode => [
                    'country' => strtoupper($this->getConfigWithDefault(
                        ConfigData::PATH_SITE_ID,
                        ScopeInterface::SCOPE_STORE
                    )),
                    'api_access_mode' => $this->getConfigWithDefault(
                        ConfigData::PATH_SPEI_API_ACCESS_MODE,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'spei_logo_url' => $this->assetRepo->getUrl('Cardpay_Core::images/spei.png'),
                ]
            ]
        ];
    }
}
