<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Command\SyncDomainsCommand;
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
#[CoversClass(SyncDomainsCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncDomainsCommandTest extends AbstractCommandTestCase
{
    private MockObject&DigitalOceanClient $client;

    private SyncDomainsCommand $command;

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
        $this->command = self::getService(SyncDomainsCommand::class);

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
        $this->setClientResponse([
            'domains' => [
                ['name' => 'example.com', 'ttl' => 1800],
                ['name' => 'test.com', 'ttl' => 1800],
                ['name' => 'demo.com', 'ttl' => 1800],
            ],
            'meta' => ['total' => 3],
            'links' => [],
        ]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步 3 个域名', $display);
        $this->assertStringContainsString('example.com', $display);
        $this->assertStringContainsString('test.com', $display);
        $this->assertStringContainsString('demo.com', $display);
    }

    public function testExecuteWithNoDomains(): void
    {
        $this->setClientResponse([
            'domains' => [],
            'meta' => ['total' => 0],
            'links' => [],
        ]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('没有找到任何域名', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->setClientException(new \RuntimeException('API connection failed'));

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('同步域名时发生错误: API connection failed', $this->commandTester->getDisplay());
    }
}
