<?php

namespace Cardpay\Core\Test\Unit\Controller\Checkout;

use Cardpay\Core\Controller\Checkout\Installments;
use Cardpay\Core\Helper\Data;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InstallmentsTest extends TestCase
{
    private const TOTAL_AMOUNT = 1000;
    private const CURRENCY = 'EUR';

    private const INSTALLMENT_OPTIONS = [
        [
            'installments' => 2,
            'amount' => 500
        ],
        [
            'installments' => 3,
            'amount' => 333.33
        ],
        [
            'installments' => 4,
            'amount' => 250
        ],
        [
            'installments' => 5,
            'amount' => 200
        ],
        [
            'installments' => 6,
            'amount' => 166.66
        ],
        [
            'installments' => 7,
            'amount' => 142.85
        ],
        [
            'installments' => 8,
            'amount' => 125
        ],
        [
            'installments' => 9,
            'amount' => 111.11
        ],
        [
            'installments' => 10,
            'amount' => 100
        ],
        [
            'installments' => 11,
            'amount' => 90.9
        ],
        [
            'installments' => 12,
            'amount' => 83.33
        ]
    ];

    private const DUMMY_INSTALLMENTS_RESULT = [
        'currency' => self::CURRENCY,
        'options' => self::INSTALLMENT_OPTIONS
    ];

    private const INSTALLMENT_OPTIONS_RESPONSE = [
        'response' => [
            'total_amount' => self::TOTAL_AMOUNT,
            'currency' => self::CURRENCY,
            'options' => self::INSTALLMENT_OPTIONS
        ]
    ];

    /**
     * @var Installments
     */
    private $installmentsMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var Session|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var Data|MockObject
     */
    private $dataHelperMock;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactoryMock;

    /**
     * @var Quote
     */
    private $quoteMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var Json
     */
    private $resultJson;

    /**
     * @var OrderFactory
     */
    private $orderFactoryMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->getMock();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn(random_int(1, 1000));

        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->dataHelperMock = $this->createMock(Data::class);

        $this->quoteMock = $this->createMock(Quote::class);
        $this->quoteRepositoryMock = $this->createConfiguredMock(
            CartRepositoryInterface::class,
            ['getActive' => $this->quoteMock]
        );

        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);

        $this->resultJson = $this->createMock(Json::class);
        $this->resultJson->method('setData')
            ->willReturn(self::DUMMY_INSTALLMENTS_RESULT);

        $this->resultJsonFactoryMock->method('create')
            ->willReturn($this->resultJson);

        $this->orderFactoryMock = $this->createMock(OrderFactory::class);

        $this->installmentsMock = $this->getMockBuilder(Installments::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->requestMock,
                $this->checkoutSessionMock,
                $this->dataHelperMock,
                $this->resultJsonFactoryMock,
                $this->quoteRepositoryMock,
                $this->orderFactoryMock
            ])
            ->onlyMethods(['getApiResponse'])
            ->getMock();

        $this->installmentsMock->method('getApiResponse')
            ->willReturn(self::INSTALLMENT_OPTIONS_RESPONSE);
    }

    public function testExecute()
    {
        $json = $this->installmentsMock->execute();

        $this->assertEquals(self::CURRENCY, $json['currency']);
        $this->assertNotEmpty($json['options']);

        $optionsCount = count(self::INSTALLMENT_OPTIONS);
        $this->assertCount($optionsCount, $json['options']);

        for ($optionsIndex = 0; $optionsIndex < $optionsCount; $optionsIndex++) {
            $option = $json['options'][$optionsIndex];
            $expectedOption = self::INSTALLMENT_OPTIONS[$optionsIndex];

            $this->assertEquals($expectedOption['installments'], $option['installments']);
            $this->assertEquals($expectedOption['amount'], $option['amount']);
        }
    }
}