<?php

namespace Cardpay\Core\Controller\Notifications;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Basic\Payment;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderFactory;

class Basic extends NotificationBase
{
    const LOG_NAME = 'basic_notification';

    protected $paymentFactory;
    protected $coreHelper;
    protected $coreModel;
    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charged_back'];
    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];
    protected $orderFactory;
    protected $notifications;
    protected $request;

    /**
     * Basic constructor
     * @param Context $context
     * @param Payment $paymentFactory
     * @param Data $coreHelper
     * @param Core $coreModel
     * @param OrderFactory $orderFactory
     * @param Notifications $notifications
     */
    public function __construct(
        Context       $context,
        Payment       $paymentFactory,
        Data          $coreHelper,
        Core          $coreModel,
        OrderFactory  $orderFactory,
        Notifications $notifications,
        Request       $request
    )
    {
        $this->paymentFactory = $paymentFactory;
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->orderFactory = $orderFactory;
        $this->notifications = $notifications;
        $this->request = $request;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $request = $this->request;

        try {
            $requestParams = $this->notifications->getRequestParams($request);

            $topicClass = $this->notifications->getPayment();
            $data = $this->notifications->getPaymentInformation($topicClass, $requestParams);
            if (empty($data)) {
                throw new Exception(__('Error Merchant Order notification is expected'), 400);
            }
            $merchantOrder = $data['merchantOrder'];

            if (is_null($merchantOrder)) {
                throw new Exception(__('Merchant Order not found or is an notification invalid type.'), 400);
            }

            $order = $this->orderFactory->create()->loadByIncrementId($merchantOrder["external_reference"]);
            if (empty($order) || empty($order->getId())) {
                throw new Exception(__('Error Order Not Found in Magento: ') . $merchantOrder["external_reference"], 400);
            }
            if ($order->getStatus() === 'canceled') {
                throw new Exception(__('Order already cancelled: ') . $merchantOrder["external_reference"], 400);
            }

            $data['statusFinal'] = $topicClass->getStatusFinal($merchantOrder);

            if (!$topicClass->validateRefunded($order, $data)) {
                throw new Exception(__('Error Order Refund'), 400);
            }

            $statusResponse = $topicClass->updateOrder($order, $data);

            $this->setResponseHttp($statusResponse['code'], $statusResponse['text'], $request->getBodyParams());

        } catch (Exception $e) {
            $this->setResponseHttp($e->getCode(), $e->getMessage(), $request->getBodyParams());
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