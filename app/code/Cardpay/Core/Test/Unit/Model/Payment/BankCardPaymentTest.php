<?php

namespace Cardpay\Core\Test\Unit\Model\Payment;

use Cardpay\Core\Model\Payment\BankCardPayment;
use PHPUnit\Framework\TestCase;

class BankCardPaymentTest extends TestCase
{
    /**
     * @var BankCardPayment
     */
    private $payment;

    protected function setUp(): void
    {
        $this->payment = $this->getMockBuilder(BankCardPayment::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetIpAddress()
    {
        $ip = $this->payment->getIpAddress();
        $this->assertNull($ip);
    }
}