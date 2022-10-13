<?php

namespace Cardpay\Core\Controller\Redirect;

use Cardpay\Core\Helper\Response;
use Exception;

class Success extends AbstractRedirectAction
{
    const LOG_NAME = 'success-redirect-page';

    /**
     * Controller Action
     */
    public function execute()
    {
        $this->isExecuted = false;

        try {
            $message = __('Unlimint - Transaction created successfully.');
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
