<?php

namespace Cardpay\Core\Block\CustomBankTransfer;

use Cardpay\Core\Block\AbstractSuccess;
use Cardpay\Core\Helper\ConfigData;

/**
 * Class Success
 *
 * @package Cardpay\Core\Block\CustomBankTransfer
 */
class Success extends AbstractSuccess
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('custom_bank_transfer/success.phtml');
    }

    public function getRedirectUserStatus()
    {
        return $this->_scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_BANK_TRANSFER_REDIRECT_PAYER);
    }

    public function checkExistCallback()
    {
        $callback = $this->getRequest()->getParam('callback');
        return !empty($callback);
    }
}