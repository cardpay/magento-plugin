<?php

namespace Cardpay\Core\Test\Unit\Helper\Message;

use Cardpay\Core\Helper\Message\StatusMessage;
use PHPUnit\Framework\TestCase;

class StatusMessageTest extends TestCase
{
    private $statusMessage;

    protected function setUp(): void
    {
        $this->statusMessage = new StatusMessage();
    }

    public function testGetMessageMap()
    {
        $messageMap = $this->statusMessage->getMessageMap();

        $this->assertIsArray($messageMap);
        $this->assertCount(7, $messageMap);
    }
}