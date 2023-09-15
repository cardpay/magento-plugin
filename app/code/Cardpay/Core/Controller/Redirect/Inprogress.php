<?php

namespace Cardpay\Core\Controller\Redirect;

class Inprogress extends AbstractRedirectAction
{
    const LOG_NAME = 'inprogress-redirect-page';

    /**
     * Controller Action
     */
    public function execute()
    {
        $message = __('Unlimit - Transaction in progress.');
        $this->executeWithMessage($message);
    }
}
