<?php

namespace Cardpay\Core\Controller\Redirect;

use Cardpay\Core\Helper\Response;
use Exception;

class Decline extends AbstractRedirectAction
{
    const LOG_NAME = 'decline-redirect-page';

    /**
     * Controller Action
     */
    public function execute()
    {
        $this->isExecuted = false;

        try {
            $request = $this->request;

            $bodyParams = $request->getBodyParams();
            $params = $request->getParams();

            $this->coreHelper->log('DeclinePage::execute - Request Params: ' . json_encode($bodyParams), self::LOG_NAME);
            $this->coreHelper->log('DeclinePage::execute - Request BodyParams: ' . json_encode($params), self::LOG_NAME);

            $message = __('Unlimint - Transaction declined.');
            $this->setResponseHttp('200', $message);

            $this->isExecuted = true;
            return;
        } catch (Exception $e) {
            $statusResponse = Response::HTTP_INTERNAL_ERROR;

            if (method_exists($e, 'getCode')) {
                $statusResponse = $e->getCode();
            }

            $message = 'Unlimint - There was an error processing the redirection.';
            $this->setResponseHttp($statusResponse, $message, ['exception_error' => $e->getMessage()]);
        }
    }
}
