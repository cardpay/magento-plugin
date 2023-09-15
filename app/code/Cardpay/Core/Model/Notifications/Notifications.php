<?php

namespace Cardpay\Core\Model\Notifications;

use Cardpay\Core\Exceptions\UnlimitBaseException;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Helper\Response;
use Cardpay\Core\Model\Notifications\Topics\MerchantOrder;
use Cardpay\Core\Model\Notifications\Topics\Payment;
use Cardpay\Core\Model\Payment\BankCardPayment;
use Cardpay\Core\Model\Payment\BoletoPayment;
use Cardpay\Core\Model\Payment\GpayPayment;
use Cardpay\Core\Model\Payment\MbWayPayment;
use Cardpay\Core\Model\Payment\MultibancoPayment;
use Cardpay\Core\Model\Payment\PaypalPayment;
use Cardpay\Core\Model\Payment\PixPayment;
use Cardpay\Core\Model\Payment\SepaInstantPayment;
use Cardpay\Core\Model\Payment\SpeiPayment;
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
     * @param  cpHelper  $cpHelper
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  MerchantOrder  $merchantOrder
     * @param  Payment  $payment
     * @param  OrderFactory  $orderFactory
     */
    public function __construct(
        cpHelper $cpHelper,
        ScopeConfigInterface $scopeConfig,
        MerchantOrder $merchantOrder,
        Payment $payment,
        OrderFactory $orderFactory
    ) {
        $this->_cpHelper = $cpHelper;
        $this->scopeConfig = $scopeConfig;
        $this->merchantOrder = $merchantOrder;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param  Request  $request
     * @return array
     * @throws UnlimitBaseException
     */
    public function getRequestParams($request)
    {
        $this->validateSignature($request);

        $this->_cpHelper->log('Received notification', self::LOG_NAME, $request);
        $bodyDecoded = $this->getBodyDecoded($request);
        $this->_cpHelper->log('Received notification', self::LOG_NAME, $bodyDecoded);

        if (isset($bodyDecoded['refund_data'])) {
            $type = 'refund_data';
        } else {
            $type = 'payment_data';
        }

        if (empty($type) || !isset($bodyDecoded['payment_method'])) {
            throw new UnlimitBaseException(__('Invalid Unlimit callback'), null, Response::HTTP_BAD_REQUEST);

        }
        $method = $bodyDecoded['payment_method'];

        $requestData = $bodyDecoded[$type];
        if (!isset($requestData['id'])) {
            throw new UnlimitBaseException(__('Request param ID not found'), null, Response::HTTP_BAD_REQUEST);
        }

        $id = $requestData['id'];

        return ['id' => $id, 'method' => $method, 'type' => $type];
    }

    /**
     * @param  Request  $request
     * @throws UnlimitBaseException
     */
    public function validateSignature($request)
    {
        $callbackSignatureHeader = $request->getHeaders(self::HEADER_SIGNATURE, null);
        if (is_null($callbackSignatureHeader)) {
            throw new UnlimitBaseException(__('Could not get Unlimit callback signature'), null,
                Response::HTTP_BAD_REQUEST);
        }

        $bodyDecoded = $this->getBodyDecoded($request);
        if (!isset($bodyDecoded['merchant_order']['id'])) {
            throw new UnlimitBaseException(__('Invalid Unlimit callback'), null, Response::HTTP_BAD_REQUEST);
        }

        $orderId = $bodyDecoded['merchant_order']['id'];
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        if (BankCardPayment::isBankCardPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_BANKCARD_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (BoletoPayment::isBoletoPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_BOLETO_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (PixPayment::isPixPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_PIX_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (PaypalPayment::isPaypalPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_PAYPAL_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (GpayPayment::isGpayPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_GPAY_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (SepaInstantPayment::isSepaInstantPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_SEPA_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (SpeiPayment::isSpeiPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_SPEI_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (MultibancoPayment::isMultibancoPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_MULTIBANCO_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } elseif (MbWayPayment::isMbWayPaymentMethod($order)) {
            $callbackSecret = $this->scopeConfig->getValue(
                ConfigData::PATH_MBWAY_CALLBACK_SECRET,
                ScopeInterface::SCOPE_STORE);
        } else {
            throw new UnlimitBaseException(__('Unable to detect Unlimit callback secret'), null,
                Response::HTTP_BAD_REQUEST);
        }

        $generatedSignature = hash('sha512', $request->getContent().$callbackSecret);

        if ($generatedSignature !== $callbackSignatureHeader->getFieldValue()) {
            throw new UnlimitBaseException(__('Unlimit callback signature does not match'), null,
                Response::HTTP_BAD_REQUEST);
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
     * @param  cpHelper  $cpHelper
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
