<?php

namespace Cardpay\Core\Test\Unit\Notifications;

use Cardpay\Core\Helper\Data as cpHelper;
use Cardpay\Core\Model\Notifications\Notifications;
use Magento\Framework\Webapi\Rest\Request;
use PHPUnit\Framework\TestCase;

class NotificationsTest extends TestCase
{
    /**
     * @var Notifications
     */
    private $notifications;

    /**
     * @var cpHelper
     */
    private $cpHelper;

    protected function setUp(): void
    {
        $this->cpHelper = $this->createMock(cpHelper::class);

        $this->notifications = $this->getMockBuilder(Notifications::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateSignature'])
            ->getMock();

        $this->notifications->setCpHelper($this->cpHelper);
    }

    /**
     * @throws \Exception
     */
    public function testGetRequestParams()
    {
        $paymentId = random_int(1, 1000);
        $paymentMethod = 'BANKCARD';
        $type = 'payment_data';

        $requestMock = $this->createConfiguredMock(
            Request::class,
            [
                'getBodyParams' => [
                    $type => [
                        'id' => $paymentId
                    ],
                    'payment_method' => $paymentMethod
                ]
            ]
        );

        $requestParams = $this->notifications->getRequestParams($requestMock);

        $this->assertNotNull($requestParams);
        $this->assertIsArray($requestParams);
        $this->assertCount(3, $requestParams);
        $this->assertEquals($paymentId, $requestParams['id']);
        $this->assertEquals($paymentMethod, $requestParams['method']);
        $this->assertEquals($type, $requestParams['type']);
    }
}