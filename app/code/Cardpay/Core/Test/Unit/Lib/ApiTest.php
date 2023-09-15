<?php

namespace Cardpay\Core\Test\Unit\Lib;

use Cardpay\Core\Lib\Api;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private const HTTP_METHODS = ['post', 'get', 'patch', 'put', 'delete'];

    /**
     * @var Api
     */
    private $api;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->api = new Api((string)random_int(1, 1000), (string)random_int(1, 1000));
    }

    public function testExecute()
    {
        foreach (self::HTTP_METHODS as $httpMethod) {
            $this->expectException(LocalizedException::class);
            $this->expectExceptionMessage('Invalid auth response');

            $this->api->$httpMethod('dummy-url', '');
        }
    }
}