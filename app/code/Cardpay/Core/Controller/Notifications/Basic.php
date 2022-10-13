<?php

namespace Cardpay\Core\Controller\Notifications;

use Cardpay\Core\Exceptions\UnlimintBaseException;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Basic\Payment;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Cardpay\Core\Model\Notifications\Topics\MerchantOrder;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderFactory;

class Basic extends NotificationBase
{
    public const LOG_NAME = 'basic_notification';

    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charged_back'];

    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];

    /**
     * @var Payment
     */
    protected $paymentFactory;

    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var MerchantOrder
     */
    protected $finalStatus;

    /**
     * @var Core
     */
    protected $coreModel;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Notifications
     */
    protected $notifications;

    /**
     * @var Request
     */
    protected $request;

    /**
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
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->orderFactory = $orderFactory;
        $this->notifications = $notifications;
        $this->request = $request;

        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $requestParams = $this->notifications->getRequestParams($this->request);

            $topicClass = $this->notifications->getPayment();
            $data = $this->notifications->getPaymentInformation($topicClass, $requestParams);
            if (empty($data)) {
                throw new UnlimintBaseException(__('Error Merchant Order notification is expected'), 400);
            }
            $merchantOrder = $data['merchantOrder'];

            if (is_null($merchantOrder)) {
                throw new UnlimintBaseException(__('Merchant Order not found or is an notification invalid type.'), 400);
            }

            $order = $this->orderFactory->create()->loadByIncrementId($merchantOrder["external_reference"]);
            if (is_null($order) || empty($order->getId())) {
                throw new UnlimintBaseException(__('Error Order Not Found in Magento: ') . $merchantOrder["external_reference"], 400);
            }
            if ($order->getStatus() === 'canceled') {
                throw new UnlimintBaseException(__('Order already cancelled: ') . $merchantOrder["external_reference"], 400);
            }

            $data['statusFinal'] = $this->finalStatus->getStatusFinal($merchantOrder);

            if (!$topicClass->validateRefunded($order, $data)) {
                throw new UnlimintBaseException(__('Error Order Refund'), 400);
            }

            $statusResponse = $topicClass->updateOrder($order, $data);

            $this->setResponseHttp($statusResponse['code'], $statusResponse['text'], $this->request->getBodyParams());
        } catch (Exception $e) {
            $this->setResponseHttp($e->getCode(), $e->getMessage(), $this->request->getBodyParams());
        }
    }

    /**
     * @param $httpStatus
     * @param $message
     * @param array $data
     */
    protected function setResponseHttp($httpStatus, $message, $data = [])
    {
        $responseParams = [
            'status' => $httpStatus,
            'message' => $message,
            'data' => $data
        ];

        $response = $this->getResponse();
        if (is_null($response)) {
            return;
        }

        $response->setHeader('Content-Type', 'application/json', true);
        $response->setBody(json_encode($responseParams));
        $response->setHttpResponseCode($httpStatus);
    }
}
