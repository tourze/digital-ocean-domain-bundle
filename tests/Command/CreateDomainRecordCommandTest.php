<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanDomainBundle\Command\CreateDomainRecordCommand;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use DigitalOceanDomainBundle\Tests\Service\TestDomainService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(CreateDomainRecordCommand::class)]
#[RunTestsInSeparateProcesses]
final class CreateDomainRecordCommandTest extends AbstractCommandTestCase
{
    private TestDomainService $domainService;

    private CreateDomainRecordCommand $command;

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

        // 从容器获取Command
        $this->command = self::getService(CreateDomainRecordCommand::class);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccess(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();

        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'priority' => null,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testExecuteWithOptions(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 124,
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            'priority' => 10,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            '--priority' => '10',
            '--ttl' => '3600',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            'priority' => 10,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testExecuteFailure(): void
    {
        $this->domainService->resetCalls();

        // 设置期望的响应数据（空数组表示失败）
        $this->domainService->setCreateDomainRecordResponse([]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('添加域名记录失败', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
    }

    public function testExecuteException(): void
    {
        $this->domainService->resetCalls();

        // 设置期望的异常
        $this->domainService->setCreateDomainRecordException(new TestException('API error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('添加域名记录时发生错误: API error', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 123,
            'type' => 'A',
            'name' => 'test',
            'data' => '192.168.1.1',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'test-domain.com',
            'type' => 'A',
            'name' => 'test',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'test-domain.com',
            'type' => 'A',
            'name' => 'test',
            'data' => '192.168.1.1',
            'priority' => null,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('test-domain.com', $syncCalls[0]);
    }

    public function testArgumentType(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 124,
            'type' => 'AAAA',
            'name' => 'www',
            'data' => '2001:db8::1',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'AAAA',
            'name' => 'www',
            'data' => '2001:db8::1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'AAAA',
            'name' => 'www',
            'data' => '2001:db8::1',
            'priority' => null,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testArgumentName(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 125,
            'type' => 'CNAME',
            'name' => 'api',
            'data' => 'api.example.com',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'CNAME',
            'name' => 'api',
            'data' => 'api.example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'CNAME',
            'name' => 'api',
            'data' => 'api.example.com',
            'priority' => null,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testArgumentData(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 126,
            'type' => 'TXT',
            'name' => '@',
            'data' => 'v=spf1 include:_spf.example.com ~all',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'TXT',
            'name' => '@',
            'data' => 'v=spf1 include:_spf.example.com ~all',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'TXT',
            'name' => '@',
            'data' => 'v=spf1 include:_spf.example.com ~all',
            'priority' => null,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testOptionPriority(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 127,
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            'priority' => 5,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            '--priority' => '5',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            'priority' => 5,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testOptionPort(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 128,
            'type' => 'SRV',
            'name' => '_sip._tcp',
            'data' => 'sip.example.com',
            'priority' => 10,
            'port' => 5060,
            'ttl' => 3600,
            'weight' => 5,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'SRV',
            'name' => '_sip._tcp',
            'data' => 'sip.example.com',
            '--priority' => '10',
            '--port' => '5060',
            '--weight' => '5',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'SRV',
            'name' => '_sip._tcp',
            'data' => 'sip.example.com',
            'priority' => 10,
            'port' => 5060,
            'ttl' => null,
            'weight' => 5,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testOptionTtl(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 129,
            'type' => 'A',
            'name' => 'cache',
            'data' => '192.168.1.10',
            'priority' => null,
            'port' => null,
            'ttl' => 1800,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'cache',
            'data' => '192.168.1.10',
            '--ttl' => '1800',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'A',
            'name' => 'cache',
            'data' => '192.168.1.10',
            'priority' => null,
            'port' => null,
            'ttl' => 1800,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testOptionWeight(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 130,
            'type' => 'SRV',
            'name' => '_http._tcp',
            'data' => 'web.example.com',
            'priority' => 10,
            'port' => 80,
            'ttl' => 3600,
            'weight' => 10,
            'flags' => null,
            'tag' => null,
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'SRV',
            'name' => '_http._tcp',
            'data' => 'web.example.com',
            '--priority' => '10',
            '--port' => '80',
            '--weight' => '10',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'SRV',
            'name' => '_http._tcp',
            'data' => 'web.example.com',
            'priority' => 10,
            'port' => 80,
            'ttl' => null,
            'weight' => 10,
            'flags' => null,
            'tag' => null,
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testOptionFlags(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 131,
            'type' => 'CAA',
            'name' => '@',
            'data' => 'letsencrypt.org',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => 0,
            'tag' => 'issue',
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'CAA',
            'name' => '@',
            'data' => 'letsencrypt.org',
            '--flags' => '0',
            '--tag' => 'issue',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'CAA',
            'name' => '@',
            'data' => 'letsencrypt.org',
            'priority' => null,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => '0',
            'tag' => 'issue',
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }

    public function testOptionTag(): void
    {
        $this->domainService->resetCalls();

        $recordData = [
            'id' => 132,
            'type' => 'CAA',
            'name' => '@',
            'data' => 'ca.example.com',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => 0,
            'tag' => 'issuewild',
        ];

        // 设置期望的响应数据
        $this->domainService->setCreateDomainRecordResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'CAA',
            'name' => '@',
            'data' => 'ca.example.com',
            '--flags' => '0',
            '--tag' => 'issuewild',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $createCalls = $this->domainService->getCreateDomainRecordCalls();
        $this->assertCount(1, $createCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'type' => 'CAA',
            'name' => '@',
            'data' => 'ca.example.com',
            'priority' => null,
            'port' => null,
            'ttl' => null,
            'weight' => null,
            'flags' => '0',
            'tag' => 'issuewild',
        ], $createCalls[0]);

        $syncCalls = $this->domainService->getSyncDomainRecordsCalls();
        $this->assertCount(1, $syncCalls);
        $this->assertEquals('example.com', $syncCalls[0]);
    }
}
