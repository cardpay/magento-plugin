<?php

namespace Cardpay\Core\Controller\Basic;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Basic\Payment;
use Exception;
use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class Pay extends Action implements ViewInterface
{
    public const LOG_NAME = 'CONTROLLER_BASIC_PAY';

    /**
     * @var \Cardpay\Core\Model\Basic\Payment
     */
    protected $_paymentFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var
     */
    protected $_messageManager;

    /**
     * @var ResultFactory
     */
    protected $_resultFactory;

    /**
     * @var
     */
    protected $_url;

    /**
     * @var
     */
    protected $_coreHelper;

    /**
     * Pay constructor
     *
     * @param Context $context
     * @param Payment $paymentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param UrlInterface $urlInterface
     * @param Data $coreHelper
     */
    public function __construct(
        Context              $context,
        Payment              $paymentFactory,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface     $messageManager,
        ResultFactory        $resultFactory,
        UrlInterface         $urlInterface,
        Data                 $coreHelper
    )
    {
        $this->_paymentFactory = $paymentFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
        $this->_resultFactory = $resultFactory;
        $this->_url = $urlInterface;
        $this->_coreHelper = $coreHelper;

        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $array_assign = $this->_paymentFactory->postPago();
            $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
            if ((int)$array_assign['status'] !== 400) {
                $resultRedirect->setUrl($array_assign['init_point']);
            } else {
                $this->_messageManager->addError(__($array_assign['message']));
                $resultRedirect->setUrl($this->_url->getUrl($this->_scopeConfig->getValue(ConfigData::PATH_BASIC_URL_FAILURE)));
            }
            return $resultRedirect;
        } catch (Exception $e) {
            $this->_coreHelper->log("ERROR CONTROLLER BASIC PAY: " . $e->getMessage(), self::LOG_NAME);
            return null;
        }
    }
}