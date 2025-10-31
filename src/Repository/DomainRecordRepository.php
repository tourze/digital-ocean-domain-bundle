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
        $qb = $this->createQueryBuilder('r')
            ->where('r.domainName = :domain')
            ->andWhere('r.name LIKE :name')
            ->setParameter('domain', $domain)
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('r.recordId', 'ASC')
        ;

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

        /** @var list<DomainRecord> $result */
        $result = $qb->getQuery()->getResult();

        // Ensure result is an array of DomainRecord entities
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
