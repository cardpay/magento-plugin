<?php

namespace Cardpay\Core\Controller\ParamsContainer;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ParamContextContainer
{
    protected Context $context;
    protected Data $data;
    protected Core $core;
    protected Notifications $notifications;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(Context $context, Data $data, Core $core, Notifications $notifications, ScopeConfigInterface $scopeConfig)
    {
        $this->context = $context;
        $this->data = $data;
        $this->core = $core;
        $this->notifications = $notifications;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return Data
     */
    public function getData(): Data
    {
        return $this->data;
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * @return Notifications
     */
    public function getNotifications(): Notifications
    {
        return $this->notifications;
    }

    /**
     * @return ScopeConfigInterface
     */
    public function getScopeConfig(): ScopeConfigInterface
    {
        return $this->scopeConfig;
    }
}
