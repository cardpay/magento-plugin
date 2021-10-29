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
    const LOG_NAME = 'creditcard_notification';

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

    public function execute()
    {
        try {
            $request = $this->_wRequest;

            $requestValues = $this->_notifications->getRequestParams($request);
            $notificationPayment = $this->_notifications->getPayment();

            if ($requestValues['method'] !== 'BANKCARD') {
                $message = 'Unlimint - Invalid Notification Parameters, Invalid Type.';
                $this->setResponseHttp(Response::HTTP_BAD_REQUEST, $message, $request->getBodyParams());
            }

            $requestParams = $request->getBodyParams();

            $response = null;
            if ($requestValues['type'] === 'refund_data') {
                $notificationPayment->refund($requestParams);
            } else {
                $response = $notificationPayment->updateStatusOrderByPayment($requestParams);
            }
            if (isset($response['httpStatus'], $response['message'], $response['data'])) {
                $this->setResponseHttp($response['httpStatus'], $response['message'], $response['data']);
            }

        } catch (Exception $e) {
            $statusResponse = Response::HTTP_INTERNAL_ERROR;

            if (method_exists($e, 'getCode')) {
                $statusResponse = $e->getCode();
            }

            $message = 'Unlimint - There was an error processing the notification. Could not handle the error.';
            $this->setResponseHttp($statusResponse, $message, ['exception_error' => $e->getMessage()]);
        }
    }

    /**
     * @param $httpStatus
     * @param $message
     * @param array $data
     */
    protected function setResponseHttp($httpStatus, $message, $data = [])
    {
        $response = [
            'status' => $httpStatus,
            'message' => $message,
            'data' => $data
        ];

        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($response));
        $this->getResponse()->setHttpResponseCode($httpStatus);
    }
}