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
    private const INSTALLMENTS_MIN = 1;
    private const INSTALLMENTS_MAX = 12;

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

        $installments = [
            'currency' => $this->getCurrencySymbol(),
            'options' => $this->getInstallmentOptions($quote->getGrandTotal())
        ];

        $json = $this->resultJsonFactory->create();
        return $json->setData($installments);
    }

    private function getInstallmentOptions($grandTotal)
    {
        $options = [];

        for ($installments = self::INSTALLMENTS_MIN; $installments <= self::INSTALLMENTS_MAX; $installments++) {
            $options[] = [
                'installments' => $installments,
                'amount' => $this->formatAmount($grandTotal / $installments)
            ];
        }

        return $options;
    }

    private function formatAmount($amount)
    {
        if (empty($amount)) {
            return $amount;
        }

        return number_format($amount, 2);
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
