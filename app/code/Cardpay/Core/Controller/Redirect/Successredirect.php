<?php

namespace Cardpay\Core\Controller\Redirect;

use Cardpay\Core\Helper\Response;
use Exception;

class Successredirect extends AbstractRedirectAction
{
    const LOG_NAME = 'success-redirect-page';

    /**
     * Controller Action
     */
    public function execute()
    {
        $message = __('Unlimit - Transaction created successfully.');
        $this->executeWithMessage($message);
    }
}
