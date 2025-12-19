<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Command\SyncDomainRecordsCommand;
use DigitalOceanDomainBundle\Entity\Domain;
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
#[CoversClass(SyncDomainRecordsCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncDomainRecordsCommandTest extends AbstractCommandTestCase
{
    private MockObject&DigitalOceanClient $client;

    private MockObject&DomainRepository $domainRepository;

    private SyncDomainRecordsCommand $command;

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
        $this->domainRepository = $this->createMock(DomainRepository::class);
        $domainRecordRepository = $this->createMock(DomainRecordRepository::class);

        // 将 Mock 注入容器
        $container = self::getContainer();
        $container->set(DigitalOceanClient::class, $this->client);
        $container->set(DigitalOceanConfigService::class, $configService);
        $container->set(DomainRepository::class, $this->domainRepository);
        $container->set(DomainRecordRepository::class, $domainRecordRepository);

        // 从容器获取 Command（使用真正的 DomainService）
        $this->command = self::getService(SyncDomainRecordsCommand::class);

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

    public function testExecuteWithSpecificDomain(): void
    {
        $this->setClientResponse([
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
            'links' => [],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步 2 条记录', $display);
    }

    public function testExecuteWithAllDomains(): void
    {
        $domain1 = new Domain();
        $domain1->setName('mycompany.com');

        $domain2 = new Domain();
        $domain2->setName('testsite.org');

        $domain3 = new Domain();
        $domain3->setName('webapp.net');

        $this->domainRepository->method('findAll')
            ->willReturn([$domain1, $domain2, $domain3]);

        // 设置 client 返回不同域名的记录
        $this->client->method('request')
            ->willReturnCallback(function ($request) {
                return [
                    'domain_records' => [
                        [
                            'id' => 123,
                            'type' => 'A',
                            'name' => 'www',
                            'data' => '192.168.1.1',
                            'ttl' => 3600,
                        ],
                    ],
                    'meta' => ['total' => 1],
                    'links' => [],
                ];
            });

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步总计 3 条记录', $display);
    }

    public function testExecuteWithNoDomains(): void
    {
        $this->domainRepository->method('findAll')->willReturn([]);

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有找到任何域名', $display);
    }

    public function testExecuteWithPartialErrors(): void
    {
        $domain1 = new Domain();
        $domain1->setName('example.com');

        $domain2 = new Domain();
        $domain2->setName('test.com');

        $this->domainRepository->method('findAll')
            ->willReturn([$domain1, $domain2]);

        // 第一次调用返回成功，第二次抛出异常
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_records' => [
                            [
                                'id' => 123,
                                'type' => 'A',
                                'name' => 'www',
                                'data' => '192.168.1.1',
                                'ttl' => 3600,
                            ],
                        ],
                        'meta' => ['total' => 1],
                        'links' => [],
                    ];
                }
                throw new \RuntimeException('API error');
            });

        $this->commandTester->execute([]);

        // 命令应该返回失败因为有错误
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('同步过程中发生 1 个错误', $display);
    }

    public function testExecuteWithSpecificDomainException(): void
    {
        $this->setClientException(new \RuntimeException('Sync error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('同步域名记录时发生错误: Sync error', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        $this->setClientResponse([
            'domain_records' => [
                [
                    'id' => 456,
                    'type' => 'CNAME',
                    'name' => 'api',
                    'data' => 'api.example.com',
                    'ttl' => 3600,
                ],
                [
                    'id' => 457,
                    'type' => 'TXT',
                    'name' => '@',
                    'data' => 'verification-token',
                    'ttl' => 3600,
                ],
            ],
            'meta' => ['total' => 2],
            'links' => [],
        ]);

        $this->commandTester->execute([
            'domain' => 'api-domain.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步 2 条记录', $display);
        $this->assertStringContainsString('api', $display);
        $this->assertStringContainsString('verification-token', $display);
    }
}
