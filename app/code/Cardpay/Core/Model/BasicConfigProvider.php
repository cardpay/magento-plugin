<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Lib\RestClient;
use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\ScopeInterface;

class BasicConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = Basic\Payment::CODE;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $methodInstance;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetaData;

    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var Context
     */
    protected $context;

    /**
     * BasicConfigProvider constructor.
     * @param Context $context
     * @param PaymentHelper $paymentHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param Repository $assetRepo
     * @param ProductMetadataInterface $productMetadata
     * @param Data $coreHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context                  $context,
        PaymentHelper            $paymentHelper,
        ScopeConfigInterface     $scopeConfig,
        Session                  $checkoutSession,
        Repository               $assetRepo,
        ProductMetadataInterface $productMetadata,
        Data                     $coreHelper
    )
    {
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->assetRepo = $assetRepo;
        $this->productMetaData = $productMetadata;
        $this->coreHelper = $coreHelper;
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        try {
            if (is_null($this->methodInstance) || !$this->methodInstance->isAvailable()) {
                return [];
            }

            $bannerInfo = $this->makeBannerCheckout();

            return [
                'payment' => [
                    $this->methodCode => [
                        'active' => $this->scopeConfig->getValue(ConfigData::PATH_BASIC_ACTIVE, ScopeInterface::SCOPE_STORE),
                        'actionUrl' => $this->context->getUrl()->getUrl(Basic\Payment::ACTION_URL),
                        'banner_info' => $bannerInfo,
                        'logEnabled' => $this->scopeConfig->getValue(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE),
                        'auto_return' => $this->scopeConfig->getValue(ConfigData::PATH_BASIC_AUTO_RETURN, ScopeInterface::SCOPE_STORE),
                        'exclude_payments' => $this->scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE),
                        'order_status' => $this->scopeConfig->getValue(ConfigData::PATH_BASIC_ORDER_STATUS, ScopeInterface::SCOPE_STORE),
                        'loading_gif' => $this->assetRepo->getUrl('Cardpay_Core::images/loading.gif'),
                        'logoUrl' => $this->assetRepo->getUrl('Cardpay_Core::images/cp_logo.jpg'),
                        'redirect_image' => $this->assetRepo->getUrl('Cardpay_Core::images/redirect_checkout.png'),
                        'platform_version' => $this->productMetaData->getVersion(),
                        'module_version' => $this->coreHelper->getModuleVersion()
                    ],
                ],
            ];

        } catch (Exception $e) {
            $this->coreHelper->log('BasicConfigProvider ERROR: ' . $e->getMessage(), 'BasicConfigProvider');
            return [];
        }
    }

    private function makeBannerCheckout()
    {
        $accessToken = $this->scopeConfig->getValue(ConfigData::PATH_BANKCARD_TERMINAL_PASSWORD, ScopeInterface::SCOPE_WEBSITE);
        $excludePaymentMethods = $this->scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE);

        $excludePaymentMethods = explode(',', $excludePaymentMethods);

        try {
            $paymentMethods = RestClient::get('/v1/payment_methods', null, ['Authorization: Bearer ' . $accessToken]);

            //validate active payments methods
            $debit = 0;
            $credit = 0;
            $ticket = 0;
            $choMethods = [];

            foreach ($paymentMethods['response'] as $pm) {
                if (!in_array($pm['id'], $excludePaymentMethods)) {
                    $choMethods[] = $pm;
                    if ((string)$pm['payment_type_id'] === 'credit_card') {
                        ++$credit;
                    } elseif ((string)$pm['payment_type_id'] === 'debit_card' || (string)$pm['payment_type_id'] === 'prepaid_card') {
                        ++$debit;
                    } else {
                        ++$ticket;
                    }
                }
            }

            return [
                'debit' => $debit,
                'credit' => $credit,
                'ticket' => $ticket,
                'checkout_methods' => $choMethods
            ];

        } catch (Exception $e) {
            $this->coreHelper->log('makeBannerCheckout:: An error occurred at the time of obtaining the ticket payment methods: ' . $e);
            return [];
        }
    }
}
