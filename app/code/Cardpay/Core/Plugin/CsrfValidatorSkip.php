<?php

namespace Cardpay\Core\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

class CsrfValidatorSkip
{
    /**
     * @param  CsrfValidator  $subject
     * @param  callable  $proceed
     * @param  RequestInterface  $request
     * @param  ActionInterface  $action
     * @return void
     */
    public function aroundValidate(
        CsrfValidator $subject, //NOSONAR
        callable $proceed,
        RequestInterface $request,
        ActionInterface $action
    ) {
        if ($request->getModuleName() === 'cardpay') {
            return null; // Skip CSRF check
        }
        return $proceed($request, $action);
    }

}
