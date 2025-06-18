<?php

namespace DigitalOceanDomainBundle\Tests\Entity;

use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use PHPUnit\Framework\TestCase;

class DomainRecordTest extends TestCase
{
    private DomainRecord $record;

    protected function setUp(): void
    {
        $this->record = new DomainRecord();
        $this->record->setDomainName('example.com')
            ->setRecordId(12345)
            ->setType('A')
            ->setName('www')
            ->setData('192.168.1.1')
            ->setPriority(10)
            ->setPort(80)
            ->setTtl(3600)
            ->setWeight(100)
            ->setFlags('flags')
            ->setTag('tag');
    }

    public function testGettersAndSetters(): void
    {
        $this->assertEquals('example.com', $this->record->getDomainName());
        $this->assertEquals(12345, $this->record->getRecordId());
        $this->assertEquals('A', $this->record->getType());
        $this->assertEquals('www', $this->record->getName());
        $this->assertEquals('192.168.1.1', $this->record->getData());
        $this->assertEquals(10, $this->record->getPriority());
        $this->assertEquals(80, $this->record->getPort());
        $this->assertEquals(3600, $this->record->getTtl());
        $this->assertEquals(100, $this->record->getWeight());
        $this->assertEquals('flags', $this->record->getFlags());
        $this->assertEquals('tag', $this->record->getTag());

        // 测试Config关联
        $config = new DigitalOceanConfig();
        $this->record->setConfig($config);
        $this->assertSame($config, $this->record->getConfig());
    }

    public function testToPlainArray(): void
    {
        $array = $this->record->toPlainArray();

        $this->assertArrayHasKey('domainName', $array);
        $this->assertArrayHasKey('recordId', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('data', $array);

        $this->assertEquals('example.com', $array['domainName']);
        $this->assertEquals(12345, $array['recordId']);
        $this->assertEquals('A', $array['type']);
        $this->assertEquals('www', $array['name']);
        $this->assertEquals('192.168.1.1', $array['data']);
    }

    public function testToAdminArray(): void
    {
        $array = $this->record->toAdminArray();

        // 确保管理数组包含必要的字段
        $this->assertArrayHasKey('domainName', $array);
        $this->assertArrayHasKey('recordId', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('name', $array);
    }

    public function testRetrievePlainArray(): void
    {
        $array = $this->record->retrievePlainArray();

        $this->assertArrayHasKey('domainName', $array);
        $this->assertArrayHasKey('recordId', $array);
    }

    public function testRetrieveAdminArray(): void
    {
        $array = $this->record->retrieveAdminArray();

        $this->assertArrayHasKey('domainName', $array);
        $this->assertArrayHasKey('recordId', $array);
    }

    public function testToString(): void
    {
        $this->assertEquals('www', (string)$this->record);
    }
}
