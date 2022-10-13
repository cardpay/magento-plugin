<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Payment\BankCardPayment;
use Cardpay\Core\Model\Payment\PixPayment;
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

class BankCardConfigProvider extends BasicConfigProvider
{
    protected $methodCode = BankCardPayment::CODE;

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
        $scope = ScopeInterface::SCOPE_STORE;
        return [
            'payment' => [
                $this->methodCode => [
                    'maximum_accepted_installments' => $this->getConfigWithDefault(ConfigData::PATH_BANKCARD_MAXIMUM_ACCEPTED_INSTALLMENTS, $scope),
                    'api_access_mode' => $this->getConfigWithDefault(ConfigData::PATH_BANKCARD_API_ACCESS_MODE, $scope),
                    'installment_type' => $this->getConfigWithDefault(ConfigData::PATH_BANKCARD_INSTALLMENT_TYPE, $scope),
                    'minimum_installment_amount' => $this->getConfigWithDefault(ConfigData::PATH_BANKCARD_MINIMUM_INSTALLMENT_AMOUNT, $scope),
                    'bannerUrl' => $this->getConfigWithDefault(ConfigData::PATH_CUSTOM_BANNER, $scope),
                    'country' => strtoupper($this->getConfigWithDefault(ConfigData::PATH_SITE_ID, $scope)),
                    'terminal_code' => $this->getConfigWithDefault(ConfigData::PATH_BANKCARD_TERMINAL_CODE, $scope),
                    'logEnabled' => $this->getConfigWithDefault(ConfigData::PATH_ADVANCED_LOG, $scope),
                    'mp_gateway_mode' => $this->getConfigWithDefault(ConfigData::PATH_CUSTOM_GATEWAY_MODE, $scope),
                    'is_cpf_required' => (
                        $this->getConfigWithDefault(ConfigData::PATH_CUSTOM_ASK_CPF, $scope) &&
                        ($this->getConfigWithDefault(ConfigData::PATH_BANKCARD_API_ACCESS_MODE, $scope) === 'gateway')
                    ),
                    'are_installments_enabled' => $this->getConfigWithDefault(ConfigData::PATH_CUSTOM_INSTALLMENT, $scope),
                    'discount_coupon' => $this->scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_COUPON, $scope),
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
                    'card_brands_logo_url' => $this->assetRepo->getUrl('Cardpay_Core::images/card_brands.png'),
                ],
            ],
        ];
    }
}
