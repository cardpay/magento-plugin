<?php

namespace Cardpay\Core\Controller\Basic;

use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;

class Failure extends Action implements ViewInterface
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Failure constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig)
    {
        $this->_context = $context;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout(['default', 'cardpay_basic_failure']);
        $this->_view->renderLayout();
    }
}