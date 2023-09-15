<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Model\Payment\GpayPayment;
use Magento\Store\Model\ScopeInterface;

class GpayConfigProvider extends BasicConfigProvider
{
    protected $methodCode = GpayPayment::CODE;

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
                        $this->getConfigWithDefault(ConfigData::PATH_SITE_ID, ScopeInterface::SCOPE_STORE)
                    ),
                    'sandbox' => $this->getConfigWithDefault(ConfigData::PATH_GPAY_SANDBOX, ScopeInterface::SCOPE_STORE)
                        ? "TEST" : "PRODUCTION",
                    'gpay_merchant_id' => $this->getConfigWithDefault(
                        ConfigData::PATH_GPAY_MERCHANT_ID,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                ]
            ]
        ];
    }
}
