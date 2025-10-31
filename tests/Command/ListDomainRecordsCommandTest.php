<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanDomainBundle\Command\ListDomainRecordsCommand;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use DigitalOceanDomainBundle\Tests\Service\TestDomainService;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(ListDomainRecordsCommand::class)]
#[RunTestsInSeparateProcesses]
final class ListDomainRecordsCommandTest extends AbstractCommandTestCase
{
    private TestDomainService $domainService;

    private ListDomainRecordsCommand $command;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 创建Mock服务并注入容器
        $this->domainService = new TestDomainService();
        $container = self::getContainer();
        $container->set('DigitalOceanDomainBundle\Service\DomainServiceInterface', $this->domainService);
        $container->set(TestDomainService::class, $this->domainService);

        // 创建直接使用TestDomainService的Repository模拟
        $mockManagerRegistry = $this->createMock(ManagerRegistry::class);
        $mockRepository = new class($mockManagerRegistry, $this->domainService) extends DomainRecordRepository {
            private TestDomainService $testService;

            public function __construct(ManagerRegistry $registry, TestDomainService $testService)
            {
                parent::__construct($registry);
                $this->testService = $testService;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                $this->testService->addFindByCall([
                    'criteria' => $criteria,
                    'orderBy' => $orderBy,
                    'limit' => $limit,
                    'offset' => $offset,
                ]);

                /** @var list<DomainRecord> */
                return $this->testService->getFindByResponse();
            }

            public function findByDomainAndName(string $domain, string $name, ?string $type = null, ?int $limit = null, ?int $offset = null): array
            {
                $this->testService->addFindByDomainAndNameCall([
                    'domainName' => $domain,
                    'name' => $name,
                    'type' => $type,
                ]);

                /** @var list<DomainRecord> */
                return $this->testService->getFindByDomainAndNameResponse();
            }

            public function count(array $criteria = []): int
            {
                $this->testService->addCountCall($criteria);

                /** @var int<0, max> */
                return $this->testService->getCountResponse();
            }
        };

        $container->set('DigitalOceanDomainBundle\Repository\DomainRecordRepository', $mockRepository);

        // 从容器获取Command
        $this->command = self::getService(ListDomainRecordsCommand::class);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithRemoteApi(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setListDomainRecordsResponse([
            'domain_records' => [
                [
                    'id' => 123,
                    'type' => 'A',
                    'name' => 'www',
                    'data' => '192.168.1.1',
                    'ttl' => 3600,
                ],
                [
                    'id' => 124,
                    'type' => 'MX',
                    'name' => '@',
                    'data' => 'mail.example.com',
                    'ttl' => 3600,
                ],
            ],
            'meta' => ['total' => 2],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--remote' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
        $this->assertStringContainsString('mail.example.com', $display);
        $this->assertStringContainsString('共 2 条记录', $display);

        // 验证方法调用
        $listCalls = $this->domainService->getListDomainRecordsCalls();
        $this->assertCount(1, $listCalls);
        $this->assertEquals([
            'domain' => 'example.com',
            'page' => 1,
            'perPage' => 50,
        ], $listCalls[0]);
    }

    public function testExecuteWithLocalDatabase(): void
    {
        $this->domainService->resetCalls();

        // 创建模拟的 DomainRecord 对象
        $record1 = new DomainRecord();
        $record1->setRecordId(123);
        $record1->setType('A');
        $record1->setName('www');
        $record1->setData('192.168.1.1');
        $record1->setTtl(3600);

        $record2 = new DomainRecord();
        $record2->setRecordId(124);
        $record2->setType('CNAME');
        $record2->setName('blog');
        $record2->setData('www.example.com');
        $record2->setTtl(3600);

        $this->domainService->setFindByResponse([$record1, $record2]);
        $this->domainService->setCountResponse(2);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
        $this->assertStringContainsString('blog', $display);
        $this->assertStringContainsString('共 2 条记录', $display);

        // 验证方法调用
        $findByCalls = $this->domainService->getFindByCalls();
        $this->assertCount(1, $findByCalls);
        $this->assertEquals([
            'criteria' => ['domainName' => 'example.com'],
            'orderBy' => ['recordId' => 'ASC'],
            'limit' => 50,
            'offset' => 0,
        ], $findByCalls[0]);

        $countCalls = $this->domainService->getCountCalls();
        $this->assertCount(1, $countCalls);
        $this->assertEquals(['domainName' => 'example.com'], $countCalls[0]);
    }

    public function testExecuteWithTypeFilter(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setListDomainRecordsResponse([
            'domain_records' => [
                [
                    'id' => 123,
                    'type' => 'A',
                    'name' => 'www',
                    'data' => '192.168.1.1',
                    'ttl' => 3600,
                ],
                [
                    'id' => 125,
                    'type' => 'CNAME',
                    'name' => 'blog',
                    'data' => 'www.example.com',
                    'ttl' => 3600,
                ],
            ],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--remote' => true,
            '--type' => 'A',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
        $this->assertStringNotContainsString('blog', $display);

        // 验证方法调用
        $listCalls = $this->domainService->getListDomainRecordsCalls();
        $this->assertCount(1, $listCalls);
    }

    public function testExecuteWithNameFilter(): void
    {
        $this->domainService->resetCalls();

        // 创建真实的 DomainRecord 对象
        $record = new DomainRecord();
        $record->setRecordId(123);
        $record->setType('A');
        $record->setName('www');
        $record->setData('192.168.1.1');
        $record->setTtl(3600);

        $this->domainService->setFindByDomainAndNameResponse([$record]);
        $this->domainService->setCountResponse(1);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--name' => 'www',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);

        // 验证方法调用
        $findByDomainAndNameCalls = $this->domainService->getFindByDomainAndNameCalls();
        $this->assertCount(1, $findByDomainAndNameCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'name' => 'www',
            'type' => null,
        ], $findByDomainAndNameCalls[0]);

        $countCalls = $this->domainService->getCountCalls();
        $this->assertCount(1, $countCalls);
    }

    public function testExecuteWithNoRecords(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setFindByResponse([]);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('没有找到任何记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $findByCalls = $this->domainService->getFindByCalls();
        $this->assertCount(1, $findByCalls);
    }

    public function testExecuteWithException(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setFindByException(new TestException('Database error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('获取域名记录时发生错误: Database error', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        $this->domainService->resetCalls();

        // 创建真实的 DomainRecord 对象
        $record = new DomainRecord();
        $record->setRecordId(123);
        $record->setType('A');
        $record->setName('api');
        $record->setData('192.168.1.100');
        $record->setTtl(3600);

        $this->domainService->setFindByResponse([$record]);
        $this->domainService->setCountResponse(1);

        $this->commandTester->execute([
            'domain' => 'test-domain.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('api', $display);
        $this->assertStringContainsString('192.168.1.100', $display);

        // 验证方法调用
        $findByCalls = $this->domainService->getFindByCalls();
        $this->assertCount(1, $findByCalls);
        $this->assertEquals([
            'criteria' => ['domainName' => 'test-domain.com'],
            'orderBy' => ['recordId' => 'ASC'],
            'limit' => 50,
            'offset' => 0,
        ], $findByCalls[0]);

        $countCalls = $this->domainService->getCountCalls();
        $this->assertCount(1, $countCalls);
        $this->assertEquals(['domainName' => 'test-domain.com'], $countCalls[0]);
    }

    public function testOptionType(): void
    {
        $this->domainService->resetCalls();

        // 创建真实的 DomainRecord 对象
        $record = new DomainRecord();
        $record->setRecordId(124);
        $record->setType('MX');
        $record->setName('@');
        $record->setData('mail.example.com');
        $record->setTtl(3600);

        $this->domainService->setFindByResponse([$record]);
        $this->domainService->setCountResponse(1);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--type' => 'MX',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('MX', $display);

        // 验证方法调用
        $findByCalls = $this->domainService->getFindByCalls();
        $this->assertCount(1, $findByCalls);
        $this->assertEquals([
            'criteria' => ['domainName' => 'example.com', 'type' => 'MX'],
            'orderBy' => ['recordId' => 'ASC'],
            'limit' => 50,
            'offset' => 0,
        ], $findByCalls[0]);

        $countCalls = $this->domainService->getCountCalls();
        $this->assertCount(1, $countCalls);
        $this->assertEquals(['domainName' => 'example.com'], $countCalls[0]);
    }

    public function testOptionName(): void
    {
        $this->domainService->resetCalls();

        // 创建真实的 DomainRecord 对象
        $record = new DomainRecord();
        $record->setRecordId(125);
        $record->setType('CNAME');
        $record->setName('cdn');
        $record->setData('cdn.example.com');
        $record->setTtl(3600);

        $this->domainService->setFindByDomainAndNameResponse([$record]);
        $this->domainService->setCountResponse(1);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--name' => 'cdn',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('cdn', $display);
        $this->assertStringContainsString('cdn.example.com', $display);

        // 验证方法调用
        $findByDomainAndNameCalls = $this->domainService->getFindByDomainAndNameCalls();
        $this->assertCount(1, $findByDomainAndNameCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'name' => 'cdn',
            'type' => null,
        ], $findByDomainAndNameCalls[0]);

        $countCalls = $this->domainService->getCountCalls();
        $this->assertCount(1, $countCalls);
    }

    public function testOptionRemote(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setListDomainRecordsResponse([
            'domain_records' => [
                [
                    'id' => 126,
                    'type' => 'TXT',
                    'name' => '@',
                    'data' => 'v=spf1 include:_spf.example.com ~all',
                    'ttl' => 3600,
                ],
            ],
            'meta' => ['total' => 1],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--remote' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('v=spf1', $display);
        $this->assertStringContainsString('共 1 条记录', $display);

        // 验证方法调用
        $listCalls = $this->domainService->getListDomainRecordsCalls();
        $this->assertCount(1, $listCalls);
        $this->assertEquals([
            'domain' => 'example.com',
            'page' => 1,
            'perPage' => 50,
        ], $listCalls[0]);
    }

    public function testOptionPage(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setListDomainRecordsResponse([
            'domain_records' => [
                [
                    'id' => 127,
                    'type' => 'NS',
                    'name' => '@',
                    'data' => 'ns1.digitalocean.com',
                    'ttl' => 1800,
                ],
            ],
            'meta' => ['total' => 100],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--remote' => true,
            '--page' => '2',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ns1.digitalocean.com', $display);
        $this->assertStringContainsString('第 2/', $display);

        // 验证方法调用
        $listCalls = $this->domainService->getListDomainRecordsCalls();
        $this->assertCount(1, $listCalls);
        $this->assertEquals([
            'domain' => 'example.com',
            'page' => 2,
            'perPage' => 50,
        ], $listCalls[0]);
    }

    public function testOptionLimit(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setListDomainRecordsResponse([
            'domain_records' => [
                [
                    'id' => 128,
                    'type' => 'A',
                    'name' => 'test',
                    'data' => '10.0.0.1',
                    'ttl' => 3600,
                ],
            ],
            'meta' => ['total' => 50],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--remote' => true,
            '--limit' => '10',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('test', $display);
        $this->assertStringContainsString('10.0.0.1', $display);

        // 验证方法调用
        $listCalls = $this->domainService->getListDomainRecordsCalls();
        $this->assertCount(1, $listCalls);
        $this->assertEquals([
            'domain' => 'example.com',
            'page' => 1,
            'perPage' => 10,
        ], $listCalls[0]);
    }
}
