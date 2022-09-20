<?php

namespace Cardpay\Core\Test\Unit\Controller\Notifications;

use Cardpay\Core\Controller\Notifications\Basic;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    /**
     * @var Basic
     */
    private $basicMock;

    /**
     * @var RequestInterface
     */
    private $requestMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);

        $this->basicMock = $this->getMockBuilder(Basic::class)
            ->setMethodsExcept(['validateForCsrf'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidateForCsrf()
    {
        $this->assertTrue($this->basicMock->validateForCsrf($this->requestMock));
    }
}