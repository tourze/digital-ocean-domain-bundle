<?php

namespace DigitalOceanDomainBundle\Tests\Entity;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DomainRecord::class)]
final class DomainRecordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new DomainRecord();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'domainName_string' => ['domainName', 'example.com'];
        yield 'recordId_integer' => ['recordId', 12345];
        yield 'type_string' => ['type', 'A'];
        yield 'name_string' => ['name', 'www'];
        yield 'data_string' => ['data', '192.168.1.1'];
        yield 'priority_integer' => ['priority', 10];
        yield 'port_integer' => ['port', 80];
        yield 'ttl_integer' => ['ttl', 3600];
        yield 'weight_integer' => ['weight', 100];
        yield 'flags_string' => ['flags', 'flags'];
        yield 'tag_string' => ['tag', 'tag'];
    }

    public function testToPlainArray(): void
    {
        $record = new DomainRecord();
        $record->setDomainName('example.com');
        $record->setRecordId(12345);
        $record->setType('A');
        $record->setName('www');
        $record->setData('192.168.1.1');

        $array = $record->toPlainArray();

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
        $record = new DomainRecord();
        $record->setDomainName('example.com');
        $record->setRecordId(12345);
        $record->setType('A');
        $record->setName('www');
        $record->setData('192.168.1.1');

        $array = $record->toAdminArray();

        // 确保管理数组包含必要的字段
        $this->assertArrayHasKey('domainName', $array);
        $this->assertArrayHasKey('recordId', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('name', $array);
    }

    public function testRetrievePlainArray(): void
    {
        $record = new DomainRecord();
        $record->setDomainName('example.com');
        $record->setRecordId(12345);
        $record->setType('A');
        $record->setName('www');
        $record->setData('192.168.1.1');

        $array = $record->retrievePlainArray();

        $this->assertArrayHasKey('domainName', $array);
        $this->assertArrayHasKey('recordId', $array);
    }

    public function testRetrieveAdminArray(): void
    {
        $record = new DomainRecord();
        $record->setDomainName('example.com');
        $record->setRecordId(12345);
        $record->setType('A');
        $record->setName('www');
        $record->setData('192.168.1.1');

        $array = $record->retrieveAdminArray();

        $this->assertArrayHasKey('domainName', $array);
        $this->assertArrayHasKey('recordId', $array);
    }

    public function testToString(): void
    {
        $record = new DomainRecord();
        $record->setName('www');

        $this->assertEquals('www', (string) $record);
    }
}
