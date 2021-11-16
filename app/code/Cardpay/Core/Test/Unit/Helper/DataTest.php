<?php

namespace Cardpay\Core\Test\Unit\Helper;

use Cardpay\Core\Helper\ConfigData;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Helper\Message\MessageInterface;
use Cardpay\Core\Lib\Api;
use Cardpay\Core\Logger\Logger;
use Magento\Backend\Block\Store\Switcher;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection;
use Magento\Store\Model\App\Emulation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $dataMock;

    /**
     * @var MessageInterface|MockObject
     */
    private $messageInterfaceMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var LayoutFactory|MockObject
     */
    private $layoutFactoryMock;

    /**
     * @var Factory|MockObject
     */
    private $paymentMethodFactoryMock;

    /**
     * @var Emulation|MockObject
     */
    private $appEmulationMock;

    /**
     * @var Config|MockObject
     */
    private $paymentConfigMock;

    /**
     * @var Initial|MockObject
     */
    private $initialConfigMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Collection|MockObject
     */
    private $statusFactoryMock;

    /**
     * @var OrderFactory|MockObject
     */
    private $orderFactoryMock;

    /**
     * @var Switcher|MockObject
     */
    private $switcherMock;

    /**
     * @var ComposerInformation|MockObject
     */
    private $composerInformationMock;

    /**
     * @var ResourceInterface|MockObject
     */
    private $moduleResourceMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $timezoneMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var MethodInterface|MockObject
     */
    private $methodInterfaceMock;

    protected function setUp(): void
    {
        $this->messageInterfaceMock = $this->createMock(MessageInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->method('getScopeConfig')->willReturn($this->scopeConfigMock);

        $this->layoutFactoryMock = $this->createMock(LayoutFactory::class);
        $this->paymentMethodFactoryMock = $this->createMock(Factory::class);
        $this->appEmulationMock = $this->createMock(Emulation::class);
        $this->paymentConfigMock = $this->createMock(Config::class);
        $this->initialConfigMock = $this->createMock(Initial::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->statusFactoryMock = $this->createMock(Collection::class);
        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->switcherMock = $this->createMock(Switcher::class);
        $this->composerInformationMock = $this->createMock(ComposerInformation::class);
        $this->moduleResourceMock = $this->createMock(ResourceInterface::class);
        $this->timezoneMock = $this->createMock(TimezoneInterface::class);

        $this->methodInterfaceMock = $this->createConfiguredMock(MethodInterface::class, [
            'getCode' => ConfigData::BANKCARD_PAYMENT_METHOD
        ]);

        $this->paymentMock = $this->createConfiguredMock(Payment::class, [
            'getMethodInstance' => $this->methodInterfaceMock
        ]);

        $this->orderMock = $this->createConfiguredMock(Order::class, [
            'getPayment' => $this->paymentMock
        ]);

        $this->dataMock = new Data(
            $this->messageInterfaceMock,
            $this->contextMock,
            $this->layoutFactoryMock,
            $this->paymentMethodFactoryMock,
            $this->appEmulationMock,
            $this->paymentConfigMock,
            $this->initialConfigMock,
            $this->loggerMock,
            $this->statusFactoryMock,
            $this->orderFactoryMock,
            $this->switcherMock,
            $this->composerInformationMock,
            $this->moduleResourceMock,
            $this->timezoneMock
        );
    }

    /**
     * @throws \Exception
     */
    public function testGetApiInstance()
    {
        $api = $this->dataMock->getApiInstance($this->orderMock);

        $this->assertNotNull($api);
        $this->assertInstanceOf(Api::class, $api);
    }
}