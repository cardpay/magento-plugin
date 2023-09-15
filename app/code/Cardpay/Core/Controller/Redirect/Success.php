<?php

namespace Cardpay\Core\Controller\Redirect;

class Success extends AbstractRedirectAction
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
