<?php

namespace Cardpay\Core\Model\Notifications;

use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\Notifications\Topics\MerchantOrder;
use Cardpay\Core\Model\Notifications\Topics\Payment;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Notifications
{
    const LOG_NAME = 'Notifications';
    const TYPE_NOTIFICATION_WEBHOOK = 'webhook';
    const HEADER_SIGNATURE = 'Signature';

    protected $merchant_order;
    protected $payment;
    protected $_cpHelper;
    protected $_scopeConfig;

    /**
     * Notifications constructor.
     * @param cpHelper $cpHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param MerchantOrder $merchantOrder
     * @param Payment $payment
     */
    public function __construct(cpHelper $cpHelper, ScopeConfigInterface $scopeConfig, MerchantOrder $merchantOrder, Payment $payment)
    {
        $this->_cpHelper = $cpHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->merchant_order = $merchantOrder;
        $this->payment = $payment;
    }

    /**
     * @param $request
     * @return array
     * @throws Exception
     */
    public function getRequestParams($request)
    {
        $this->validateSignature($request);

        $params = $request->getBodyParams();
        $this->_cpHelper->log('Received notification', self::LOG_NAME, $params);

        $type = '';
        if (isset($params['refund_data'])) {
            $type = 'refund_data';
        } elseif (isset($params['recurring_data'])) {
            $type = 'recurring_data';
        } elseif (isset($params['payment_data'])) {
            $type = 'payment_data';
        }

        if (!isset($params['payment_method'], $params[$type])) {
            throw new Exception(__('Invalid Unlimint callback'), Response::HTTP_BAD_REQUEST);
        }

        $requestData = $params[$type];
        if (!isset($requestData['id'])) {
            throw new Exception(__('Request param ID not found'), Response::HTTP_BAD_REQUEST);
        }

        $method = $params['payment_method'];
        $id = $requestData['id'];

        return ['id' => $id, 'method' => $method, 'type' => $type];
    }

    private function validateSignature($request)
    {
        $callbackSignatureHeader = $request->getHeaders(self::HEADER_SIGNATURE, null);
        if (is_null($callbackSignatureHeader)) {
            throw new Exception(__('Could not get Unlimint callback signature'), Response::HTTP_BAD_REQUEST);
        }

        $body = $request->getContent();
        $callbackSecret = $this->_cpHelper->getCallbackSecret();
        $generatedSignature = hash('sha512', $body . $callbackSecret);

        if ($generatedSignature !== $callbackSignatureHeader->getFieldValue()) {
            throw new Exception(__('Unlimint callback signature does not match'), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param $request
     * @return MerchantOrder|Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @param $class
     * @param $request
     * @return mixed
     */
    public function getPaymentInformation($class, $request)
    {
        return $class->getPaymentData($request['id'], $request['type']);
    }
}
