<?php

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanDomainBundle\Entity\DomainRecord;

/**
 * 测试用的 DomainRecordRepository 模拟类
 */
class TestDomainRecordRepository
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?DomainRecord
    {
        return null;
    }

    public function save(DomainRecord $record, bool $flush = false): void
    {
        // 模拟保存操作，实际不执行任何操作
    }

    public function remove(DomainRecord $record, bool $flush = false): void
    {
        // 模拟删除操作，实际不执行任何操作
    }

    public function flush(): void
    {
        // 模拟flush操作，实际不执行任何操作
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return DomainRecord[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return [];
    }
}
