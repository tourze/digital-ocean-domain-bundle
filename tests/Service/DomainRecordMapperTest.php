<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Service\DomainRecordMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * DomainRecordMapper 单元测试
 *
 * @internal
 */
#[CoversClass(DomainRecordMapper::class)]
final class DomainRecordMapperTest extends TestCase
{
    private DomainRecordMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new DomainRecordMapper();
    }

    public function testUpdateRecordFromDataWithCompleteData(): void
    {
        $record = new DomainRecord();
        $recordData = [
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'priority' => 10,
            'port' => 443,
            'ttl' => 3600,
            'weight' => 100,
            'flags' => '0',
            'tag' => 'issue',
        ];

        $this->mapper->updateRecordFromData($record, $recordData, 'example.com');

        $this->assertSame('example.com', $record->getDomainName());
        $this->assertSame(123, $record->getRecordId());
        $this->assertSame('A', $record->getType());
        $this->assertSame('www', $record->getName());
        $this->assertSame('192.168.1.1', $record->getData());
        $this->assertSame(10, $record->getPriority());
        $this->assertSame(443, $record->getPort());
        $this->assertSame(3600, $record->getTtl());
        $this->assertSame(100, $record->getWeight());
        $this->assertSame('0', $record->getFlags());
        $this->assertSame('issue', $record->getTag());
    }

    public function testUpdateRecordFromDataWithMinimalData(): void
    {
        $record = new DomainRecord();
        $recordData = [
            'id' => 456,
        ];

        $this->mapper->updateRecordFromData($record, $recordData, 'test.com');

        $this->assertSame('test.com', $record->getDomainName());
        $this->assertSame(456, $record->getRecordId());
    }

    public function testUpdateRecordFromDataWithNonNumericTypeValues(): void
    {
        $record = new DomainRecord();
        // Initialize required fields first to avoid uninitialized property errors
        $record->setType('INITIAL');
        $record->setName('initial');
        $record->setData('initial');

        $recordData = [
            'id' => 789,
            'type' => 123, // non-string - will be converted to empty string
            'name' => ['array'], // non-string - will be converted to empty string
            'data' => null, // null - will be converted to empty string (isset returns false for null)
            'priority' => 'high', // non-numeric - will be null
            'port' => 'invalid', // non-numeric - will be null
            'ttl' => [], // non-numeric - will be null
            'weight' => new \stdClass(), // non-numeric - will be null
            'flags' => 123, // non-string - will be null
            'tag' => [], // non-string - will be null
        ];

        $this->mapper->updateRecordFromData($record, $recordData, 'example.org');

        $this->assertSame('example.org', $record->getDomainName());
        $this->assertSame(789, $record->getRecordId());
        $this->assertSame('', $record->getType());
        $this->assertSame('', $record->getName());
        // data remains as 'initial' since null doesn't pass isset() check
        $this->assertSame('initial', $record->getData());
        $this->assertNull($record->getPriority());
        $this->assertNull($record->getPort());
        $this->assertNull($record->getTtl());
        $this->assertNull($record->getWeight());
        $this->assertNull($record->getFlags());
        $this->assertNull($record->getTag());
    }

    public function testUpdateRecordFromDataThrowsExceptionForNonNumericId(): void
    {
        $record = new DomainRecord();
        $recordData = [
            'id' => 'invalid',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Record ID must be numeric');

        $this->mapper->updateRecordFromData($record, $recordData, 'example.com');
    }

    public function testUpdateRecordFromDataWithNumericStringId(): void
    {
        $record = new DomainRecord();
        $recordData = [
            'id' => '999',
        ];

        $this->mapper->updateRecordFromData($record, $recordData, 'example.com');

        $this->assertSame(999, $record->getRecordId());
    }

    public function testUpdateRecordFromDataWithFloatValues(): void
    {
        $record = new DomainRecord();
        $recordData = [
            'id' => 100.5,
            'priority' => 10.9,
            'port' => 443.1,
            'ttl' => 3600.7,
            'weight' => 50.3,
        ];

        $this->mapper->updateRecordFromData($record, $recordData, 'example.com');

        $this->assertSame(100, $record->getRecordId());
        $this->assertSame(10, $record->getPriority());
        $this->assertSame(443, $record->getPort());
        $this->assertSame(3600, $record->getTtl());
        $this->assertSame(50, $record->getWeight());
    }

    public function testUpdateRecordFromDataPreservesExistingUnsetFields(): void
    {
        $record = new DomainRecord();
        $record->setDomainName('old.com');
        $record->setRecordId(111);
        $record->setType('CNAME');
        $record->setPriority(5);

        $recordData = [
            'id' => 222,
            'type' => 'A',
            // priority not set - will not be overwritten
        ];

        $this->mapper->updateRecordFromData($record, $recordData, 'new.com');

        $this->assertSame('new.com', $record->getDomainName());
        $this->assertSame(222, $record->getRecordId());
        $this->assertSame('A', $record->getType());
        // Priority should remain unchanged since it wasn't in the update data
        $this->assertSame(5, $record->getPriority());
    }
}
