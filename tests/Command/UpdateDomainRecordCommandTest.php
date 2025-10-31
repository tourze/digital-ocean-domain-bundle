<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanDomainBundle\Command\UpdateDomainRecordCommand;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use DigitalOceanDomainBundle\Tests\Helper\TestDomainService;
use DigitalOceanDomainBundle\Tests\Helper\TestEntityGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(UpdateDomainRecordCommand::class)]
#[RunTestsInSeparateProcesses]
final class UpdateDomainRecordCommandTest extends AbstractCommandTestCase
{
    private TestDomainService $domainService;

    private MockObject&DomainRecordRepository $domainRecordRepository;

    private UpdateDomainRecordCommand $command;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 创建Mock服务并注入容器
        $this->domainService = new TestDomainService();
        $this->domainRecordRepository = $this->createMock(DomainRecordRepository::class);

        $container = self::getContainer();
        $container->set('DigitalOceanDomainBundle\Service\DomainServiceInterface', $this->domainService);
        $container->set(DomainRecordRepository::class, $this->domainRecordRepository);
        $container->set(TestDomainService::class, $this->domainService);

        // 从容器获取Command
        $this->command = self::getService(UpdateDomainRecordCommand::class);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithLocalData(): void
    {
        $this->domainService->resetCalls();

        $mockRecord = TestEntityGenerator::createDomainRecord(
            'example.com',
            123,
            'A',
            'www',
            '192.168.1.1',
            null,
            null,
            3600
        );

        $this->domainRecordRepository->method('findOneBy')->willReturn($mockRecord);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'ttl' => 3600,
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--local' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
    }

    public function testExecuteWithLocalDataNotFound(): void
    {
        $this->domainService->resetCalls();

        $this->domainRecordRepository->method('findOneBy')->willReturn(null);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--local' => true,
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('找不到本地记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithRemoteData(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.2',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--data' => '192.168.1.2',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功更新域名记录', $display);
        $this->assertStringContainsString('成功同步记录到本地数据库', $display);

        // 验证方法调用
        $getDomainRecordCalls = $this->domainService->getGetDomainRecordCalls();
        $this->assertCount(1, $getDomainRecordCalls);
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
    }

    public function testExecuteWithRemoteDataCancelled(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--data' => '192.168.1.2',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('操作已取消', $this->commandTester->getDisplay());

        // 验证只调用了获取记录方法，没有调用更新方法
        $getDomainRecordCalls = $this->domainService->getGetDomainRecordCalls();
        $this->assertCount(1, $getDomainRecordCalls);
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(0, $updateCalls);
    }

    public function testExecuteWithRemoteDataNotFound(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('找不到远程记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordException(new TestException('API error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('更新域名记录时发生错误: API error', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 456,
            'type' => 'CNAME',
            'name' => 'api',
            'data' => 'api.example.com',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 456,
            'type' => 'CNAME',
            'name' => 'api',
            'data' => 'api.test-domain.com',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'test-domain.com',
            'record_id' => '456',
            '--data' => 'api.test-domain.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $getDomainRecordCalls = $this->domainService->getGetDomainRecordCalls();
        $this->assertCount(1, $getDomainRecordCalls);
        $this->assertEquals([
            'domainName' => 'test-domain.com',
            'recordId' => 456,
        ], $getDomainRecordCalls[0]);
    }

    public function testArgumentRecordId(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 789,
            'type' => 'TXT',
            'name' => '@',
            'data' => 'v=spf1 include:_spf.example.com ~all',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 789,
            'type' => 'TXT',
            'name' => '@',
            'data' => 'v=spf1 include:_spf.example.com -all',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '789',
            '--data' => 'v=spf1 include:_spf.example.com -all',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $getDomainRecordCalls = $this->domainService->getGetDomainRecordCalls();
        $this->assertCount(1, $getDomainRecordCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'recordId' => 789,
        ], $getDomainRecordCalls[0]);
    }

    public function testOptionType(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 123,
            'type' => 'AAAA',
            'name' => 'www',
            'data' => '2001:db8::1',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--type' => 'AAAA',
            '--data' => '2001:db8::1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals('AAAA', $updateCalls[0]['type']);
        $this->assertEquals('2001:db8::1', $updateCalls[0]['data']);
    }

    public function testOptionData(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 124,
            'type' => 'A',
            'name' => 'test',
            'data' => '192.168.1.1',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 124,
            'type' => 'A',
            'name' => 'test',
            'data' => '10.0.0.1',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '124',
            '--data' => '10.0.0.1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals('10.0.0.1', $updateCalls[0]['data']);
    }

    public function testOptionPriority(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 125,
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            'priority' => 10,
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 125,
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            'priority' => 5,
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '125',
            '--priority' => '5',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals(5, $updateCalls[0]['priority']);
    }

    public function testOptionTtl(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 126,
            'type' => 'A',
            'name' => 'cache',
            'data' => '192.168.1.10',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 126,
            'type' => 'A',
            'name' => 'cache',
            'data' => '192.168.1.10',
            'ttl' => 1800,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '126',
            '--ttl' => '1800',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals(1800, $updateCalls[0]['ttl']);
    }

    public function testOptionLocal(): void
    {
        $this->domainService->resetCalls();

        $mockRecord = TestEntityGenerator::createDomainRecord(
            'example.com',
            127,
            'CNAME',
            'cdn',
            'cdn.example.com',
            null,
            null,
            3600
        );

        $this->domainRecordRepository->method('findOneBy')->willReturn($mockRecord);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 127,
            'type' => 'CNAME',
            'name' => 'cdn',
            'data' => 'cdn.example.com',
            'ttl' => 3600,
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '127',
            '--local' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
    }

    public function testOptionName(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 128,
            'type' => 'CNAME',
            'name' => 'www',
            'data' => 'example.com',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 128,
            'type' => 'CNAME',
            'name' => 'blog',
            'data' => 'example.com',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '128',
            '--name' => 'blog',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals('blog', $updateCalls[0]['name']);
    }

    public function testOptionPort(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 129,
            'type' => 'SRV',
            'name' => '_http._tcp',
            'data' => 'web.example.com',
            'priority' => 10,
            'port' => 80,
            'weight' => 5,
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 129,
            'type' => 'SRV',
            'name' => '_http._tcp',
            'data' => 'web.example.com',
            'priority' => 10,
            'port' => 443,
            'weight' => 5,
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '129',
            '--port' => '443',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals(443, $updateCalls[0]['port']);
    }

    public function testOptionWeight(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 130,
            'type' => 'SRV',
            'name' => '_https._tcp',
            'data' => 'web.example.com',
            'priority' => 10,
            'port' => 443,
            'weight' => 5,
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 130,
            'type' => 'SRV',
            'name' => '_https._tcp',
            'data' => 'web.example.com',
            'priority' => 10,
            'port' => 443,
            'weight' => 10,
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '130',
            '--weight' => '10',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals(10, $updateCalls[0]['weight']);
    }

    public function testOptionFlags(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 131,
            'type' => 'CAA',
            'name' => '@',
            'data' => 'letsencrypt.org',
            'flags' => '0',
            'tag' => 'issue',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 131,
            'type' => 'CAA',
            'name' => '@',
            'data' => 'letsencrypt.org',
            'flags' => '128',
            'tag' => 'issue',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '131',
            '--flags' => '128',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals('128', $updateCalls[0]['flags']);
    }

    public function testOptionTag(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setGetDomainRecordResponse([
            'id' => 132,
            'type' => 'CAA',
            'name' => '@',
            'data' => 'letsencrypt.org',
            'flags' => '0',
            'tag' => 'issue',
            'ttl' => 3600,
        ]);

        $this->domainService->setUpdateDomainRecordResponse([
            'id' => 132,
            'type' => 'CAA',
            'name' => '@',
            'data' => 'letsencrypt.org',
            'flags' => '0',
            'tag' => 'issuewild',
            'ttl' => 3600,
        ]);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '132',
            '--tag' => 'issuewild',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $updateCalls = $this->domainService->getUpdateDomainRecordCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals('issuewild', $updateCalls[0]['tag']);
    }
}
