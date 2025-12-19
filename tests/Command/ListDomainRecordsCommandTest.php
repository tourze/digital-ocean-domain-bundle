<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Command\ListDomainRecordsCommand;
use DigitalOceanDomainBundle\Entity\DomainRecord;
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
#[CoversClass(ListDomainRecordsCommand::class)]
#[RunTestsInSeparateProcesses]
final class ListDomainRecordsCommandTest extends AbstractCommandTestCase
{
    private MockObject&DigitalOceanClient $client;

    private MockObject&DomainRecordRepository $domainRecordRepository;

    private ListDomainRecordsCommand $command;

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
        $this->domainRecordRepository = $this->createMock(DomainRecordRepository::class);

        // 将 Mock 注入容器
        $container = self::getContainer();
        $container->set(DigitalOceanClient::class, $this->client);
        $container->set(DigitalOceanConfigService::class, $configService);
        $container->set(DomainRepository::class, $domainRepository);
        $container->set(DomainRecordRepository::class, $this->domainRecordRepository);

        // 从容器获取 Command（使用真正的 DomainService）
        $this->command = self::getService(ListDomainRecordsCommand::class);

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

    private function createDomainRecord(int $recordId, string $type, string $name, string $data, int $ttl = 3600): DomainRecord
    {
        $record = new DomainRecord();
        $record->setRecordId($recordId);
        $record->setType($type);
        $record->setName($name);
        $record->setData($data);
        $record->setTtl($ttl);

        return $record;
    }

    public function testExecuteWithRemoteApi(): void
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
    }

    public function testExecuteWithLocalDatabase(): void
    {
        $record1 = $this->createDomainRecord(123, 'A', 'www', '192.168.1.1');
        $record2 = $this->createDomainRecord(124, 'CNAME', 'blog', 'www.example.com');

        $this->domainRecordRepository->method('findBy')
            ->willReturn([$record1, $record2]);
        $this->domainRecordRepository->method('count')
            ->willReturn(2);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
        $this->assertStringContainsString('blog', $display);
        $this->assertStringContainsString('共 2 条记录', $display);
    }

    public function testExecuteWithTypeFilter(): void
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
    }

    public function testExecuteWithNameFilter(): void
    {
        $record = $this->createDomainRecord(123, 'A', 'www', '192.168.1.1');

        $this->domainRecordRepository->method('findByDomainAndName')
            ->willReturn([$record]);
        $this->domainRecordRepository->method('count')
            ->willReturn(1);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--name' => 'www',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
    }

    public function testExecuteWithNoRecords(): void
    {
        $this->domainRecordRepository->method('findBy')
            ->willReturn([]);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('没有找到任何记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->domainRecordRepository->method('findBy')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('获取域名记录时发生错误: Database error', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        $record = $this->createDomainRecord(123, 'A', 'api', '192.168.1.100');

        $this->domainRecordRepository->method('findBy')
            ->willReturn([$record]);
        $this->domainRecordRepository->method('count')
            ->willReturn(1);

        $this->commandTester->execute([
            'domain' => 'test-domain.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('api', $display);
        $this->assertStringContainsString('192.168.1.100', $display);
    }

    public function testOptionType(): void
    {
        $record = $this->createDomainRecord(124, 'MX', '@', 'mail.example.com');

        $this->domainRecordRepository->method('findBy')
            ->willReturn([$record]);
        $this->domainRecordRepository->method('count')
            ->willReturn(1);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--type' => 'MX',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('MX', $display);
    }

    public function testOptionName(): void
    {
        $record = $this->createDomainRecord(125, 'CNAME', 'cdn', 'cdn.example.com');

        $this->domainRecordRepository->method('findByDomainAndName')
            ->willReturn([$record]);
        $this->domainRecordRepository->method('count')
            ->willReturn(1);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--name' => 'cdn',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('cdn', $display);
        $this->assertStringContainsString('cdn.example.com', $display);
    }

    public function testOptionRemote(): void
    {
        $this->setClientResponse([
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
    }

    public function testOptionPage(): void
    {
        $this->setClientResponse([
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
    }

    public function testOptionLimit(): void
    {
        $this->setClientResponse([
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
    }
}
