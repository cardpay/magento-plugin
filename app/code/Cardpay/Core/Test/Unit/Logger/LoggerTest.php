<?php

namespace Cardpay\Core\Test\Unit\Logger;

use Cardpay\Core\Logger\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private const LOGGER_NAME = 'dummy-logger';

    /**
     * @var Logger
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger(self::LOGGER_NAME);
    }

    public function testName()
    {
        $loggerName = 'logger-' . random_int(1, 1000);
        $this->logger->setName($loggerName);

        $this->assertEquals($loggerName, $this->logger->getName());
    }
}