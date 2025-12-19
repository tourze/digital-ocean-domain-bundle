<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Command\CreateDomainRecordCommand;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
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
    private MockObject&DigitalOceanClient $client;

    private CreateDomainRecordCommand $command;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 创建配置
        $config = new DigitalOceanConfig();
        $config->setApiKey('test_api_key');

        // Mock 网络层（DigitalOceanClient）
        $this->client = $this->createMock(DigitalOceanClient::class);
        $configService = $this->createMock(DigitalOceanConfigService::class);
        $configService->method('getConfig')->willReturn($config);

        // Mock Repository（避免真实数据库操作）
        $domainRepository = $this->createMock(DomainRepository::class);
        $domainRecordRepository = $this->createMock(DomainRecordRepository::class);

        // 将 Mock 注入容器
        $container = self::getContainer();
        $container->set(DigitalOceanClient::class, $this->client);
        $container->set(DigitalOceanConfigService::class, $configService);
        $container->set(DomainRepository::class, $domainRepository);
        $container->set(DomainRecordRepository::class, $domainRecordRepository);

        // 从容器获取 Command（使用真正的 DomainService）
        $this->command = self::getService(CreateDomainRecordCommand::class);

        $application = new Application();
        $application->addCommand($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function setClientResponse(array $response): void
    {
        $this->client->method('request')->willReturn($response);
    }

    private function setClientException(\Throwable $exception): void
    {
        $this->client->method('request')->willThrowException($exception);
    }

    public function testExecuteSuccess(): void
    {
        $recordData = [
            'domain_record' => [
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
            ],
        ];

        $this->setClientResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithOptions(): void
    {
        $recordData = [
            'domain_record' => [
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
            ],
        ];

        $this->setClientResponse($recordData);

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
    }

    public function testExecuteFailure(): void
    {
        // 空响应表示失败
        $this->setClientResponse(['domain_record' => []]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('添加域名记录失败', $this->commandTester->getDisplay());
    }

    public function testExecuteException(): void
    {
        $this->setClientException(new \RuntimeException('API error'));

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
        $recordData = [
            'domain_record' => [
                'id' => 123,
                'type' => 'A',
                'name' => 'test',
                'data' => '192.168.1.1',
                'ttl' => 3600,
            ],
        ];

        $this->setClientResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'test-domain.com',
            'type' => 'A',
            'name' => 'test',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testArgumentType(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 124,
                'type' => 'AAAA',
                'name' => 'www',
                'data' => '2001:db8::1',
                'ttl' => 3600,
            ],
        ];

        $this->setClientResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'AAAA',
            'name' => 'www',
            'data' => '2001:db8::1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testArgumentName(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 125,
                'type' => 'CNAME',
                'name' => 'api',
                'data' => 'api.example.com',
                'ttl' => 3600,
            ],
        ];

        $this->setClientResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'CNAME',
            'name' => 'api',
            'data' => 'api.example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testArgumentData(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 126,
                'type' => 'TXT',
                'name' => '@',
                'data' => 'v=spf1 include:_spf.example.com ~all',
                'ttl' => 3600,
            ],
        ];

        $this->setClientResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'TXT',
            'name' => '@',
            'data' => 'v=spf1 include:_spf.example.com ~all',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionPriority(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 127,
                'type' => 'MX',
                'name' => '@',
                'data' => 'mail.example.com',
                'priority' => 5,
                'ttl' => 3600,
            ],
        ];

        $this->setClientResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            '--priority' => '5',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionPort(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 128,
                'type' => 'SRV',
                'name' => '_sip._tcp',
                'data' => 'sip.example.com',
                'priority' => 10,
                'port' => 5060,
                'ttl' => 3600,
                'weight' => 5,
            ],
        ];

        $this->setClientResponse($recordData);

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
    }

    public function testOptionTtl(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 129,
                'type' => 'A',
                'name' => 'cache',
                'data' => '192.168.1.10',
                'ttl' => 1800,
            ],
        ];

        $this->setClientResponse($recordData);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'cache',
            'data' => '192.168.1.10',
            '--ttl' => '1800',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionWeight(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 130,
                'type' => 'SRV',
                'name' => '_http._tcp',
                'data' => 'web.example.com',
                'priority' => 10,
                'port' => 80,
                'ttl' => 3600,
                'weight' => 10,
            ],
        ];

        $this->setClientResponse($recordData);

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
    }

    public function testOptionFlags(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 131,
                'type' => 'CAA',
                'name' => '@',
                'data' => 'letsencrypt.org',
                'ttl' => 3600,
                'flags' => 0,
                'tag' => 'issue',
            ],
        ];

        $this->setClientResponse($recordData);

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
    }

    public function testOptionTag(): void
    {
        $recordData = [
            'domain_record' => [
                'id' => 132,
                'type' => 'CAA',
                'name' => '@',
                'data' => 'ca.example.com',
                'ttl' => 3600,
                'flags' => 0,
                'tag' => 'issuewild',
            ],
        ];

        $this->setClientResponse($recordData);

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
    }
}
