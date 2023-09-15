<?php

namespace Cardpay\Core\Controller\Redirect;

class Decline extends AbstractRedirectAction
{
    const LOG_NAME = 'decline-redirect-page';

    /**
     * Controller Action
     */
    public function execute()
    {
        $message = __('Unlimit - Transaction declined.');
        $this->executeWithMessage($message);
    }
}
