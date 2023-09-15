<?php

namespace Cardpay\Core\Test\Unit\Model;

use Cardpay\Core\Helper\Data as CoreHelper;
use Cardpay\Core\Model\BoletoConfigProvider;
use Cardpay\Core\Model\Payment\BoletoPayment;
use Magento\Backend\Model\Url;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BoletoConfigProviderTest extends TestCase
{
    /**
     * @var BoletoConfigProvider
     */
    private $customTicketConfigProvider;

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
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);

        $url = $this->createConfiguredMock(Url::class, ['getUrl' => random_int(0, 1000)]);
        $this->contextMock = $this->createConfiguredMock(
            Context::class,
            [
                'getUrl' => $url,
                'getRequest' => null
            ]
        );

        $paymentMock = $this->createConfiguredMock(BoletoPayment::class, ['isAvailable' => true]);
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

        $this->customTicketConfigProvider = new BoletoConfigProvider(
            $this->contextMock,
            $this->paymentHelperMock,
            $this->checkoutSessionMock,
            $this->scopeConfigMock,
            $this->storeManagerMock,
            $this->assetRepoMock,
            $this->coreHelperMock,
            $this->productMetadataMock
        );
    }

    public function testGetConfig()
    {
        $config = $this->customTicketConfigProvider->getConfig();

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
        $this->assertArrayHasKey('payment', $config);
        $this->assertArrayHasKey('unlimit_boleto', $config['payment']);
        $this->assertCount(14, $config['payment']['unlimit_boleto']);
    }
}
