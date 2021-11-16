<?php

namespace Cardpay\Core\Controller\Redirect;

use Cardpay\Core\Helper\Response;
use Exception;
use Magento\Framework\App\ObjectManager;

class Callback extends AbstractRedirectAction
{
    const LOG_NAME = 'callback';

    /**
     * Controller Action
     * <magento_url>/cardpay/checkout/page
     */
    public function execute()
    {
        $this->isExecuted = false;

        try {
            $request = $this->getRequest();
            $params = $request->getParams();
            $action = $params['action'];

            switch ($action) {
                case 'success':
                case 'inprogress':
                    $this->_redirect('checkout/onepage/success');
                    break;

                case 'cancel':
                case 'decline':
                    $objectManager = ObjectManager::getInstance();
                    $checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
                    $checkoutSession->restoreQuote();

                    $this->_redirect('cardpay/basic/failure/');
                    break;

                default:
                    break;
            }

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