<?php

namespace Cardpay\Core\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Info
 *
 * @package Cardpay\Core\Block
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, OrderFactory $orderFactory, array $data = [])
    {
        parent::__construct($context, $data);
        $this->_orderFactory = $orderFactory;
    }

    /**
     * Prepare information specific to current payment method
     *
     * @param null | array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];

        $info = $this->getInfo();
        $paymentResponse = $info->getAdditionalInformation("paymentResponse");

        if (isset($paymentResponse['id'])) {
            $title = __('Payment id (Cardpay)');
            $data[$title->__toString()] = $paymentResponse['id'];
        }

        if (
            isset($paymentResponse['card']['first_six_digits']) &&
            isset($paymentResponse['card']['last_four_digits'])
        ) {
            $title = __('Card number');
            $data[$title->__toString()] =
                $paymentResponse['card']['first_six_digits'] . "..." . $paymentResponse['card']['last_four_digits'];
        }

        if (isset($paymentResponse['card']['expiration_month']) && isset($paymentResponse['card']['expiration_year'])) {
            $title = __('Expiration date');
            $data[$title->__toString()] =
                $paymentResponse['card']['expiration_month'] . "/" . $paymentResponse['card']['expiration_year'];
        }

        if (isset($paymentResponse['card']['cardholder']['name'])) {
            $title = __('CardHolder name');
            $data[$title->__toString()] = $paymentResponse['card']['cardholder']['name'];
        }

        if (isset($paymentResponse['payment_method_id'])) {
            $title = __('Payment method');
            $data[$title->__toString()] = ucfirst($paymentResponse['payment_method_id']);
        }

        if (isset($paymentResponse['installments'])) {
            $title = __('Installments');
            $data[$title->__toString()] = $paymentResponse['installments'];
        }

        if (isset($paymentResponse['statement_descriptor'])) {
            $title = __('Statement descriptor');
            $data[$title->__toString()] = $paymentResponse['statement_descriptor'];
        }

        if (isset($paymentResponse['status'])) {
            $title = __('Payment status');
            $data[$title->__toString()] = ucfirst($paymentResponse['status']);
        }

        if (isset($paymentResponse['status_detail'])) {
            $title = __('Payment status detail');
            $data[$title->__toString()] = ucfirst($paymentResponse['status_detail']);
        }

        if (isset($paymentResponse['transaction_details']) && $paymentResponse['transaction_details']['redirect_url']) {
            $data['Link'] = $paymentResponse['transaction_details']['redirect_url'];
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
