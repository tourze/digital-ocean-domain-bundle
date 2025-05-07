<?php

namespace DigitalOceanDomainBundle\Repository;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 域名记录仓库
 *
 * @method DomainRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomainRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomainRecord[] findAll()
 * @method DomainRecord[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainRecord::class);
    }

    /**
     * 按域名和名称模糊查询记录
     *
     * @param string $domain 域名
     * @param string $name 记录名称（模糊匹配）
     * @param string|null $type 记录类型（精确匹配）
     * @param int|null $limit 限制结果数量
     * @param int|null $offset 起始位置
     * @return DomainRecord[]
     */
    public function findByDomainAndName(string $domain, string $name, ?string $type = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.domainName = :domain')
            ->andWhere('r.name LIKE :name')
            ->setParameter('domain', $domain)
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('r.recordId', 'ASC');

        if ($type !== null) {
            $qb->andWhere('r.type = :type')
                ->setParameter('type', $type);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }
}
