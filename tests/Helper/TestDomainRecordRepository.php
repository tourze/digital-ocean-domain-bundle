<?php

namespace DigitalOceanDomainBundle\Tests\Helper;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use Doctrine\DBAL\LockMode;

/**
 * 测试Helper类，使用组合模式模拟Repository行为
 * 实现Repository接口所需的核心方法
 */
class TestDomainRecordRepository
{
    private ?DomainRecord $findOneByResponse = null;

    public function setFindOneByResponse(?DomainRecord $response): void
    {
        $this->findOneByResponse = $response;
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?DomainRecord
    {
        return $this->findOneByResponse;
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?DomainRecord
    {
        return null;
    }

    /**
     * @return DomainRecord[]
     */
    public function findAll(): array
    {
        return [];
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

    public function getClassName(): string
    {
        return DomainRecord::class;
    }

    public function save(DomainRecord $entity, bool $flush = true): void
    {
        // Mock实现，不执行实际操作
    }

    public function remove(DomainRecord $entity, bool $flush = true): void
    {
        // Mock实现，不执行实际操作
    }

    /**
     * @return DomainRecord[]
     */
    public function findByDomainAndName(string $domain, string $name, ?string $type = null, ?int $limit = null, ?int $offset = null): array
    {
        return [];
    }
}
