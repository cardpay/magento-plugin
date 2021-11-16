<?php

namespace Cardpay\Core\Controller\Checkout;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Lib\Api;
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;

class Installments extends Action
{
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    public function __construct(
        Context                 $context,
        Http                    $request,
        Session                 $checkoutSession,
        Data                    $dataHelper,
        JsonFactory             $resultJsonFactory,
        CartRepositoryInterface $quoteRepository,
        OrderFactory            $orderFactory
    )
    {
        parent::__construct($context);

        $this->request = $request;
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->orderFactory = $orderFactory;
    }

    public function execute()
    {
        $cartId = $this->request->getParam('cartId');
        if (empty($cartId)) {
            $this->dataHelper->log('Cart id is not provided, unable to get installment options');
            return [];
        }

        /**
         * @var Quote $quote
         */
        $quote = $this->quoteRepository->getActive($cartId);
        if (is_null($quote)) {
            $this->dataHelper->log('Empty quote, unable to get installment options');
            return [];
        }

        $response = $this->getApiResponse($quote);
        if (empty($response)) {
            $this->dataHelper->log('Empty response, unable to get installment options');
            return [];
        }
        if (!isset($response['response']['options'])) {
            $this->dataHelper->log('No options in response, unable to get installment options');
            return [];
        }

        $installments = [
            'currency' => $this->getCurrencySymbol(),
            'options' => $this->getInstallmentOptions($response, $quote->getGrandTotal())
        ];

        $json = $this->resultJsonFactory->create();
        return $json->setData($installments);
    }

    private function getInstallmentOptions($response, $grandTotal)
    {
        $optionsResponse = $response['response']['options'];

        $options = [];
        foreach ($optionsResponse as $option) {
            if (!isset($option['installments'], $option['amount'])) {
                continue;
            }

            $installments = $option['installments'];
            $amount = $option['amount'];

            $options[] = [
                'installments' => $installments,
                'amount' => $this->formatAmount($amount)
            ];
        }

        return array_merge(
            [
                [
                    'installments' => 1,
                    'amount' => $this->formatAmount($grandTotal)
                ]
            ],
            $options
        );
    }

    private function formatAmount($amount)
    {
        if (empty($amount)) {
            return $amount;
        }

        return number_format($amount, 2);
    }

    /**
     * @throws LocalizedException|\Exception
     * @var Quote $quote
     */
    public function getApiResponse($quote)
    {
        $currency = $quote->getQuoteCurrencyCode();
        $requestId = uniqid('', true);

        $params = [
            'currency' => $currency,
            'request_id' => $requestId,
            'total_amount' => $quote->getGrandTotal()
        ];

        /**
         * @var Api
         */
        $api = $this->dataHelper->getApiInstance(
            null,
            ConfigData::PATH_BANKCARD_TERMINAL_CODE,
            ConfigData::PATH_BANKCARD_TERMINAL_PASSWORD
        );

        return $api->get('/api/installments/options_calculator', $params);
    }

    private function getCurrencySymbol()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!is_null($quote)) {
            $currencyCode = $quote->getQuoteCurrencyCode();
            $objectManager = ObjectManager::getInstance();
            $currency = $objectManager->create(CurrencyFactory::class)->create()->load($currencyCode);
            return $currency->getCurrencySymbol();
        }

        return '';
    }
}