<?php

namespace Cardpay\Core\Test\Unit\Preference;

use Cardpay\Core\Model\Preference\Basic;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    /**
     * @var Basic|MockObject
     */
    private $basicMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->basicMock = $this->getMockBuilder(Basic::class)
            ->setMethodsExcept(['getConfig', 'setScopeConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->basicMock->setScopeConfig($this->scopeConfigMock);
    }

    public function testGetConfig()
    {
        $config = $this->basicMock->getConfig();

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
        $this->assertCount(11, $config);
    }
}