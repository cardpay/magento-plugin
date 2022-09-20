<?php

namespace Cardpay\Core\Test\Unit\Controller\Checkout;

use Cardpay\Core\Controller\Checkout\Page;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Topics\Payment as PaymentNotification;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PageTest extends TestCase
{
    /**
     * @var Page
     */
    private $page;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var OrderFactory|MockObject
     */
    private $orderFactoryMock;

    /**
     * @var OrderSender|MockObject
     */
    private $orderSenderMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var Data
     */
    private $helperDataMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Core
     */
    private $coreMock;

    /**
     * @var CatalogSession|MockObject
     */
    private $catalogSessionMock;

    /**
     * @var PaymentNotification
     */
    private $paymentNotificationMock;

    /**
     * @var RedirectInterface
     */
    protected $redirectMock;

    /**
     * @var ResponseInterface
     */
    protected $responseMock;

    protected function setUp(): void
    {
        $this->redirectMock = $this->createMock(RedirectInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->method('getRedirect')->willReturn($this->redirectMock);
        $this->contextMock->method('getResponse')->willReturn($this->responseMock);

        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);

        $this->paymentMock = $this->createMock(Payment::class);

        $this->orderMock = $this->createMock(Order::class);
        $this->orderMock->method('loadByIncrementId')->willReturn($this->orderMock);
        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);

        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->orderFactoryMock->method('create')->willReturn($this->orderMock);

        $this->orderSenderMock = $this->createMock(OrderSender::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helperDataMock = $this->createMock(Data::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->coreMock = $this->createMock(Core::class);
        $this->catalogSessionMock = $this->createMock(CatalogSession::class);
        $this->paymentNotificationMock = $this->createMock(PaymentNotification::class);

        $this->page = new Page(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->orderSenderMock,
            $this->loggerMock,
            $this->helperDataMock,
            $this->scopeConfigMock,
            $this->coreMock,
            $this->catalogSessionMock,
            $this->paymentNotificationMock
        );
    }

    public function testExecute()
    {
        $response = $this->page->execute();

        $this->assertNotNull($response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}