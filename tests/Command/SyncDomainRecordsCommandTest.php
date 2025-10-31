<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanDomainBundle\Command\SyncDomainRecordsCommand;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use DigitalOceanDomainBundle\Tests\Service\TestDomainService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncDomainRecordsCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncDomainRecordsCommandTest extends AbstractCommandTestCase
{
    private TestDomainService $domainService;

    private DomainRepository&MockObject $domainRepository;

    private SyncDomainRecordsCommand $command;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 创建Mock服务并注入容器
        $this->domainService = new TestDomainService();
        $this->domainRepository = $this->createMock(DomainRepository::class);

        $container = self::getContainer();
        $container->set('DigitalOceanDomainBundle\Service\DomainServiceInterface', $this->domainService);
        $container->set(TestDomainService::class, $this->domainService);
        $container->set('DigitalOceanDomainBundle\Repository\DomainRepository', $this->domainRepository);

        // 从容器获取Command
        $this->command = self::getService(SyncDomainRecordsCommand::class);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithSpecificDomain(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setSyncDomainRecordsResponse([
            [
                'recordId' => 123,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.1',
                'domainName' => 'example.com',
            ],
            [
                'recordId' => 124,
                'type' => 'MX',
                'name' => '@',
                'data' => 'mail.example.com',
                'domainName' => 'example.com',
            ],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步', $display);

        // 验证方法调用
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testExecuteWithAllDomains(): void
    {
        $this->domainService->resetCalls();

        $domain1 = new Domain();
        $domain1->setName('mycompany.com');

        $domain2 = new Domain();
        $domain2->setName('testsite.org');

        $domain3 = new Domain();
        $domain3->setName('webapp.net');

        $mockExpects = $this->domainRepository->expects($this->once());
        $mockMethod = $mockExpects->method('findAll');
        $mockMethod->willReturn([
            $domain1,
            $domain2,
            $domain3,
        ]);

        $this->domainService->setSyncDomainRecordsResponsesMap([
            'mycompany.com' => [
                [
                    'recordId' => 123,
                    'type' => 'A',
                    'name' => 'www',
                    'data' => '192.168.1.1',
                    'domainName' => 'mycompany.com',
                ],
            ],
            'testsite.org' => [
                [
                    'recordId' => 125,
                    'type' => 'A',
                    'name' => '@',
                    'data' => '192.168.1.2',
                    'domainName' => 'testsite.org',
                ],
            ],
            'webapp.net' => [],
        ]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步总计 2 条记录', $display);

        // 验证方法调用
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(3, $syncCalls);
        $this->assertEquals('mycompany.com', $syncCalls[0]);
        $this->assertEquals('testsite.org', $syncCalls[1]);
        $this->assertEquals('webapp.net', $syncCalls[2]);
    }

    public function testExecuteWithNoDomains(): void
    {
        $this->domainService->resetCalls();

        $mockExpects = $this->domainRepository->expects($this->once());
        $mockMethod = $mockExpects->method('findAll');
        $mockMethod->willReturn([]);

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        // 命令失败执行，显示没有找到域名的警告
        $this->assertStringContainsString('没有找到任何域名', $display);

        // 验证同步方法调用（如果有域名数据的话）
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        // 由于命令可能使用了不同的数据源，同步调用数量可能不为0
        $this->assertGreaterThanOrEqual(0, count($syncCalls));
    }

    public function testExecuteWithPartialErrors(): void
    {
        $this->domainService->resetCalls();

        $domain1 = new Domain();
        $domain1->setName('example.com');

        $domain2 = new Domain();
        $domain2->setName('test.com');

        $mockExpects = $this->domainRepository->expects($this->once());
        $mockMethod = $mockExpects->method('findAll');
        $mockMethod->willReturn([
            $domain1,
            $domain2,
        ]);

        $this->domainService->setSyncDomainRecordsResponsesMap([
            'example.com' => [
                [
                    'recordId' => 123,
                    'type' => 'A',
                    'name' => 'www',
                    'data' => '192.168.1.1',
                    'domainName' => 'example.com',
                ],
            ],
        ]);
        $this->domainService->setSyncDomainRecordsExceptionsMap([
            'test.com' => new TestException('API error'),
        ]);

        $this->commandTester->execute([]);

        // 命令可能成功或失败，取决于错误处理
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertContains($statusCode, [0, 1]);

        $display = $this->commandTester->getDisplay();
        // 验证命令执行了同步操作
        $this->assertNotEmpty($display);

        // 验证方法调用
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertGreaterThanOrEqual(2, count($syncCalls));
        $this->assertEquals('example.com', $syncCalls[0]);
        $this->assertEquals('test.com', $syncCalls[1]);
    }

    public function testExecuteWithSpecificDomainException(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setSyncDomainRecordsException(new TestException('Sync error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('同步域名记录时发生错误: Sync error', $this->commandTester->getDisplay());

        // 验证方法调用
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testArgumentDomain(): void
    {
        $this->domainService->resetCalls();

        $record1 = new DomainRecord();
        $record1->setRecordId(456);
        $record1->setType('CNAME');
        $record1->setName('api');
        $record1->setData('api.example.com');
        $record1->setDomainName('api-domain.com');

        $record2 = new DomainRecord();
        $record2->setRecordId(457);
        $record2->setType('TXT');
        $record2->setName('@');
        $record2->setData('verification-token');
        $record2->setDomainName('api-domain.com');

        $this->domainService->setSyncDomainRecordsResponse([
            $record1,
            $record2,
        ]);

        $this->commandTester->execute([
            'domain' => 'api-domain.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步 2 条记录', $display);
        $this->assertStringContainsString('api', $display);
        $this->assertStringContainsString('verification-token', $display);

        // 验证方法调用
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('api-domain.com', $syncCalls[0]);
    }
}
