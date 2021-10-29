<?php

namespace Cardpay\Core\Model\Preference;

use Cardpay\Core\Block\Adminhtml\System\Config\Version;
use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data as dataHelper;
use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as customerSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;

class Basic extends AbstractMethod
{
    const FAILURE_URL = 'cardpay/basic/failure';
    const NOTIFICATION_URL = 'cardpay/notifications/basic';

    protected $_orderFactory;
    protected $_checkoutSession;
    protected $_helperData;
    protected $_customerSession;
    protected $_helperImage;
    protected $_urlBuilder;
    protected $_scopeConfig;
    protected $_coreHelper;
    protected $_version;
    protected $_productMetadata;

    public function __construct(
        OrderFactory               $orderFactory,
        Session                    $checkoutSession,
        dataHelper                 $helperData,
        Image                      $helperImage,
        UrlInterface               $urlBuilder,
        ScopeConfigInterface       $scopeConfig,
        customerSession            $customerSession,
        Context                    $context,
        Registry                   $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory      $customAttributeFactory,
        Data                       $paymentData,
        Logger                     $logger,
        Version                    $version,
        ProductMetadataInterface   $productMetadata
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            []
        );
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_helperData = $helperData;
        $this->_customerSession = $customerSession;
        $this->_helperImage = $helperImage;
        $this->_urlBuilder = $urlBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->_version = $version;
        $this->_productMetaData = $productMetadata;
    }

    /**
     * @return \Magento\Sales\Model\Order
     * @throws Exception
     */
    protected function getOrderInfo()
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
        if (empty($order->getId())) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getOrderInfo'));
        }
        return $order;
    }

    /**
     * @return \Magento\Customer\Model\Customer
     * @throws Exception
     */
    protected function getCustomerInfo()
    {
        $customer = $this->_customerSession->getCustomer();
        if (empty($customer)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getCustomerInfo'));
        }
        return $customer;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getConfig()
    {
        $config = [
            'auto_return' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_AUTO_RETURN, ScopeInterface::SCOPE_STORE),
            'success_page' => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_SUCCESS_PAGE, ScopeInterface::SCOPE_STORE),
            'sponsor_id' => $this->_scopeConfig->getValue(ConfigData::PATH_SPONSOR_ID, ScopeInterface::SCOPE_STORE),
            'category_id' => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_CATEGORY, ScopeInterface::SCOPE_STORE),
            'country' => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_COUNTRY, ScopeInterface::SCOPE_STORE),
            'access_token' => $this->_scopeConfig->getValue(ConfigData::PATH_TERMINAL_PASSWORD, ScopeInterface::SCOPE_STORE),
            'binary_mode' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_BINARY_MODE, ScopeInterface::SCOPE_STORE),
            'expiration_time_preference' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_EXPIRATION_TIME_PREFERENCE, ScopeInterface::SCOPE_STORE),
            'statement_descriptor' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_STATEMENT_DESCRIPTION, ScopeInterface::SCOPE_STORE),
            'exclude_payment_methods' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE),
            'gateway_mode' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_GATEWAY_MODE, ScopeInterface::SCOPE_STORE)
        ];

        if (!$config) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getConfig'));
        }

        return $config;
    }

    /**
     * @param $order
     * @param $config
     * @return array
     * @throws Exception
     */
    protected function getItems($order, $config)
    {
        $items = [];
        $difference = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $image = $this->_helperImage->init($product, 'image');
            $items[] = [
                "id" => $item->getSku(),
                "title" => $product->getName(),
                "description" => $product->getName(),
                "picture_url" => $image->getUrl(),
                "category_id" => $config['category_id'],
                "quantity" => (int)number_format($item->getQtyOrdered(), 0, '.', ''),
                "unit_price" => (float)number_format($item->getPrice(), 2, '.', '')
            ];
        }

        $this->calculateDiscountAmount($items, $order, $config);
        $this->calculateBaseTaxAmount($items, $order, $config);
        $total_item = $this->getTotalItems($items);
        $total_item += (float)$order->getBaseShippingAmount();
        $order_amount = (float)$order->getBaseGrandTotal();
        if (!$order_amount) {
            $order_amount = (float)$order->getBasePrice() + $order->getBaseShippingAmount();
        }

        if ($total_item > $order_amount || $total_item < $order_amount) {
            $diff_price = $order_amount - $total_item;
            $difference = [
                "title" => "Difference amount of the items with a total",
                "description" => "Difference amount of the items with a total",
                "category_id" => $config['category_id'],
                "quantity" => 1,
                "unit_price" => (float)$diff_price
            ];
            $this->_helperData->log("Total items: " . $total_item, ConfigData::BASIC_LOG_PREFIX);
            $this->_helperData->log("Total order: " . $order_amount, ConfigData::BASIC_LOG_PREFIX);
            $this->_helperData->log("Difference add items: " . $diff_price, ConfigData::BASIC_LOG_PREFIX);
        }

        if (!$items) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getItems'));
        }

        return ['items' => $items, 'difference' => $difference];
    }

    /**
     * @param $arr
     * @param $order
     * @param $config
     * @throws Exception
     */
    protected function calculateDiscountAmount(&$arr, $order, $config)
    {
        if ($order->getDiscountAmount() < 0) {
            $arr[] = [
                "title" => "Store discount coupon",
                "description" => "Store discount coupon",
                "category_id" => $config['category_id'],
                "quantity" => 1,
                "unit_price" => (float)$order->getDiscountAmount()
            ];
        }

        if (empty($arr)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on calculateDiscountAmount'));
        }
    }

    /**
     * @param $arr
     * @param $order
     * @param $config
     * @throws Exception
     */
    protected function calculateBaseTaxAmount(&$arr, $order, $config)
    {
        if ($order->getBaseTaxAmount() > 0) {
            $arr[] = [
                "title" => "Store taxes",
                "description" => "Store taxes",
                "category_id" => $config['category_id'],
                "quantity" => 1,
                "unit_price" => (float)$order->getBaseTaxAmount()
            ];
        }

        if (empty($arr)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on calculateBaseTaxAmount'));
        }
    }

    /**
     * @param $items
     * @return float|int
     * @throws Exception
     */
    protected function getTotalItems($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['unit_price'] * $item['quantity'];
        }

        if (empty($total)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getTotalItems'));
        }

        return $total;
    }

    /**
     * @param $shippingAddress
     * @return array
     * @throws Exception
     */
    protected function getReceiverAddress($shippingAddress)
    {
        $receiverAddress = [
            "floor" => "-",
            "zip_code" => $shippingAddress->getPostcode(),
            "street_name" => $shippingAddress->getStreet()[0] . " - " . $shippingAddress->getCity() . " - " . $shippingAddress->getCountryId(),
            "apartment" => "-",
            "street_number" => ""
        ];

        if (!is_array($receiverAddress)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getReceiverAddress'));
        }

        return $receiverAddress;
    }

    /**
     * @param $config
     * @return array
     * @throws Exception
     */
    protected function getExcludedPaymentsMethods($config)
    {
        $excludedMethods = [];
        $excluded_payment_methods = $config['exclude_payment_methods'];
        $arr_epm = explode(",", $excluded_payment_methods);
        if (count($arr_epm) > 0) {
            foreach ($arr_epm as $m) {
                $excludedMethods[] = ["id" => $m];
            }
        }

        if (!is_array($excludedMethods)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getExcludedPaymentsMethods'));
        }

        return $excludedMethods;
    }

    /**
     * @param $order
     * @param $customer
     * @return array
     * @throws Exception
     */
    protected function getPayerInfo($order, $customer)
    {
        $result = [];

        $billingAddress = $order->getBillingAddress()->getData();
        $payment = $order->getPayment();

        $result['date_created'] = date('Y-m-d', $customer->getCreatedAtTimestamp()) . "T" . date('H:i:s', $customer->getCreatedAtTimestamp());
        $result['email'] = $customer->getId() ? htmlentities($customer->getEmail()) : htmlentities($billingAddress['email']);
        $result['first_name'] = $customer->getId() ? htmlentities($customer->getFirstname()) : htmlentities($billingAddress['firstname']);
        $result['last_name'] = $customer->getId() ? htmlentities($customer->getLastname()) : htmlentities($billingAddress['lastname']);

        if (isset($payment['additional_information']['doc_number']) && $payment['additional_information']['doc_number'] != "") {
            $result['identification'] = ["type" => "CPF", "number" => $payment['additional_information']['doc_number']];
        }

        $result['address'] = [
            "zip_code" => $billingAddress['postcode'],
            "street_name" => $billingAddress['street'] . " - " . $billingAddress['city'] . " - " . $billingAddress['country_id'],
            "street_number" => ""
        ];

        if (!is_array($result)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getPayerInfo'));
        }

        return $result;
    }

    /**
     * @param $config
     * @return array
     * @throws Exception
     */
    protected function getBackUrls($config)
    {
        $result = [];
        $successUrl = $config['success_page'] ? 'cardpay/checkout/page' : 'checkout/onepage/success';
        $result['success'] = $this->_urlBuilder->getUrl($successUrl);
        $result['pending'] = $this->_urlBuilder->getUrl($successUrl);
        $result['failure'] = $config['success_page'] ? $this->_urlBuilder->getUrl(self::FAILURE_URL) : $this->_urlBuilder->getUrl('checkout/onepage/failure');

        if (!is_array($result)) {
            throw new Exception(__('Error on create preference Basic Checkout - Exception on getBackUrls'));
        }
        return $result;
    }

    /**
     * @param $config
     * @return int|null
     */
    protected function getSponsorId($config)
    {
        $sponsor_id = $config['sponsor_id'];

        $this->_helperData->log("Sponsor_id", ConfigData::BASIC_LOG_PREFIX, $sponsor_id);
        if (!empty($sponsor_id)) {
            $this->_helperData->log("Sponsor_id identificado", ConfigData::BASIC_LOG_PREFIX, $sponsor_id);
            return (int)$sponsor_id;
        }

        return null;
    }

    /**
     * @return array
     */
    public function makePreference()
    {
        try {
            $order = $this->getOrderInfo();
            $customer = $this->getCustomerInfo();
            $config = $this->getConfig();

            $arr = [];
            $arr['external_reference'] = $order->getIncrementId();
            $arrItems = $this->getItems($order, $config);

            $arr['items'] = $arrItems['items'];
            if (!empty($arrItems['difference'])) {
                $arr['items'][] = $arrItems['difference'];
            }

            if ($order->canShip()) {
                $shippingAddress = $order->getShippingAddress();
                $shipping = $shippingAddress->getData();
                $arr['payer']['phone'] = ["area_code" => "-", "number" => $shipping['telephone']];
                $arr['shipments'] = [];
                $arr['shipments']['receiver_address'] = $this->getReceiverAddress($shippingAddress);
                $arr['items'][] = [
                    "title" => "Shipment cost",
                    "description" => "Shipment cost",
                    "category_id" => $config['category_id'],
                    "quantity" => 1,
                    "unit_price" => (float)$order->getBaseShippingAmount()
                ];
            }

            $payerInfo = $this->getPayerInfo($order, $customer);

            $arr['payer']['date_created'] = $payerInfo['date_created'];
            $arr['payer']['email'] = $payerInfo['email'];
            $arr['payer']['first_name'] = $payerInfo['first_name'];
            $arr['payer']['last_name'] = $payerInfo['last_name'];
            $arr['payer']['address'] = $payerInfo['address'];

            if (isset($payerInfo['identification'])) {
                $arr['payer']['identification'] = $payerInfo['identification'];
            }

            $backUrls = $this->getBackUrls($config);
            $arr['back_urls']['success'] = $backUrls['success'];
            $arr['back_urls']['pending'] = $backUrls['pending'];
            $arr['back_urls']['failure'] = $backUrls['failure'];

            $arr['notification_url'] = $this->_urlBuilder->getUrl(self::NOTIFICATION_URL);
            $arr['payment_methods']['excluded_payment_methods'] = $this->getExcludedPaymentsMethods($config);
            $arr['payment_methods']['installments'] = (int)$config['installments'];

            if ($config['auto_return'] == 1) {
                $arr['auto_return'] = "approved";
            }

            $sponsor_id = $this->getSponsorId($config);

            $siteId = strtoupper($config['country']);
            if ($siteId == 'MLC' || $siteId == 'MCO') {
                foreach ($arr['items'] as $key => $item) {
                    $arr['items'][$key]['unit_price'] = (int)$item['unit_price'];
                }
            }

            $arr['binary_mode'] = $config['binary_mode'] ? true : false;

            if (!empty($config['expiration_time_preference'])) {
                $arr['expires'] = true;
                $arr['expiration_date_to'] = date('Y-m-d\TH:i:s.000O', strtotime('+' . $config['expiration_time_preference'] . ' hours'));
            }

            $test_mode = true;
            if (!empty($sponsor_id) && strpos($payerInfo['email'], "@testuser.com") === false) {
                $arr['sponsor_id'] = (int)$sponsor_id;
                $test_mode = false;
            }

            $arr['metadata'] = [
                "platform" => "Magento2",
                "platform_version" => $this->_productMetaData->getVersion(),
                "module_version" => $this->_version->afterLoad(),
                "site" => $siteId,
                "checkout" => "Pro",
                "sponsor_id" => $sponsor_id,
                "test_mode" => $test_mode
            ];

            if (!empty($config['statement_descriptor'])) {
                $arr['statement_descriptor'] = $config['statement_descriptor'];
            }

            if ($config['gateway_mode']) {
                $arr['processing_modes'] = ['gateway'];
            }

            $this->_helperData->log("make array", ConfigData::BASIC_LOG_PREFIX, $arr);
            $api = $this->_helperData->getApiInstance($config['access_token']);
            $response = $api->createParams($arr);
            $this->_helperData->log("create preference result", ConfigData::BASIC_LOG_PREFIX, $response);

            return $response;

        } catch (Exception $e) {
            $this->_helperData->log('Error: ' . $e->getMessage(), ConfigData::BASIC_LOG_PREFIX);
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
