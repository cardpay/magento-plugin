<?php

namespace Cardpay\Core\Controller\Checkout;

use Cardpay\Core\Controller\ParamsContainer\ParamContextContainer;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;

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

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        ParamContextContainer   $paramContainer,
        Http                    $request,
        Session                 $checkoutSession,
        JsonFactory             $resultJsonFactory,
        CartRepositoryInterface $quoteRepository,
        OrderFactory            $orderFactory
    ) {
        parent::__construct($paramContainer->getContext());
        $this->dataHelper = $paramContainer->getData();
        $this->scopeConfig = $paramContainer->getScopeConfig();

        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->orderFactory = $orderFactory;
    }

    public function execute()
    {
        $cartId = $this->request->getParam('cartId');
        if (empty($cartId)) {
            $this->dataHelper->log(
                'Cart id is not provided, unable to get installment options'
            );
            return [];
        }

        /**
         * @var Quote $quote
         */
        $quote = $this->quoteRepository->getActive($cartId);
        if (is_null($quote)) {
            $this->dataHelper->log(
                'Empty quote, unable to get installment options'
            );
            return [];
        }

        $installments = [
            'currency' => $this->getCurrencySymbol(),
            'options' => $this->getInstallmentOptions($quote->getGrandTotal())
        ];

        $json = $this->resultJsonFactory->create();
        return $json->setData($installments);
    }

    private function appendInstallmentOption(&$result, $value, $range)
    {
        if (in_array($value, $range)) {
            $result[] = $value;
        }
    }

    private function getInstallmentRange($settings)
    {
        $result = [];

        $range = $this->getAllowedInstallmentRange();

        foreach (explode(',', trim($settings)) as $value) {
            if (strpos($value, '-') !== false) {
                $value = explode('-', $value);
                if (count($value) !== 2) {
                    continue;
                }
                for ($i = (int)$value[0]; $i <= ((int)$value[1]); $i++) {
                    $this->appendInstallmentOption($result, $i, $range);
                }
            } else {
                $this->appendInstallmentOption($result, (int)$value, $range);
            }
        }

        return $this->nolmalizeInstallmentArray($result);
    }

    private function getAllowedInstallmentRange()
    {
        $type = $this->scopeConfig->getValue(ConfigData::PATH_BANKCARD_INSTALLMENT_TYPE, ScopeInterface::SCOPE_STORE);
        return ($type == 'IF') ? [3, 6, 9, 12, 18] : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    }

    private function nolmalizeInstallmentArray($array)
    {
        $array[] = 1;
        $result = array_unique($array);
        sort($result);
        return (empty($result)) ? [1] : $result;
    }

    private function getInstallmentOptions($grandTotal)
    {
        $options = [];
        $range = $this->getInstallmentRange(
            $this->scopeConfig->getValue(
                ConfigData::PATH_BANKCARD_MAXIMUM_ACCEPTED_INSTALLMENTS,
                ScopeInterface::SCOPE_STORE)
        );
        $minAmount = $this->scopeConfig->getValue(
            ConfigData::PATH_BANKCARD_MINIMUM_INSTALLMENT_AMOUNT,
            ScopeInterface::SCOPE_STORE
        );

        foreach ($range as $installments) {
            $amount = $grandTotal / $installments;
            if (
                ($amount < $minAmount) &&
                ($installments > 1) &&
                ($minAmount > 0)) {
                break;
            }
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
