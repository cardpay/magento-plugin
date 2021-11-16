<?php

namespace Cardpay\Core\Test\Unit\Model;

use Cardpay\Core\Helper\Data as CoreHelper;
use Cardpay\Core\Model\BankCardConfigProvider;
use Cardpay\Core\Model\Payment\BankCardPayment;
use Magento\Backend\Model\Url;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BankCardConfigProviderTest extends TestCase
{
    /**
     * @var BankCardConfigProvider
     */
    private $сonfigProvider;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var PaymentHelper
     */
    private $paymentHelperMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var Session|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var Repository|MockObject
     */
    private $assetRepoMock;

    /**
     * @var ProductMetadata|MockObject
     */
    private $productMetadataMock;

    /**
     * @var CoreHelper|MockObject
     */
    private $coreHelperMock;

    /**
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $url = $this->createConfiguredMock(Url::class, ['getUrl' => random_int(0, 1000)]);
        $this->contextMock = $this->createConfiguredMock(
            Context::class,
            [
                'getUrl' => $url,
                'getRequest' => null
            ]
        );

        $paymentMock = $this->createConfiguredMock(BankCardPayment::class, ['isAvailable' => true]);
        $this->paymentHelperMock = $this->createConfiguredMock(
            PaymentHelper::class,
            ['getMethodInstance' => $paymentMock]
        );

        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);

        $quoteMock = $this->createMock(Quote::class);
        $this->checkoutSessionMock = $this->createConfiguredMock(
            Session::class,
            ['getQuote' => $quoteMock]
        );

        $storeMock = $this->createConfiguredMock(Store::class, ['getBaseUrl' => random_int(0, 1000)]);
        $this->storeManagerMock = $this->createConfiguredMock(
            StoreManagerInterface::class,
            ['getStore' => $storeMock]
        );

        $this->assetRepoMock = $this->createMock(Repository::class);
        $this->productMetadataMock = $this->createMock(ProductMetadata::class);
        $this->coreHelperMock = $this->createMock(CoreHelper::class);

        $this->сonfigProvider = new BankCardConfigProvider(
            $this->contextMock,
            $this->paymentHelperMock,
            $this->scopeConfigMock,
            $this->checkoutSessionMock,
            $this->storeManagerMock,
            $this->assetRepoMock,
            $this->productMetadataMock,
            $this->coreHelperMock
        );
    }

    public function testGetConfig()
    {
        $config = $this->сonfigProvider->getConfig();

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
        $this->assertArrayHasKey('payment', $config);
        $this->assertArrayHasKey('unlimint_bankcard', $config['payment']);
        $this->assertCount(22, $config['payment']['unlimint_bankcard']);
    }
}