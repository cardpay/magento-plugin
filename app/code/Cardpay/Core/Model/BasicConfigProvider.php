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

/**
 * Class BasicConfigProvider
 * @package Cardpay\Core\Model
 */
class BasicConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = Basic\Payment::CODE;
    protected $_scopeConfig;
    protected $_methodInstance;
    protected $_checkoutSession;
    protected $_assetRepo;
    protected $_productMetaData;
    protected $_coreHelper;
    protected $_context;

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
        $this->_methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_assetRepo = $assetRepo;
        $this->_productMetaData = $productMetadata;
        $this->_coreHelper = $coreHelper;
        $this->_context = $context;

    }

    /**
     * @return array
     */
    public function getConfig()
    {
        try {
            if (!$this->_methodInstance->isAvailable()) {
                return [];
            }

            $bannerInfo = $this->makeBannerCheckout();

            return [
                'payment' => [
                    $this->methodCode => [
                        'active' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_ACTIVE, ScopeInterface::SCOPE_STORE),
                        'actionUrl' => $this->_context->getUrl()->getUrl(Basic\Payment::ACTION_URL),
                        'banner_info' => $bannerInfo,
                        'logEnabled' => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE),
                        'auto_return' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_AUTO_RETURN, ScopeInterface::SCOPE_STORE),
                        'exclude_payments' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE),
                        'order_status' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_ORDER_STATUS, ScopeInterface::SCOPE_STORE),
                        'loading_gif' => $this->_assetRepo->getUrl('Cardpay_Core::images/loading.gif'),
                        'logoUrl' => $this->_assetRepo->getUrl("Cardpay_Core::images/cp_logo.jpg"),
                        'redirect_image' => $this->_assetRepo->getUrl("Cardpay_Core::images/redirect_checkout.png"),
                        'platform_version' => $this->_productMetaData->getVersion(),
                        'module_version' => $this->_coreHelper->getModuleVersion()
                    ],
                ],
            ];

        } catch (Exception $e) {
            $this->_coreHelper->log("BasicConfigProvider ERROR: " . $e->getMessage(), 'BasicConfigProvider');
            return [];
        }
    }

    public function makeBannerCheckout()
    {
        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_TERMINAL_PASSWORD, ScopeInterface::SCOPE_WEBSITE);
        $excludePaymentMethods = $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE);

        $excludePaymentMethods = explode(",", $excludePaymentMethods);

        try {
            $paymentMethods = RestClient::get("/v1/payment_methods", null, ["Authorization: Bearer " . $accessToken]);

            //validate active payments methods
            $debit = 0;
            $credit = 0;
            $ticket = 0;
            $choMethods = [];

            foreach ($paymentMethods['response'] as $pm) {
                if (!in_array($pm['id'], $excludePaymentMethods)) {
                    $choMethods[] = $pm;
                    if ($pm['payment_type_id'] == 'credit_card') {
                        $credit += 1;
                    } elseif ($pm['payment_type_id'] == 'debit_card' || $pm['payment_type_id'] == 'prepaid_card') {
                        $debit += 1;
                    } else {
                        $ticket += 1;
                    }
                }
            }

            return [
                "debit" => $debit,
                "credit" => $credit,
                "ticket" => $ticket,
                "checkout_methods" => $choMethods
            ];

        } catch (Exception $e) {
            $this->_coreHelper->log("makeBannerCheckout:: An error occurred at the time of obtaining the ticket payment methods: " . $e);
            return [];
        }
    }
}
