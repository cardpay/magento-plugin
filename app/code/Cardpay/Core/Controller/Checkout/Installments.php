<?php

namespace Cardpay\Core\Controller\Checkout;

use Cardpay\Core\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;

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

    public function __construct(
        Context                 $context,
        Http                    $request,
        Session                 $checkoutSession,
        Data                    $dataHelper,
        JsonFactory             $resultJsonFactory,
        CartRepositoryInterface $quoteRepository
    )
    {
        parent::__construct($context);

        $this->request = $request;
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        $cartId = $this->request->getParam('cartId');
        if (empty($cartId)) {
            $this->dataHelper->log('Cart id is not provided, unable to get installment options');
            return array();
        }

        /**
         * @var \Magento\Quote\Model\Quote $quote
         */
        $quote = $this->quoteRepository->getActive($cartId);
        if (is_null($quote)) {
            $this->dataHelper->log('Empty quote, unable to get installment options');
            return array();
        }

        $response = $this->getApiResponse($quote);
        if (empty($response)) {
            $this->dataHelper->log('Empty response, unable to get installment options');
            return array();
        }
        if (!isset($response['response']['options'])) {
            $this->dataHelper->log('No options in response, unable to get installment options');
            return array();
        }

        $installmentsOptions = array_merge(
            array(
                array(
                    'installments' => 1,
                    'amount' => (float)$quote->getGrandTotal()   // cast to float is required to remove trailing zeros (00)
                )
            ),
            $response['response']['options']
        );

        $installments = array(
            'currency' => $this->getCurrencySymbol(),
            'options' => $installmentsOptions
        );

        $jsonFactory = $this->resultJsonFactory->create();
        return $jsonFactory->setData($installments);
    }

    /**
     * @var \Magento\Quote\Model\Quote $quote
     */
    private function getApiResponse($quote)
    {
        $currency = $quote->getQuoteCurrencyCode();
        $requestId = uniqid('', true);

        $params = array(
            'currency' => $currency,
            'request_id' => $requestId,
            'total_amount' => $quote->getGrandTotal()
        );

        $api = $this->dataHelper->getApiInstance();
        return $api->get('/api/installments/options_calculator', $params);
    }

    private function getCurrencySymbol()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!is_null($quote)) {
            $currencyCode = $quote->getQuoteCurrencyCode();
            $objectManager = ObjectManager::getInstance();
            $currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode);
            return $currency->getCurrencySymbol();
        }

        return '';
    }
}