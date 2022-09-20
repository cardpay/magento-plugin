<?php

namespace Cardpay\Core\Controller\ParamsContainer;

use Cardpay\Core\Helper\Data;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository;


class BasicConfigParams
{
    protected Context $context;
    protected ScopeConfigInterface $scopeConfig;
    protected Repository $assetRepo;
    protected Data $coreHelper;


    public function __construct(
        Context              $context,
        ScopeConfigInterface $scopeConfig,
        Repository           $assetRepo,
        Data                 $coreHelper
    )
    {
        $this->context = $context;
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetRepo;
        $this->coreHelper = $coreHelper;
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return ScopeConfigInterface
     */
    public function getScopeConfig(): ScopeConfigInterface
    {
        return $this->scopeConfig;
    }

    /**
     * @return Repository
     */
    public function getAssetRepo(): Repository
    {
        return $this->assetRepo;
    }

    /**
     * @return Data
     */
    public function getCoreHelper(): Data
    {
        return $this->coreHelper;
    }
}
