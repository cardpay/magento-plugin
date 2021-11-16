<?php

namespace Cardpay\Core\Model\Notifications;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\Notifications\Topics\MerchantOrder;
use Cardpay\Core\Model\Notifications\Topics\Payment;
use Cardpay\Core\Model\Payment\BankCardPayment;
use Cardpay\Core\Model\Payment\BoletoPayment;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;

class Notifications
{
    public const LOG_NAME = 'Notifications';
    public const TYPE_NOTIFICATION_WEBHOOK = 'webhook';
    public const HEADER_SIGNATURE = 'Signature';

    /**
     * @var MerchantOrder
     */
    protected $merchantOrder;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var cpHelper
     */
    protected $_cpHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @param cpHelper $cpHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param MerchantOrder $merchantOrder
     * @param Payment $payment
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        cpHelper             $cpHelper,
        ScopeConfigInterface $scopeConfig,
        MerchantOrder        $merchantOrder,
        Payment              $payment,
        OrderFactory         $orderFactory
    )
    {
        $this->_cpHelper = $cpHelper;
        $this->scopeConfig = $scopeConfig;
        $this->merchantOrder = $merchantOrder;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getRequestParams($request)
    {
        $this->validateSignature($request);

        $bodyDecoded = $this->getBodyDecoded($request);
        $this->_cpHelper->log('Received notification', self::LOG_NAME, $bodyDecoded);

        $type = '';
        if (isset($bodyDecoded['refund_data'])) {
            $type = 'refund_data';
        } elseif (isset($bodyDecoded['recurring_data'])) {
            $type = 'recurring_data';
        } elseif (isset($bodyDecoded['payment_data'])) {
            $type = 'payment_data';
        }

        if (empty($type) || !isset($bodyDecoded['payment_method'])) {
            throw new Exception(__('Invalid Unlimint callback'), Response::HTTP_BAD_REQUEST);
        }
        $method = $bodyDecoded['payment_method'];

        $requestData = $bodyDecoded[$type];
        if (!isset($requestData['id'])) {
            throw new Exception(__('Request param ID not found'), Response::HTTP_BAD_REQUEST);
        }

        $id = $requestData['id'];

        return ['id' => $id, 'method' => $method, 'type' => $type];
    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function validateSignature($request)
    {
        $callbackSignatureHeader = $request->getHeaders(self::HEADER_SIGNATURE, null);
        if (is_null($callbackSignatureHeader)) {
            throw new Exception(__('Could not get Unlimint callback signature'), Response::HTTP_BAD_REQUEST);
        }

        $bodyDecoded = $this->getBodyDecoded($request);
        if (!isset($bodyDecoded['merchant_order']['id'])) {
            throw new Exception(__('Invalid Unlimint callback'), Response::HTTP_BAD_REQUEST);
        }

        $orderId = $bodyDecoded['merchant_order']['id'];
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        if (BankCardPayment::isBankCardPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(ConfigData::PATH_BANKCARD_CALLBACK_SECRET, ScopeInterface::SCOPE_STORE);
        } else if (BoletoPayment::isBoletoPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(ConfigData::PATH_BOLETO_CALLBACK_SECRET, ScopeInterface::SCOPE_STORE);
        } else {
            throw new Exception(__('Unable to detect Unlimint callback secret'), Response::HTTP_BAD_REQUEST);
        }

        $generatedSignature = hash('sha512', $request->getContent() . $callbackSecret);

        if ($generatedSignature !== $callbackSignatureHeader->getFieldValue()) {
            throw new Exception(__('Unlimint callback signature does not match'), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @return Payment
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

    /**
     * @param cpHelper $cpHelper
     */
    public function setCpHelper(cpHelper $cpHelper): void
    {
        $this->_cpHelper = $cpHelper;
    }

    private function getBodyDecoded($request)
    {
        if (is_null($request)) {
            return [];
        }

        return json_decode($request->getContent(), true);
    }
}
