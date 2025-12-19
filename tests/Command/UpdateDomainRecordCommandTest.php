<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Command\UpdateDomainRecordCommand;
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
#[CoversClass(UpdateDomainRecordCommand::class)]
#[RunTestsInSeparateProcesses]
final class UpdateDomainRecordCommandTest extends AbstractCommandTestCase
{
    private MockObject&DigitalOceanClient $client;

    private MockObject&DomainRecordRepository $domainRecordRepository;

    private UpdateDomainRecordCommand $command;

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
        $this->command = self::getService(UpdateDomainRecordCommand::class);

        $application = new Application();
        $application->addCommand($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    private function createDomainRecord(
        string $domainName,
        int $recordId,
        string $type,
        string $name,
        string $data,
        ?int $priority = null,
        ?int $port = null,
        int $ttl = 3600,
    ): DomainRecord {
        $record = new DomainRecord();
        $record->setDomainName($domainName);
        $record->setRecordId($recordId);
        $record->setType($type);
        $record->setName($name);
        $record->setData($data);
        $record->setPriority($priority);
        $record->setPort($port);
        $record->setTtl($ttl);

        return $record;
    }

    public function testExecuteWithLocalData(): void
    {
        $mockRecord = $this->createDomainRecord(
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

        // Client 返回更新结果
        $this->client->method('request')->willReturn([
            'domain_record' => [
                'id' => 123,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.1',
                'ttl' => 3600,
            ],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--local' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithLocalDataNotFound(): void
    {
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
        // 设置 client 返回当前记录和更新后记录
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                // 第一次调用是 getDomainRecord
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 123,
                            'type' => 'A',
                            'name' => 'www',
                            'data' => '192.168.1.1',
                            'ttl' => 3600,
                        ],
                    ];
                }
                // 第二次调用是 updateDomainRecord
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 123,
                            'type' => 'A',
                            'name' => 'www',
                            'data' => '192.168.1.2',
                            'ttl' => 3600,
                        ],
                    ];
                }
                // 第三次调用是 syncDomainRecords (listDomainRecords)
                return [
                    'domain_records' => [],
                    'meta' => ['total' => 0],
                    'links' => [],
                ];
            });

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
    }

    public function testExecuteWithRemoteDataCancelled(): void
    {
        $this->client->method('request')->willReturn([
            'domain_record' => [
                'id' => 123,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.1',
                'ttl' => 3600,
            ],
        ]);

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--data' => '192.168.1.2',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('操作已取消', $this->commandTester->getDisplay());
    }

    public function testExecuteWithRemoteDataNotFound(): void
    {
        $this->client->method('request')->willReturn([
            'domain_record' => [],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('找不到远程记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->client->method('request')
            ->willThrowException(new \RuntimeException('API error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('更新域名记录时发生错误: API error', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 456,
                            'type' => 'CNAME',
                            'name' => 'api',
                            'data' => 'api.example.com',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 456,
                            'type' => 'CNAME',
                            'name' => 'api',
                            'data' => 'api.test-domain.com',
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'test-domain.com',
            'record_id' => '456',
            '--data' => 'api.test-domain.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testArgumentRecordId(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 789,
                            'type' => 'TXT',
                            'name' => '@',
                            'data' => 'v=spf1 include:_spf.example.com ~all',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 789,
                            'type' => 'TXT',
                            'name' => '@',
                            'data' => 'v=spf1 include:_spf.example.com -all',
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '789',
            '--data' => 'v=spf1 include:_spf.example.com -all',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionType(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 123,
                            'type' => 'A',
                            'name' => 'www',
                            'data' => '192.168.1.1',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 123,
                            'type' => 'AAAA',
                            'name' => 'www',
                            'data' => '2001:db8::1',
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--type' => 'AAAA',
            '--data' => '2001:db8::1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionData(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 124,
                            'type' => 'A',
                            'name' => 'test',
                            'data' => '192.168.1.1',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 124,
                            'type' => 'A',
                            'name' => 'test',
                            'data' => '10.0.0.1',
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '124',
            '--data' => '10.0.0.1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionPriority(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 125,
                            'type' => 'MX',
                            'name' => '@',
                            'data' => 'mail.example.com',
                            'priority' => 10,
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 125,
                            'type' => 'MX',
                            'name' => '@',
                            'data' => 'mail.example.com',
                            'priority' => 5,
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '125',
            '--priority' => '5',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionTtl(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 126,
                            'type' => 'A',
                            'name' => 'cache',
                            'data' => '192.168.1.10',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 126,
                            'type' => 'A',
                            'name' => 'cache',
                            'data' => '192.168.1.10',
                            'ttl' => 1800,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '126',
            '--ttl' => '1800',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionLocal(): void
    {
        $mockRecord = $this->createDomainRecord(
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

        $this->client->method('request')->willReturn([
            'domain_record' => [
                'id' => 127,
                'type' => 'CNAME',
                'name' => 'cdn',
                'data' => 'cdn.example.com',
                'ttl' => 3600,
            ],
        ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '127',
            '--local' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionName(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 128,
                            'type' => 'CNAME',
                            'name' => 'www',
                            'data' => 'example.com',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 128,
                            'type' => 'CNAME',
                            'name' => 'blog',
                            'data' => 'example.com',
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '128',
            '--name' => 'blog',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionPort(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 129,
                            'type' => 'SRV',
                            'name' => '_http._tcp',
                            'data' => 'web.example.com',
                            'priority' => 10,
                            'port' => 80,
                            'weight' => 5,
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 129,
                            'type' => 'SRV',
                            'name' => '_http._tcp',
                            'data' => 'web.example.com',
                            'priority' => 10,
                            'port' => 443,
                            'weight' => 5,
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '129',
            '--port' => '443',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionWeight(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 130,
                            'type' => 'SRV',
                            'name' => '_https._tcp',
                            'data' => 'web.example.com',
                            'priority' => 10,
                            'port' => 443,
                            'weight' => 5,
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 130,
                            'type' => 'SRV',
                            'name' => '_https._tcp',
                            'data' => 'web.example.com',
                            'priority' => 10,
                            'port' => 443,
                            'weight' => 10,
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '130',
            '--weight' => '10',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionFlags(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 131,
                            'type' => 'CAA',
                            'name' => '@',
                            'data' => 'letsencrypt.org',
                            'flags' => '0',
                            'tag' => 'issue',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 131,
                            'type' => 'CAA',
                            'name' => '@',
                            'data' => 'letsencrypt.org',
                            'flags' => '128',
                            'tag' => 'issue',
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '131',
            '--flags' => '128',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }

    public function testOptionTag(): void
    {
        $callCount = 0;
        $this->client->method('request')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 132,
                            'type' => 'CAA',
                            'name' => '@',
                            'data' => 'letsencrypt.org',
                            'flags' => '0',
                            'tag' => 'issue',
                            'ttl' => 3600,
                        ],
                    ];
                }
                if (2 === $callCount) {
                    return [
                        'domain_record' => [
                            'id' => 132,
                            'type' => 'CAA',
                            'name' => '@',
                            'data' => 'letsencrypt.org',
                            'flags' => '0',
                            'tag' => 'issuewild',
                            'ttl' => 3600,
                        ],
                    ];
                }

                return ['domain_records' => [], 'meta' => ['total' => 0], 'links' => []];
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '132',
            '--tag' => 'issuewild',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功更新域名记录', $this->commandTester->getDisplay());
    }
}
