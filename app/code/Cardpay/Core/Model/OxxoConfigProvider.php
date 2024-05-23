<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Model\Payment\OxxoPayment;
use Magento\Store\Model\ScopeInterface;

class OxxoConfigProvider extends BasicConfigProvider
{
    protected $methodCode = OxxoPayment::CODE;

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
                    'country' => strtoupper(
                        $this->getConfigWithDefault(
                            ConfigData::PATH_SITE_ID,
                            ScopeInterface::SCOPE_STORE
                        )
                    ),
                    'api_access_mode' => $this->getConfigWithDefault(
                        ConfigData::PATH_OXXO_API_ACCESS_MODE,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'oxxo_logo_url' => $this->assetRepo->getUrl('Cardpay_Core::images/oxxo.png'),
                ]
            ]
        ];
    }
}
