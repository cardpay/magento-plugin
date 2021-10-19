<?php

namespace Cardpay\Core\Controller\Redirect;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Class Creditcard
 *
 * @package Cardpay\Core\Controller\Notifications
 */
class Success extends Action
{
    const LOG_NAME = 'success-redirect-page';

    protected $coreHelper;
    protected $coreModel;
    protected $_order;
    protected $_notifications;
    protected $_wRequest;

    /**
     * Creditcard constructor.
     * @param Context $context
     * @param Data $coreHelper
     * @param Core $coreModel
     */
    public function __construct(Context $context, Data $coreHelper, Core $coreModel, Notifications $notifications, Request $wRequest)
    {
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->_notifications = $notifications;
        $this->_wRequest = $wRequest;
        parent::__construct($context);
    }

    /**
     * Controller Action
     */
    public function execute()
    {
        try {
            $message = __('Unlimint - Transaction created successfully.');
            $this->setResponseHttp('200', $message);

            return;

        } catch (Exception $e) {
            $statusResponse = Response::HTTP_INTERNAL_ERROR;

            if (method_exists($e, "getCode")) {
                $statusResponse = $e->getCode();
            }

            $message = "Unlimint - There was an error processing the redirection.";
            $this->setResponseHttp($statusResponse, $message, ["exception_error" => $e->getMessage()]);
        }
    }

    /**
     * @param $httpStatus
     * @param $message
     * @param array $data
     */
    protected function setResponseHttp($httpStatus, $message, $data = [])
    {
        $this->getResponse()->setHeader('Content-Type', 'text/plain', true);
        $this->getResponse()->setBody($message);
        $this->getResponse()->setHttpResponseCode($httpStatus);
    }
}