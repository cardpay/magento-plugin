<?php

namespace Cardpay\Core\Test\Unit\Controller\Redirect;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Webapi\Rest\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedirectEventTest extends TestCase
{
    private const REDIRECT_EVENT_CLASSES = ['Success', 'Cancel', 'Decline', 'Inprogress', 'Successredirect'];

    /**
     * @var Action|MockObject
     */
    private $redirectEventMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var Data|MockObject
     */
    private $coreHelperMock;

    /**
     * @var Core|MockObject
     */
    private $coreModelMock;

    /**
     * @var Notifications|MockObject
     */
    private $notificationsMock;

    /**
     * @var Request|MockObject
     */
    private $requestMock;

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
        $this->contextMock->method('getResponse')->willReturn(null);

        $this->coreHelperMock = $this->createMock(Data::class);
        $this->coreModelMock = $this->createMock(Core::class);
        $this->notificationsMock = $this->createMock(Notifications::class);

        $this->requestMock = $this->createMock(Request::class);
    }

    public function testExecute()
    {
        foreach (self::REDIRECT_EVENT_CLASSES as $eventClass) {
            $this->redirectEventMock = $this->getMockBuilder('Cardpay\Core\Controller\Redirect\\' . $eventClass)
                ->setConstructorArgs([
                    $this->contextMock,
                    $this->coreHelperMock,
                    $this->coreModelMock,
                    $this->notificationsMock,
                    $this->requestMock
                ])
                ->onlyMethods(['getRequest'])
                ->getMock();

            $this->redirectEventMock->method('getRequest')->willReturn($this->requestMock);

            $this->redirectEventMock->execute();

            $this->assertTrue($this->redirectEventMock->isExecuted());
        }
    }
}