<?php

namespace Cardpay\Core\Controller\Redirect;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Webapi\Rest\Request;

abstract class AbstractRedirectAction extends Action
{

    const LOG_NAME = '';
    /**
     * @var bool
     */
    protected $isExecuted;

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
    protected $notifications;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param  Context  $context
     * @param  Data  $coreHelper
     * @param  Core  $coreModel
     * @param  Notifications  $notifications
     * @param  Request  $request
     */
    public function __construct(
        Context $context,
        Data $coreHelper,
        Core $coreModel,
        Notifications $notifications,
        Request $request
    ) {
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->notifications = $notifications;
        $this->request = $request;
        $this->isExecuted = false;

        parent::__construct($context);
    }

    /**
     * @param $httpStatus
     * @param $message
     * @param  array  $data
     */
    protected function setResponseHttp($httpStatus, $message, $data = [])
    {
        /**
         * @var ResponseInterface
         */
        $response = $this->getResponse();
        if (is_null($response)) {
            return;
        }

        $response->setHeader('Content-Type', 'text/plain', true);
        $response->setBody($message);
        $response->setHttpResponseCode($httpStatus);
    }

    /**
     * @return bool
     */
    public function isExecuted()
    {
        return $this->isExecuted;
    }

    protected function executeWithMessage($message)
    {
        $this->isExecuted = false;

        try {
            $request = $this->request;

            $bodyParams = $request->getBodyParams();
            $params = $request->getParams();

            $this->coreHelper->log(
                static::LOG_NAME.'::execute - Request Params: '.json_encode($bodyParams),
                self::LOG_NAME
            );
            $this->coreHelper->log(
                static::LOG_NAME.'::execute - Request BodyParams: '.json_encode($params),
                self::LOG_NAME
            );

            $this->setResponseHttp('200', $message);

            $this->isExecuted = true;
            return;
        } catch (Exception $e) {
            $statusResponse = Response::HTTP_INTERNAL_ERROR;

            if (method_exists($e, 'getCode')) {
                $statusResponse = $e->getCode();
            }

            $message = 'Unlimit - There was an error processing the redirection.';
            $this->setResponseHttp($statusResponse, $message, ['exception_error' => $e->getMessage()]);
        }
    }
}
