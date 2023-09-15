<?php

namespace Cardpay\Core\Test\Unit\Helper\Message;

use Cardpay\Core\Helper\Message\StatusDetailMessage;
use PHPUnit\Framework\TestCase;

class StatusDetailMessageTest extends TestCase
{
    private $statusDetailMessage;

    protected function setUp(): void
    {
        $this->statusDetailMessage = new StatusDetailMessage();
    }

    public function testGetMessageMap()
    {
        $messageMap = $this->statusDetailMessage->getMessageMap();

        $this->assertIsArray($messageMap);
        $this->assertCount(14, $messageMap);
    }
}