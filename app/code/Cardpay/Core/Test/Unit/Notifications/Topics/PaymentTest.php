<?php

namespace Cardpay\Core\Test\Unit\Notifications\Topics;

use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Model\Notifications\Topics\Payment;
use Cardpay\Core\Model\Notifications\Topics\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var cpHelper|MockObject
     */
    protected $cpHelperMock;

    /**
     * @var Transaction|MockObject
     */
    private $transaction;

    /**
     * @var InvoiceSender|MockObject
     */
    private $invoiceSender;

    /**
     * @var Invoice|MockObject
     */
    private $invoiceMock;

    protected function setUp(): void
    {
        $this->invoiceMock = $this->createMock(Invoice::class);

        $this->orderMock = $this->createMock(Order::class);
        $this->orderMock->method('hasInvoices')->willReturn(false);
        $this->orderMock->method('prepareInvoice')->willReturn($this->invoiceMock);

        $this->transaction = $this->createMock(Transaction::class);
        $this->cpHelperMock = $this->createMock(cpHelper::class);
        $this->invoiceSender = $this->createMock(InvoiceSender::class);

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->setMethodsExcept(['createInvoice', 'setCpHelper', 'setTransaction', 'setInvoiceSender', 'isValidResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMock->setCpHelper($this->cpHelperMock);
        $this->paymentMock->setTransaction($this->transaction);
        $this->paymentMock->setInvoiceSender($this->invoiceSender);
    }

    /**
     * @throws LocalizedException
     */
    public function testCreateInvoice()
    {
        $isInvoiceCreated = $this->paymentMock->createInvoice(
            $this->orderMock,
            ['id' => random_int(1, 10000)]
        );

        $this->assertTrue($isInvoiceCreated);
    }

    public function testIsValidResponse()
    {
        $this->assertTrue($this->paymentMock->isValidResponse(['status' => 200]));
        $this->assertTrue($this->paymentMock->isValidResponse(['status' => 201]));

        $this->assertTrue($this->paymentMock->isValidResponse(
            [
                'status' => 202,
                'response' => ['dummy-response']
            ]
        ));

        $this->assertFalse($this->paymentMock->isValidResponse([]));
    }
}