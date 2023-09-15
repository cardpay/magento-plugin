<?php

namespace Cardpay\Core\Model;

use Cardpay\Core\Controller\ParamsContainer\BasicConfigParams;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Payment\BankCardPayment;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class BasicConfigProvider implements ConfigProviderInterface
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

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    protected $coreHelper;

    /**
     * @param PaymentHelper $paymentHelper
     * @throws LocalizedException
     */
    public function __construct(
        PaymentHelper            $paymentHelper,
        Session                  $checkoutSession,
        StoreManagerInterface    $storeManager,
        ProductMetadataInterface $productMetadata,
        BasicConfigParams        $basicConfigParams
    )
    {
        $this->scopeConfig = $basicConfigParams->getScopeConfig();
        $this->context = $basicConfigParams->getContext();
        $this->assetRepo = $basicConfigParams->getAssetRepo();
        $this->coreHelper = $basicConfigParams->getCoreHelper();

        $this->request = $this->context->getRequest();
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->productMetaData = $productMetadata;
        $this->urlBuilder = $this->context->getUrl();
    }

    /**
     * @param $path
     * @param $scopeType
     * @param string $default
     * @return mixed|string
     */
    protected function getConfigWithDefault($path, $scopeType, string $default = '')
    {
        return $this->scopeConfig->getValue($path, $scopeType) ?? $default;
    }
}
