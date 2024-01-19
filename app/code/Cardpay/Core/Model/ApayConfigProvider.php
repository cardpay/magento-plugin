<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Model\Payment\ApayPayment;
use Magento\Store\Model\ScopeInterface;

class ApayConfigProvider extends BasicConfigProvider
{
    protected $methodCode = ApayPayment::CODE;

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
                    'sandbox' => $this->getConfigWithDefault(ConfigData::PATH_APAY_SANDBOX, ScopeInterface::SCOPE_STORE)
                        ? "TEST" : "PRODUCTION",
                    'store_name' => $this->storeManager->getStore()->getName(),
                    'apay_merchant_id' => $this->getMerchantId(),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                ]
            ]
        ];
    }

    public function getMerchantId()
    {
        return $this->getConfigWithDefault(ConfigData::PATH_APAY_MERCHANT_ID, ScopeInterface::SCOPE_STORE);
    }

    public function getMerchantCertificate()
    {
        return $this->getConfigWithDefault(ConfigData::PATH_APAY_MERCHANT_CERTIFICATE, ScopeInterface::SCOPE_STORE);
    }

    public function getMerchantKey()
    {
        return $this->getConfigWithDefault(ConfigData::PATH_APAY_MERCHANT_KEY, ScopeInterface::SCOPE_STORE);
    }
}
