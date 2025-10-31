<?php

namespace DigitalOceanDomainBundle\Tests\Repository;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DomainRecordRepository::class)]
#[RunTestsInSeparateProcesses]
final class DomainRecordRepositoryTest extends AbstractRepositoryTestCase
{
    private DomainRecordRepository $repository;

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

    protected function createNewEntity(): object
    {
        return $this->createTestDomainRecord();
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DomainRecordRepository::class);
    }

    public function testRepositoryExistsInContainer(): void
    {
        $this->assertInstanceOf(DomainRecordRepository::class, $this->repository);
    }

    // find方法测试

    public function testFindWithStringIdShouldHandleCorrectly(): void
    {
        $result = $this->repository->find('invalid');

        $this->assertNull($result);
    }

    public function testFindWithValidDatabaseConnectionShouldWorkNormally(): void
    {
        $record = $this->createTestDomainRecord('example.com', 'www', 'A');
        $this->repository->save($record);

        $found = $this->repository->find($record->getId());

        $this->assertInstanceOf(DomainRecord::class, $found);
        $this->assertSame($record->getDomainName(), $found->getDomainName());
    }

    // findAll方法测试

    public function testFindAllShouldReturnAllRecords(): void
    {
        $results = $this->repository->findAll();

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(5, count($results));
        foreach ($results as $record) {
            $this->assertInstanceOf(DomainRecord::class, $record);
        }
    }

    public function testFindAllShouldUseEntityManagerCorrectly(): void
    {
        $results = $this->repository->findAll();

        $this->assertIsArray($results);
        foreach ($results as $record) {
            $this->assertInstanceOf(DomainRecord::class, $record);
        }
    }

    // findBy方法测试

    public function testFindByWithEmptyCriteriaShouldReturnAllEntities(): void
    {
        $initialCount = count($this->repository->findBy([]));

        $record1 = $this->createTestDomainRecord('example.com', 'test1', 'A');
        $record2 = $this->createTestDomainRecord('example.org', 'test2', 'CNAME');
        $this->repository->save($record1);
        $this->repository->save($record2);

        $results = $this->repository->findBy([]);

        $this->assertCount($initialCount + 2, $results);
        foreach ($results as $record) {
            $this->assertInstanceOf(DomainRecord::class, $record);
        }
    }

    public function testFindByWithNullCriteriaShouldFindNullFields(): void
    {
        $initialNullPriorityCount = count($this->repository->findBy(['priority' => null]));

        $recordWithPriority = $this->createTestDomainRecord('example.com', 'mail', 'MX');
        $recordWithPriority->setPriority(10);

        $recordWithoutPriority1 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutPriority1->setPriority(null);

        $recordWithoutPriority2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutPriority2->setPriority(null);

        $this->repository->save($recordWithPriority);
        $this->repository->save($recordWithoutPriority1);
        $this->repository->save($recordWithoutPriority2);

        $results = $this->repository->findBy(['priority' => null]);

        $this->assertCount($initialNullPriorityCount + 2, $results);
        foreach ($results as $record) {
            $this->assertInstanceOf(DomainRecord::class, $record);
            $this->assertNull($record->getPriority());
        }
    }

    public function testFindByWithNullPortShouldFindNullFields(): void
    {
        $initialCount = count($this->repository->findBy(['port' => null]));

        $recordWithPort = $this->createTestDomainRecord('example.com', 'srv', 'SRV');
        $recordWithPort->setPort(80);

        $recordWithoutPort = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutPort->setPort(null);

        $this->repository->save($recordWithPort);
        $this->repository->save($recordWithoutPort);

        $results = $this->repository->findBy(['port' => null]);

        $this->assertCount($initialCount + 1, $results);
        $this->assertInstanceOf(DomainRecord::class, $results[count($results) - 1]);
        $this->assertNull($results[count($results) - 1]->getPort());
    }

    public function testFindByWithNullTtlShouldFindNullFields(): void
    {
        $initialCount = count($this->repository->findBy(['ttl' => null]));

        $recordWithTtl = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithTtl->setTtl(3600);

        $recordWithoutTtl = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutTtl->setTtl(null);

        $this->repository->save($recordWithTtl);
        $this->repository->save($recordWithoutTtl);

        $results = $this->repository->findBy(['ttl' => null]);

        $this->assertCount($initialCount + 1, $results);
        $this->assertInstanceOf(DomainRecord::class, $results[count($results) - 1]);
        $this->assertNull($results[count($results) - 1]->getTtl());
    }

    public function testFindByWithNullWeightShouldFindNullFields(): void
    {
        $initialCount = count($this->repository->findBy(['weight' => null]));

        $recordWithWeight = $this->createTestDomainRecord('example.com', 'srv', 'SRV');
        $recordWithWeight->setWeight(100);

        $recordWithoutWeight = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutWeight->setWeight(null);

        $this->repository->save($recordWithWeight);
        $this->repository->save($recordWithoutWeight);

        $results = $this->repository->findBy(['weight' => null]);

        $this->assertCount($initialCount + 1, $results);
        $this->assertInstanceOf(DomainRecord::class, $results[count($results) - 1]);
        $this->assertNull($results[count($results) - 1]->getWeight());
    }

    public function testFindByWithNullFlagsShouldFindNullFields(): void
    {
        $initialCount = count($this->repository->findBy(['flags' => null]));

        $recordWithFlags = $this->createTestDomainRecord('example.com', 'caa', 'CAA');
        $recordWithFlags->setFlags('0');

        $recordWithoutFlags = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutFlags->setFlags(null);

        $this->repository->save($recordWithFlags);
        $this->repository->save($recordWithoutFlags);

        $results = $this->repository->findBy(['flags' => null]);

        $this->assertCount($initialCount + 1, $results);
        $this->assertInstanceOf(DomainRecord::class, $results[count($results) - 1]);
        $this->assertNull($results[count($results) - 1]->getFlags());
    }

    public function testFindByWithNullTagShouldFindNullFields(): void
    {
        $initialCount = count($this->repository->findBy(['tag' => null]));

        $recordWithTag = $this->createTestDomainRecord('example.com', 'caa', 'CAA');
        $recordWithTag->setTag('issue');

        $recordWithoutTag = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutTag->setTag(null);

        $this->repository->save($recordWithTag);
        $this->repository->save($recordWithoutTag);

        $results = $this->repository->findBy(['tag' => null]);

        $this->assertCount($initialCount + 1, $results);
        $this->assertInstanceOf(DomainRecord::class, $results[count($results) - 1]);
        $this->assertNull($results[count($results) - 1]->getTag());
    }

    public function testFindByWithConfigShouldFindAssociatedRecords(): void
    {
        $config = new DigitalOceanConfig();
        $config->setApiKey('test-api-key-2');
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($config);
        $entityManager->flush();

        $recordWithConfig1 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithConfig1->setConfig($config);

        $recordWithConfig2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithConfig2->setConfig($config);

        $recordWithoutConfig = $this->createTestDomainRecord('example.com', 'mail', 'MX');
        $recordWithoutConfig->setConfig(null);

        $this->repository->save($recordWithConfig1);
        $this->repository->save($recordWithConfig2);
        $this->repository->save($recordWithoutConfig);

        $results = $this->repository->findBy(['config' => $config]);

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertInstanceOf(DomainRecord::class, $record);
            $this->assertSame($config, $record->getConfig());
        }
    }

    // findOneBy方法测试

    public function testFindOneByWithNullCriteriaValueShouldFindNullFields(): void
    {
        $initialCount = count($this->repository->findBy(['priority' => null]));

        $recordWithPriority = $this->createTestDomainRecord('example.com', 'mail', 'MX');
        $recordWithPriority->setPriority(10);

        $recordWithoutPriority = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutPriority->setPriority(null);

        $this->repository->save($recordWithPriority);
        $this->repository->save($recordWithoutPriority);

        $result = $this->repository->findOneBy(['priority' => null]);

        $this->assertInstanceOf(DomainRecord::class, $result);
        $this->assertNull($result->getPriority());

        $finalCount = count($this->repository->findBy(['priority' => null]));
        $this->assertSame($initialCount + 1, $finalCount);
    }

    public function testFindOneByWithOrderByShouldRespectOrdering(): void
    {
        $record1 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $record2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $record3 = $this->createTestDomainRecord('example.com', 'mail', 'A');
        $this->repository->save($record1);
        $this->repository->save($record2);
        $this->repository->save($record3);

        $resultAsc = $this->repository->findOneBy(['type' => 'A'], ['name' => 'ASC']);
        $resultDesc = $this->repository->findOneBy(['type' => 'A'], ['name' => 'DESC']);

        $this->assertInstanceOf(DomainRecord::class, $resultAsc);
        $this->assertInstanceOf(DomainRecord::class, $resultDesc);
        $this->assertSame('api', $resultAsc->getName()); // 字母序最小
        $this->assertSame('www', $resultDesc->getName()); // 字母序最大
    }

    public function testFindOneByWithNullConfigShouldFindNullFields(): void
    {
        $initialCount = count($this->repository->findBy(['config' => null]));

        $recordWithConfig = $this->createTestDomainRecord('example.com', 'www', 'A');
        $config = new DigitalOceanConfig();
        $config->setApiKey('test-api-key-3');
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($config);
        $entityManager->flush();
        $recordWithConfig->setConfig($config);

        $recordWithoutConfig = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutConfig->setConfig(null);

        $this->repository->save($recordWithConfig);
        $this->repository->save($recordWithoutConfig);

        $result = $this->repository->findOneBy(['config' => null]);

        $this->assertInstanceOf(DomainRecord::class, $result);
        $this->assertNull($result->getConfig());

        $finalCount = count($this->repository->findBy(['config' => null]));
        $this->assertSame($initialCount + 1, $finalCount);
    }

    public function testFindOneByWithConfigShouldFindAssociatedRecord(): void
    {
        $config = new DigitalOceanConfig();
        $config->setApiKey('test-api-key-4');
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($config);
        $entityManager->flush();

        $recordWithConfig = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithConfig->setConfig($config);

        $recordWithoutConfig = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutConfig->setConfig(null);

        $this->repository->save($recordWithConfig);
        $this->repository->save($recordWithoutConfig);

        $result = $this->repository->findOneBy(['config' => $config]);

        $this->assertInstanceOf(DomainRecord::class, $result);
        $this->assertSame($config, $result->getConfig());
        $this->assertSame('www', $result->getName());
    }

    public function testFindOneByWithMultipleRecordsShouldRespectOrderBy(): void
    {
        $record1 = $this->createTestDomainRecord('example.com', 'mail', 'A');
        $record2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $record3 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $this->repository->save($record1);
        $this->repository->save($record2);
        $this->repository->save($record3);

        $result = $this->repository->findOneBy(['type' => 'A'], ['name' => 'ASC']);

        $this->assertInstanceOf(DomainRecord::class, $result);
        $this->assertSame('api', $result->getName());
    }

    public function testFindOneByWithOrderByShouldRespectOrderingByMultipleFields(): void
    {
        $uniqueName = 'test-priority-' . uniqid();
        $record1 = $this->createTestDomainRecord('example.com', $uniqueName, 'A');
        $record1->setPriority(10);
        $record2 = $this->createTestDomainRecord('example.com', $uniqueName, 'A');
        $record2->setPriority(5);

        $this->repository->save($record1);
        $this->repository->save($record2);

        $result = $this->repository->findOneBy(['name' => $uniqueName], ['priority' => 'ASC']);

        $this->assertInstanceOf(DomainRecord::class, $result);
        $this->assertSame(5, $result->getPriority());
    }

    public function testFindOneByWithOrderByShouldRespectOrderingByIdField(): void
    {
        $record1 = $this->createTestDomainRecord('example.com', 'test-order-1', 'A');
        $record2 = $this->createTestDomainRecord('example.com', 'test-order-2', 'A');
        $record3 = $this->createTestDomainRecord('example.com', 'test-order-3', 'A');

        $this->repository->save($record1);
        $this->repository->save($record2);
        $this->repository->save($record3);

        $resultAsc = $this->repository->findOneBy(['domainName' => 'example.com'], ['id' => 'ASC']);
        $resultDesc = $this->repository->findOneBy(['domainName' => 'example.com'], ['id' => 'DESC']);

        $this->assertInstanceOf(DomainRecord::class, $resultAsc);
        $this->assertInstanceOf(DomainRecord::class, $resultDesc);
        $this->assertLessThan($resultDesc->getId(), $resultAsc->getId());
    }

    public function testFindOneByWithOrderByShouldHandleComplexOrdering(): void
    {
        $record1 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $record1->setPriority(10);
        $record2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $record2->setPriority(5);
        $record3 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $record3->setPriority(15);

        $this->repository->save($record1);
        $this->repository->save($record2);
        $this->repository->save($record3);

        $result = $this->repository->findOneBy(['type' => 'A'], ['name' => 'ASC', 'priority' => 'ASC']);

        $this->assertInstanceOf(DomainRecord::class, $result);
        $this->assertSame('api', $result->getName());
        $this->assertSame(5, $result->getPriority());
    }

    public function testFindOneByAssociationConfigShouldReturnMatchingEntity(): void
    {
        $config = new DigitalOceanConfig();
        $config->setApiKey('test-api-key-findone');
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($config);
        $entityManager->flush();

        $recordWithConfig = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithConfig->setConfig($config);

        $recordWithoutConfig = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutConfig->setConfig(null);

        $this->repository->save($recordWithConfig);
        $this->repository->save($recordWithoutConfig);

        $result = $this->repository->findOneBy(['config' => $config]);

        $this->assertInstanceOf(DomainRecord::class, $result);
        $this->assertSame($config, $result->getConfig());
        $this->assertSame('www', $result->getName());
    }

    public function testCountByAssociationConfigShouldReturnCorrectNumber(): void
    {
        $config = new DigitalOceanConfig();
        $config->setApiKey('test-api-key-count');
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($config);
        $entityManager->flush();

        $recordWithConfig1 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithConfig1->setConfig($config);

        $recordWithConfig2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithConfig2->setConfig($config);

        $recordWithoutConfig = $this->createTestDomainRecord('example.com', 'mail', 'MX');
        $recordWithoutConfig->setConfig(null);

        $this->repository->save($recordWithConfig1);
        $this->repository->save($recordWithConfig2);
        $this->repository->save($recordWithoutConfig);

        $count = $this->repository->count(['config' => $config]);

        $this->assertSame(2, $count);
    }

    public function testCountWithNullCriteriaShouldCountNullFields(): void
    {
        $initialCount = $this->repository->count(['priority' => null]);

        $recordWithPriority = $this->createTestDomainRecord('example.com', 'mail', 'MX');
        $recordWithPriority->setPriority(10);

        $recordWithoutPriority1 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutPriority1->setPriority(null);

        $recordWithoutPriority2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutPriority2->setPriority(null);

        $this->repository->save($recordWithPriority);
        $this->repository->save($recordWithoutPriority1);
        $this->repository->save($recordWithoutPriority2);

        $count = $this->repository->count(['priority' => null]);

        $this->assertSame($initialCount + 2, $count);
    }

    public function testCountWithNullPortShouldCountNullFields(): void
    {
        $initialCount = $this->repository->count(['port' => null]);

        $recordWithPort = $this->createTestDomainRecord('example.com', 'srv', 'SRV');
        $recordWithPort->setPort(80);

        $recordWithoutPort = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutPort->setPort(null);

        $this->repository->save($recordWithPort);
        $this->repository->save($recordWithoutPort);

        $count = $this->repository->count(['port' => null]);

        $this->assertSame($initialCount + 1, $count);
    }

    public function testCountWithNullTtlShouldCountNullFields(): void
    {
        $initialCount = $this->repository->count(['ttl' => null]);

        $recordWithTtl = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithTtl->setTtl(3600);

        $recordWithoutTtl = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutTtl->setTtl(null);

        $this->repository->save($recordWithTtl);
        $this->repository->save($recordWithoutTtl);

        $count = $this->repository->count(['ttl' => null]);

        $this->assertSame($initialCount + 1, $count);
    }

    public function testCountWithNullWeightShouldCountNullFields(): void
    {
        $initialCount = $this->repository->count(['weight' => null]);

        $recordWithWeight = $this->createTestDomainRecord('example.com', 'srv', 'SRV');
        $recordWithWeight->setWeight(100);

        $recordWithoutWeight = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutWeight->setWeight(null);

        $this->repository->save($recordWithWeight);
        $this->repository->save($recordWithoutWeight);

        $count = $this->repository->count(['weight' => null]);

        $this->assertSame($initialCount + 1, $count);
    }

    public function testCountWithNullFlagsShouldCountNullFields(): void
    {
        $initialCount = $this->repository->count(['flags' => null]);

        $recordWithFlags = $this->createTestDomainRecord('example.com', 'caa', 'CAA');
        $recordWithFlags->setFlags('0');

        $recordWithoutFlags = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutFlags->setFlags(null);

        $this->repository->save($recordWithFlags);
        $this->repository->save($recordWithoutFlags);

        $count = $this->repository->count(['flags' => null]);

        $this->assertSame($initialCount + 1, $count);
    }

    public function testCountWithNullTagShouldCountNullFields(): void
    {
        $initialCount = $this->repository->count(['tag' => null]);

        $recordWithTag = $this->createTestDomainRecord('example.com', 'caa', 'CAA');
        $recordWithTag->setTag('issue');

        $recordWithoutTag = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithoutTag->setTag(null);

        $this->repository->save($recordWithTag);
        $this->repository->save($recordWithoutTag);

        $count = $this->repository->count(['tag' => null]);

        $this->assertSame($initialCount + 1, $count);
    }

    public function testCountWithValidCriteriaShouldWork(): void
    {
        $count = $this->repository->count(['type' => 'A']);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullConfigShouldCountNullFields(): void
    {
        $initialCount = $this->repository->count(['config' => null]);

        $config = new DigitalOceanConfig();
        $config->setApiKey('test-api-key-5');
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($config);
        $entityManager->flush();

        $recordWithConfig = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithConfig->setConfig($config);

        $recordWithoutConfig = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithoutConfig->setConfig(null);

        $this->repository->save($recordWithConfig);
        $this->repository->save($recordWithoutConfig);

        $count = $this->repository->count(['config' => null]);

        $this->assertSame($initialCount + 1, $count);
    }

    public function testCountWithConfigShouldCountAssociatedRecords(): void
    {
        $config = new DigitalOceanConfig();
        $config->setApiKey('test-api-key-6');
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($config);
        $entityManager->flush();

        $recordWithConfig1 = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordWithConfig1->setConfig($config);

        $recordWithConfig2 = $this->createTestDomainRecord('example.com', 'api', 'A');
        $recordWithConfig2->setConfig($config);

        $recordWithoutConfig = $this->createTestDomainRecord('example.com', 'mail', 'MX');
        $recordWithoutConfig->setConfig(null);

        $this->repository->save($recordWithConfig1);
        $this->repository->save($recordWithConfig2);
        $this->repository->save($recordWithoutConfig);

        $count = $this->repository->count(['config' => $config]);

        $this->assertSame(2, $count);
    }

    // 专有方法测试

    public function testFindByDomainAndNameWithValidParametersShouldReturnMatchingRecords(): void
    {
        $record1 = $this->createTestDomainRecord('example.com', 'www-test', 'A');
        $record2 = $this->createTestDomainRecord('example.com', 'api-www', 'A');
        $record3 = $this->createTestDomainRecord('example.com', 'mail', 'MX');
        $record4 = $this->createTestDomainRecord('example.org', 'www-test', 'A');

        $this->repository->save($record1);
        $this->repository->save($record2);
        $this->repository->save($record3);
        $this->repository->save($record4);

        $results = $this->repository->findByDomainAndName('example.com', 'www');

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertInstanceOf(DomainRecord::class, $record);
            $this->assertSame('example.com', $record->getDomainName());
            $this->assertStringContainsString('www', $record->getName());
        }
    }

    public function testFindByDomainAndNameWithTypeShouldFilterByType(): void
    {
        $recordA = $this->createTestDomainRecord('example.com', 'www', 'A');
        $recordCNAME = $this->createTestDomainRecord('example.com', 'www-alias', 'CNAME');

        $this->repository->save($recordA);
        $this->repository->save($recordCNAME);

        $results = $this->repository->findByDomainAndName('example.com', 'www', 'A');

        $this->assertCount(1, $results);
        $this->assertInstanceOf(DomainRecord::class, $results[0]);
        $this->assertSame('A', $results[0]->getType());
        $this->assertSame('www', $results[0]->getName());
    }

    public function testFindByDomainAndNameWithLimitShouldRespectLimit(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $record = $this->createTestDomainRecord('example.com', "test{$i}", 'A');
            $this->repository->save($record);
        }

        $results = $this->repository->findByDomainAndName('example.com', 'test', null, 2);

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertInstanceOf(DomainRecord::class, $record);
            $this->assertStringContainsString('test', $record->getName());
        }
    }

    public function testFindByDomainAndNameWithOffsetShouldRespectOffset(): void
    {
        $records = [];
        for ($i = 1; $i <= 5; ++$i) {
            $record = $this->createTestDomainRecord('example.com', "test{$i}", 'A');
            $record->setRecordId(1000 + $i); // 设置明确的recordId确保排序
            $this->repository->save($record);
            $records[] = $record;
        }

        $results = $this->repository->findByDomainAndName('example.com', 'test', null, 2, 1);

        $this->assertCount(2, $results);
        // 由于是按recordId升序排序，offset=1意味着跳过第一个，取第二个和第三个
        $this->assertSame($records[1]->getName(), $results[0]->getName());
        $this->assertSame($records[2]->getName(), $results[1]->getName());
    }

    public function testFindByDomainAndNameWithNonMatchingShouldReturnEmptyArray(): void
    {
        $record = $this->createTestDomainRecord('example.com', 'www', 'A');
        $this->repository->save($record);

        $results = $this->repository->findByDomainAndName('example.com', 'nonexistent');

        $this->assertSame([], $results);
    }

    public function testFindByDomainAndNameShouldOrderByRecordIdAsc(): void
    {
        $record1 = $this->createTestDomainRecord('example.com', 'test-b', 'A');
        $record1->setRecordId(200);
        $record2 = $this->createTestDomainRecord('example.com', 'test-a', 'A');
        $record2->setRecordId(100);
        $record3 = $this->createTestDomainRecord('example.com', 'test-c', 'A');
        $record3->setRecordId(300);

        $this->repository->save($record1);
        $this->repository->save($record2);
        $this->repository->save($record3);

        $results = $this->repository->findByDomainAndName('example.com', 'test');

        $this->assertCount(3, $results);
        $this->assertSame(100, $results[0]->getRecordId());
        $this->assertSame(200, $results[1]->getRecordId());
        $this->assertSame(300, $results[2]->getRecordId());
    }

    // save方法测试

    public function testSaveWithNewEntityShouldPersistToDatabase(): void
    {
        $record = $this->createTestDomainRecord('new.com', 'www', 'A');

        $this->repository->save($record);

        $this->assertNotNull($record->getId());
        $foundRecord = $this->repository->find($record->getId());
        $this->assertInstanceOf(DomainRecord::class, $foundRecord);
        $this->assertSame('new.com', $foundRecord->getDomainName());
    }

    public function testSaveWithExistingEntityShouldUpdateDatabase(): void
    {
        $record = $this->createTestDomainRecord('original.com', 'www', 'A');
        $this->repository->save($record);

        $record->setDomainName('updated.com');
        $this->repository->save($record);

        $foundRecord = $this->repository->find($record->getId());
        $this->assertInstanceOf(DomainRecord::class, $foundRecord);
        $this->assertSame('updated.com', $foundRecord->getDomainName());
    }

    public function testSaveWithFlushFalseShouldNotCommitToDatabase(): void
    {
        $record = $this->createTestDomainRecord('notflushed.com', 'www', 'A');

        $this->repository->save($record, false);

        self::getEntityManager()->clear();
        $foundRecord = $this->repository->findOneBy(['domainName' => 'notflushed.com']);
        $this->assertNull($foundRecord);
    }

    // remove方法测试

    public function testRemoveWithExistingEntityShouldDeleteFromDatabase(): void
    {
        $record = $this->createTestDomainRecord('todelete.com', 'www', 'A');
        $this->repository->save($record);
        $recordId = $record->getId();

        $this->repository->remove($record);

        $foundRecord = $this->repository->find($recordId);
        $this->assertNull($foundRecord);
    }

    public function testRemoveWithFlushFalseShouldNotCommitToDatabase(): void
    {
        $record = $this->createTestDomainRecord('notremoved.com', 'www', 'A');
        $this->repository->save($record);
        $recordId = $record->getId();

        $this->repository->remove($record, false);

        self::getEntityManager()->clear();
        $foundRecord = $this->repository->find($recordId);
        $this->assertInstanceOf(DomainRecord::class, $foundRecord);
    }

    // PHPStan 自定义规则要求的特定命名模式测试

    // 辅助方法

    private function createTestDomainRecord(
        string $domainName = 'test.com',
        string $name = 'www',
        string $type = 'A',
        string $data = '192.168.1.1',
        int $recordId = 12345,
    ): DomainRecord {
        $record = new DomainRecord();
        $record->setDomainName($domainName);
        $record->setName($name);
        $record->setType($type);
        $record->setData($data);
        // 如果 recordId 为 0，不添加随机数（用于需要精确控制 recordId 的测试）
        if (0 === $recordId) {
            $record->setRecordId(random_int(1, 10000));
        } else {
            $record->setRecordId($recordId + random_int(1, 10000));
        }

        return $record;
    }

    /**
     * @return ServiceEntityRepository<DomainRecord>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
