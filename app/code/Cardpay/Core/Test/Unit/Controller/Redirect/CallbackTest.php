<?php

namespace Cardpay\Core\Test\Unit\Controller\Redirect;

use Cardpay\Core\Controller\Redirect\Callback;
use Cardpay\Core\Helper\Data;
use Cardpay\Core\Model\Core;
use Cardpay\Core\Model\Notifications\Notifications;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Webapi\Rest\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CallbackTest extends TestCase
{
    private const ACTIONS = ['success', 'inprogress', 'cancel', 'decline'];

    /**
     * @var Callback
     */
    private $callbackMock;

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
        $this->contextMock->method('getResponse')->willReturn($this->responseMock);

        $this->coreHelperMock = $this->createMock(Data::class);
        $this->coreModelMock = $this->createMock(Core::class);
        $this->notificationsMock = $this->createMock(Notifications::class);

        $this->requestMock = $this->createMock(Request::class);

        $this->callbackMock = $this->getMockBuilder(Callback::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->coreHelperMock,
                $this->coreModelMock,
                $this->notificationsMock,
                $this->requestMock
            ])
            ->onlyMethods(['getRequest'])
            ->getMock();

        $this->callbackMock->method('getRequest')->willReturn($this->requestMock);
    }

    public function testExecute()
    {
        foreach (self::ACTIONS as $action) {
            $this->requestMock->method('getParams')->willReturn(['action' => $action]);
            $this->callbackMock->execute();

            $this->assertTrue($this->callbackMock->isExecuted());
        }
    }
}