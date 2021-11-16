<?php

namespace Cardpay\Core\Controller\Notifications;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Webapi\Rest\Request;

class Creditcard extends NotificationBase
{
    // callback responses
    private const SUCCESSFUL_RESPONSE = "OK";
    private const UNSUCCESSFUL_RESPONSE = "NOT OK";

    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var Core
     */
    protected $coreModel;

    /**
     * @var Notifications
     */
    protected $_notifications;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @param Context $context
     * @param Data $coreHelper
     * @param Core $coreModel
     * @param Notifications $notifications
     * @param Request $request
     */
    public function __construct(Context $context, Data $coreHelper, Core $coreModel, Notifications $notifications, Request $request)
    {
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->_notifications = $notifications;
        $this->_request = $request;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $request = $this->_request;

            $requestData = $this->_notifications->getRequestParams($request);
            $notificationPayment = $this->_notifications->getPayment();

            $body = $request->getContent();
            $requestParams = json_decode($body, true);

            $response = null;
            if ($requestData['type'] === 'refund_data') {
                $notificationPayment->refund($requestParams);
            } else {
                $response = $notificationPayment->updateStatusOrderByPayment($requestParams);
            }
            if (isset($response['httpStatus'])) {
                $this->setResponseHttp($response['httpStatus'], self::SUCCESSFUL_RESPONSE);
            }

        } catch (Exception $e) {
            $this->coreHelper->log('Creditcard::execute error: ' . $e->getMessage());

            $statusResponse = Response::HTTP_INTERNAL_ERROR;

            if (method_exists($e, 'getCode')) {
                $statusResponse = $e->getCode();
            }

            $this->setResponseHttp($statusResponse, self::UNSUCCESSFUL_RESPONSE);
        }
    }

    /**
     * @param $httpStatus
     * @param $message
     */
    protected function setResponseHttp($httpStatus, $message)
    {
        $response = $this->getResponse();
        if (is_null($response)) {
            return;
        }

        $response->setHeader('Content-Type', 'application/json', true);
        $response->setHttpResponseCode($httpStatus);
        $response->setBody($message);
    }
}