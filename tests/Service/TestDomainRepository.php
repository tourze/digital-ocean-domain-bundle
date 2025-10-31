<?php

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanDomainBundle\Entity\Domain;

/**
 * 测试用的 DomainRepository 模拟类
 */
class TestDomainRepository
{
    /** @var list<mixed> */
    private array $findAllResponse = [];

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?Domain
    {
        return null;
    }

    public function save(Domain $domain, bool $flush = false): void
    {
        // 模拟保存操作，实际不执行任何操作
    }

    public function remove(Domain $domain, bool $flush = false): void
    {
        // 模拟删除操作，实际不执行任何操作
    }

    public function flush(): void
    {
        // 模拟flush操作，实际不执行任何操作
    }

    /**
     * @return list<mixed>
     */
    public function findAll(): array
    {
        return $this->findAllResponse;
    }

    /**
     * @param list<mixed> $response
     */
    public function setFindAllResponse(array $response): void
    {
        $this->findAllResponse = $response;
    }
}
