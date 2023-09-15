<?php

namespace Cardpay\Core\Controller\Redirect;

class Cancel extends AbstractRedirectAction
{
    const LOG_NAME = 'cancel-redirect-page';

    /**
     * Controller Action
     */
    public function execute()
    {
        $message = __('Unlimit - Transaction canceled.');
        $this->executeWithMessage($message);
    }
}
