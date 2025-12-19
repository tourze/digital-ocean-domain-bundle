<?php

namespace DigitalOceanDomainBundle\Repository;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 域名记录仓库
 *
 * @extends ServiceEntityRepository<DomainRecord>
 */
#[AsRepository(entityClass: DomainRecord::class)]
class DomainRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainRecord::class);
    }

    /**
     * 按域名和名称模糊查询记录
     *
     * @param string      $domain 域名
     * @param string      $name   记录名称（模糊匹配）
     * @param string|null $type   记录类型（精确匹配）
     * @param int|null    $limit  限制结果数量
     * @param int|null    $offset 起始位置
     *
     * @return list<DomainRecord>
     */
    public function findByDomainAndName(string $domain, string $name, ?string $type = null, ?int $limit = null, ?int $offset = null): array
    {
        $this->validateDomainAndNameParameters($domain, $name, $limit, $offset);

        $qb = $this->createQueryBuilder('r')
            ->where('r.domainName = :domain')
            ->andWhere('r.name LIKE :name')
            ->setParameter('domain', $domain)
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('r.recordId', 'ASC')
        ;

        $this->applyOptionalFilters($qb, $type, $limit, $offset);

        /** @var list<DomainRecord> $result */
        $result = $qb->getQuery()->getResult();

        return $this->validateQueryResult($result);
    }

    /**
     * 验证域名和名称参数
     */
    private function validateDomainAndNameParameters(string $domain, string $name, ?int $limit, ?int $offset): void
    {
        if ('' === trim($domain)) {
            throw new \InvalidArgumentException('Domain cannot be empty');
        }

        if ('' === trim($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if (strlen($domain) > 253) {
            throw new \InvalidArgumentException('Domain name too long');
        }

        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('Record name too long');
        }

        if (null !== $limit && ($limit < 1 || $limit > 1000)) {
            throw new \InvalidArgumentException('Limit must be between 1 and 1000');
        }

        if (null !== $offset && $offset < 0) {
            throw new \InvalidArgumentException('Offset must be non-negative');
        }
    }

    /**
     * 应用可选的查询过滤条件
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    private function applyOptionalFilters(\Doctrine\ORM\QueryBuilder $qb, ?string $type, ?int $limit, ?int $offset): void
    {
        if (null !== $type) {
            $qb->andWhere('r.type = :type')
                ->setParameter('type', $type)
            ;
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
    }

    /**
     * 验证查询结果
     *
     * @param mixed $result
     * @return list<DomainRecord>
     */
    private function validateQueryResult(mixed $result): array
    {
        if (!is_array($result)) {
            throw new \RuntimeException('Query result should be an array');
        }

        $validatedResult = [];
        foreach ($result as $item) {
            if (!$item instanceof DomainRecord) {
                throw new \RuntimeException('Query result should contain only DomainRecord entities');
            }
            $validatedResult[] = $item;
        }

        return $validatedResult;
    }

    public function save(DomainRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DomainRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
