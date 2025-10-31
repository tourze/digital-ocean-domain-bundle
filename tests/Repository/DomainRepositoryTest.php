<?php

namespace DigitalOceanDomainBundle\Tests\Repository;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DomainRepository::class)]
#[RunTestsInSeparateProcesses]
final class DomainRepositoryTest extends AbstractRepositoryTestCase
{
    private DomainRepository $repository;

    /**
     * @return array<class-string>
     */
    public static function configureBundles(): array
    {
        return [
            DigitalOceanAccountBundle::class,
            DigitalOceanDomainBundle::class,
        ];
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DomainRepository::class);
    }

    public function testRepositoryExistsInContainer(): void
    {
        $this->assertInstanceOf(DomainRepository::class, $this->repository);
    }

    public function testRepositoryManagesCorrectEntity(): void
    {
        $entityClass = $this->repository->getClassName();
        $this->assertSame(Domain::class, $entityClass);
    }

    // 基础 find 测试用例

    // findAll 测试用例

    // findBy 测试用例

    public function testFindByWithEmptyCriteriaShouldReturnAllEntities(): void
    {
        $entity = new Domain();
        $entity->setName('example.com');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $results = $this->repository->findBy([]);

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertInstanceOf(Domain::class, $result);
        }
    }

    // findOneBy 测试用例

    public function testFindOneByWithOrderByShouldRespectOrdering(): void
    {
        $entity1 = new Domain();
        $entity1->setName('a-domain.com');
        $entity1->setTtl('3600');
        $entity2 = new Domain();
        $entity2->setName('z-domain.com');
        $entity2->setTtl('7200');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity1);
        $entityManager->persist($entity2);
        $entityManager->flush();

        $resultDesc = $this->repository->findOneBy([], ['name' => 'DESC']);
        $resultAsc = $this->repository->findOneBy([], ['name' => 'ASC']);

        $this->assertInstanceOf(Domain::class, $resultDesc);
        $this->assertInstanceOf(Domain::class, $resultAsc);
        $this->assertStringStartsWith('z-', $resultDesc->getName());
        $this->assertStringStartsWith('a-', $resultAsc->getName());
    }

    // 可空字段测试
    public function testFindByWithNullableFieldIsNull(): void
    {
        $entity = new Domain();
        $entity->setName('null-ttl-domain.com');
        // ttl 字段默认为 null，无需设置

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $results = $this->repository->findBy(['ttl' => null]);

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountWithNullTtlShouldCountNullFields(): void
    {
        $entityWithTtl = new Domain();
        $entityWithTtl->setName('ttl-count-domain.com');
        $entityWithTtl->setTtl('3600');

        $entityWithoutTtl = new Domain();
        $entityWithoutTtl->setName('no-ttl-count-domain.com');
        $entityWithoutTtl->setTtl(null);

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entityWithTtl);
        $entityManager->persist($entityWithoutTtl);
        $entityManager->flush();

        $count = $this->repository->count(['ttl' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountWithNullZoneFileShouldCountNullFields(): void
    {
        $entityWithZoneFile = new Domain();
        $entityWithZoneFile->setName('zone-count-domain.com');
        $entityWithZoneFile->setZoneFile('zone file content');

        $entityWithoutZoneFile = new Domain();
        $entityWithoutZoneFile->setName('no-zone-count-domain.com');
        $entityWithoutZoneFile->setZoneFile(null);

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entityWithZoneFile);
        $entityManager->persist($entityWithoutZoneFile);
        $entityManager->flush();

        $count = $this->repository->count(['zoneFile' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    // 健壮性测试

    public function testFindByWithNullTtlShouldFindNullFields(): void
    {
        $entityWithTtl = new Domain();
        $entityWithTtl->setName('ttl-domain.com');
        $entityWithTtl->setTtl('3600');

        $entityWithoutTtl = new Domain();
        $entityWithoutTtl->setName('no-ttl-domain.com');
        $entityWithoutTtl->setTtl(null);

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entityWithTtl);
        $entityManager->persist($entityWithoutTtl);
        $entityManager->flush();

        $results = $this->repository->findBy(['ttl' => null]);

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertInstanceOf(Domain::class, $result);
            $this->assertNull($result->getTtl());
        }
    }

    public function testFindByWithNullZoneFileShouldFindNullFields(): void
    {
        $entityWithZoneFile = new Domain();
        $entityWithZoneFile->setName('zone-domain.com');
        $entityWithZoneFile->setZoneFile('zone file content');

        $entityWithoutZoneFile = new Domain();
        $entityWithoutZoneFile->setName('no-zone-domain.com');
        $entityWithoutZoneFile->setZoneFile(null);

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entityWithZoneFile);
        $entityManager->persist($entityWithoutZoneFile);
        $entityManager->flush();

        $results = $this->repository->findBy(['zoneFile' => null]);

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertInstanceOf(Domain::class, $result);
            $this->assertNull($result->getZoneFile());
        }
    }

    public function testFindOneByWithOrderByShouldRespectOrderingByIdField(): void
    {
        $entity1 = new Domain();
        $entity1->setName('first-order-domain.com');
        $entity2 = new Domain();
        $entity2->setName('second-order-domain.com');
        $entity3 = new Domain();
        $entity3->setName('third-order-domain.com');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity1);
        $entityManager->persist($entity2);
        $entityManager->persist($entity3);
        $entityManager->flush();

        $resultAsc = $this->repository->findOneBy([], ['id' => 'ASC']);
        $resultDesc = $this->repository->findOneBy([], ['id' => 'DESC']);

        $this->assertInstanceOf(Domain::class, $resultAsc);
        $this->assertInstanceOf(Domain::class, $resultDesc);
        $this->assertLessThan($resultDesc->getId(), $resultAsc->getId());
    }

    public function testFindOneByWithOrderByShouldHandleComplexOrdering(): void
    {
        $entity1 = new Domain();
        $entity1->setName('aaa-complex.com');
        $entity1->setTtl('7200');

        $entity2 = new Domain();
        $entity2->setName('aaa-complex.com');
        $entity2->setTtl('3600');

        $entity3 = new Domain();
        $entity3->setName('zzz-complex.com');
        $entity3->setTtl('1800');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity1);
        $entityManager->persist($entity2);
        $entityManager->persist($entity3);
        $entityManager->flush();

        $result = $this->repository->findOneBy([], ['name' => 'ASC', 'ttl' => 'ASC']);

        $this->assertInstanceOf(Domain::class, $result);
        $this->assertSame('aaa-complex.com', $result->getName());
        $this->assertSame('3600', $result->getTtl());
    }

    public function testFindOneByWithOrderByShouldRespectMultipleOrderFields(): void
    {
        $entity1 = new Domain();
        $entity1->setName('multi-order-1.com');
        $entity1->setTtl('3600');

        $entity2 = new Domain();
        $entity2->setName('multi-order-2.com');
        $entity2->setTtl('1800');

        $entity3 = new Domain();
        $entity3->setName('multi-order-3.com');
        $entity3->setTtl('7200');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity1);
        $entityManager->persist($entity2);
        $entityManager->persist($entity3);
        $entityManager->flush();

        $resultTtlAsc = $this->repository->findOneBy([], ['ttl' => 'ASC']);
        $resultTtlDesc = $this->repository->findOneBy([], ['ttl' => 'DESC']);

        $this->assertInstanceOf(Domain::class, $resultTtlAsc);
        $this->assertInstanceOf(Domain::class, $resultTtlDesc);
        $this->assertSame('1800', $resultTtlAsc->getTtl());
        $this->assertSame('7200', $resultTtlDesc->getTtl());
    }

    // 自定义方法测试
    public function testSaveEntityShouldPersistToDatabase(): void
    {
        $entity = new Domain();
        $entity->setName('save-test.com');
        $entity->setTtl('3600');

        $this->repository->save($entity);

        $this->assertNotNull($entity->getId());

        // 验证实体已保存到数据库
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertInstanceOf(Domain::class, $savedEntity);
        $this->assertEquals('save-test.com', $savedEntity->getName());
        $this->assertEquals('3600', $savedEntity->getTtl());
    }

    public function testSaveEntityWithFlushFalseShouldNotFlush(): void
    {
        $entity = new Domain();
        $entity->setName('no-flush-test.com');

        $this->repository->save($entity, false);

        $this->assertNotNull($entity->getId());

        // 验证数据未被提交到数据库（通过新的 EntityManager 查询）
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->clear(); // 清空 EntityManager 缓存

        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNull($savedEntity); // 应该查询不到，因为没有 flush
    }

    public function testRemoveEntityShouldDeleteFromDatabase(): void
    {
        $entity = new Domain();
        $entity->setName('remove-test.com');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $entityId = $entity->getId();
        $this->assertNotNull($entityId);

        $this->repository->remove($entity);

        // 验证实体已从数据库中删除
        $deletedEntity = $this->repository->find($entityId);
        $this->assertNull($deletedEntity);
    }

    public function testRemoveEntityWithFlushFalseShouldNotFlush(): void
    {
        $entity = new Domain();
        $entity->setName('no-flush-remove-test.com');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $entityId = $entity->getId();
        $this->repository->remove($entity, false);

        // 实体应该仍然存在（因为没有 flush）
        $stillExistsEntity = $this->repository->find($entityId);
        $this->assertInstanceOf(Domain::class, $stillExistsEntity);
    }

    // 复杂查询测试
    public function testFindByWithMultipleConditionsShouldReturnCorrectResults(): void
    {
        $entity = new Domain();
        $entity->setName('multi-condition.com');
        $entity->setTtl('3600');
        $entity->setZoneFile('test zone content');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $results = $this->repository->findBy(['ttl' => '3600', 'name' => 'multi-condition.com']);

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertInstanceOf(Domain::class, $result);
            $this->assertEquals('3600', $result->getTtl());
            $this->assertEquals('multi-condition.com', $result->getName());
        }
    }

    public function testCountWithMultipleCriteriaShouldReturnCorrectNumber(): void
    {
        $entity = new Domain();
        $entity->setName('multi-count.com');
        $entity->setTtl('7200');
        $entity->setZoneFile('zone file content');

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $count = $this->repository->count(['ttl' => '7200', 'name' => 'multi-count.com']);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    protected function createNewEntity(): object
    {
        $domain = new Domain();
        $domain->setName('test.com');

        return $domain;
    }

    /**
     * @return ServiceEntityRepository<Domain>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
