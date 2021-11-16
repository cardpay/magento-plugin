<?php

namespace Cardpay\Core\Test\Unit\Helper\Message;

use Cardpay\Core\Helper\Message\StatusOrderMessage;
use PHPUnit\Framework\TestCase;

class StatusOrderMessageTest extends TestCase
{
    private $statusOrderMessage;

    protected function setUp(): void
    {
        $this->statusOrderMessage = new StatusOrderMessage();
    }

    public function testGetMessageMap()
    {
        $messageMap = $this->statusOrderMessage->getMessageMap();

        $this->assertIsArray($messageMap);
        $this->assertCount(8, $messageMap);
    }
}