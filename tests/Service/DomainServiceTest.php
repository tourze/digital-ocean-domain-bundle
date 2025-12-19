<?php

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/*
 * 注意：此包需要添加依赖 "tourze/http-client-bundle": "1.1.*" 到 composer.json
 * 才能正确解析 HttpClientBundle\Request\RequestInterface
 */

/**
 * @internal
 */
#[CoversClass(DomainService::class)]
#[RunTestsInSeparateProcesses]
final class DomainServiceTest extends AbstractIntegrationTestCase
{
    private DomainService $service;

    private MockObject&DigitalOceanClient $client;

    private DigitalOceanConfig $config;

    protected function onSetUp(): void
    {
        $this->config = new DigitalOceanConfig();
        $this->config->setApiKey('test_api_key');

        // 创建Mock对象
        $this->client = $this->createMock(DigitalOceanClient::class);
        $configService = $this->createMock(DigitalOceanConfigService::class);
        $domainRepository = $this->createMock(DomainRepository::class);
        $domainRecordRepository = $this->createMock(DomainRecordRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // 设置ConfigService返回测试配置
        $configService->method('getConfig')->willReturn($this->config);

        // 将 Mock 注入容器后，从容器获取被测服务
        $container = self::getContainer();
        $container->set(DigitalOceanClient::class, $this->client);
        $container->set(DigitalOceanConfigService::class, $configService);
        $container->set(DomainRepository::class, $domainRepository);
        $container->set(DomainRecordRepository::class, $domainRecordRepository);
        // 避免替换已初始化的全局 LoggerInterface 服务

        $this->service = self::getService(DomainService::class);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function setClientResponse(array $response): void
    {
        $this->client->method('request')
            ->willReturn($response)
        ;
    }

    private function setClientException(\Exception $exception): void
    {
        $this->client->method('request')
            ->willThrowException($exception)
        ;
    }

    public function testListDomains(): void
    {
        $expectedResponse = [
            'domains' => [
                ['name' => 'example.com', 'ttl' => 1800],
                ['name' => 'example.org', 'ttl' => 3600],
            ],
            'meta' => ['total' => 2],
            'links' => [],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->listDomains();

        $this->assertEquals($expectedResponse['domains'], $result['domains']);
        $this->assertEquals($expectedResponse['meta'], $result['meta']);
        $this->assertEquals($expectedResponse['links'], $result['links']);
    }

    public function testListDomainsWithPagination(): void
    {
        $expectedResponse = [
            'domains' => [
                ['name' => 'example.com', 'ttl' => 1800],
            ],
            'meta' => ['total' => 1],
            'links' => ['pages' => ['next' => 'https://api.digitalocean.com/v2/domains?page=2']],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->listDomains(2, 1);

        $this->assertEquals($expectedResponse['domains'], $result['domains']);
        $this->assertEquals($expectedResponse['meta'], $result['meta']);
        $this->assertEquals($expectedResponse['links'], $result['links']);
    }

    public function testGetDomain(): void
    {
        $expectedResponse = [
            'domain' => [
                'name' => 'example.com',
                'ttl' => 1800,
                'zone_file' => '...',
            ],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->getDomain('example.com');

        $this->assertEquals($expectedResponse['domain'], $result);
    }

    public function testGetDomainWithEmptyResponse(): void
    {
        $this->setClientResponse([]);

        $result = $this->service->getDomain('example.com');

        $this->assertEquals([], $result);
    }

    public function testCreateDomain(): void
    {
        $expectedResponse = [
            'domain' => [
                'name' => 'example.com',
                'ttl' => 1800,
                'zone_file' => '...',
            ],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->createDomain('example.com', '192.168.1.1');

        $this->assertEquals($expectedResponse['domain'], $result);
    }

    public function testDeleteDomainSuccess(): void
    {
        $this->setClientResponse([]);

        $result = $this->service->deleteDomain('example.com');

        $this->assertTrue($result);
    }

    public function testDeleteDomainFailure(): void
    {
        $this->setClientException(new \RuntimeException('API Error'));

        // Logger error 调用已通过匿名类实现自动记录

        $result = $this->service->deleteDomain('example.com');

        $this->assertFalse($result);
    }

    public function testListDomainRecords(): void
    {
        $expectedResponse = [
            'domain_records' => [
                [
                    'id' => 1,
                    'type' => 'A',
                    'name' => 'www',
                    'data' => '192.168.1.1',
                ],
                [
                    'id' => 2,
                    'type' => 'MX',
                    'name' => '@',
                    'data' => 'mail.example.com',
                ],
            ],
            'meta' => ['total' => 2],
            'links' => [],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->listDomainRecords('example.com');

        $this->assertEquals($expectedResponse['domain_records'], $result['domain_records']);
        $this->assertEquals($expectedResponse['meta'], $result['meta']);
        $this->assertEquals($expectedResponse['links'], $result['links']);
    }

    public function testGetDomainRecord(): void
    {
        $expectedResponse = [
            'domain_record' => [
                'id' => 12345,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.1',
                'ttl' => 1800,
            ],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->getDomainRecord('example.com', 12345);

        $this->assertEquals($expectedResponse['domain_record'], $result);
    }

    public function testCreateDomainRecord(): void
    {
        $expectedResponse = [
            'domain_record' => [
                'id' => 12345,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.1',
                'ttl' => 1800,
            ],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->createDomainRecord(
            'example.com',
            'A',
            'www',
            '192.168.1.1',
            null,
            null,
            1800
        );

        $this->assertEquals($expectedResponse['domain_record'], $result);
    }

    public function testCreateDomainRecordWithAllParams(): void
    {
        $expectedResponse = [
            'domain_record' => [
                'id' => 12345,
                'type' => 'MX',
                'name' => 'mail',
                'data' => 'mail.example.com',
                'priority' => 10,
                'port' => null,
                'ttl' => 3600,
                'weight' => null,
                'flags' => null,
                'tag' => null,
            ],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->createDomainRecord(
            'example.com',
            'MX',
            'mail',
            'mail.example.com',
            10,
            null,
            3600
        );

        $this->assertEquals($expectedResponse['domain_record'], $result);
    }

    public function testDeleteDomainRecordSuccess(): void
    {
        $this->setClientResponse([]);

        $result = $this->service->deleteDomainRecord('example.com', 12345);

        $this->assertTrue($result);
    }

    public function testDeleteDomainRecordFailure(): void
    {
        $this->setClientException(new \RuntimeException('API Error'));

        // Logger error 调用已通过匿名类实现自动记录

        $result = $this->service->deleteDomainRecord('example.com', 12345);

        $this->assertFalse($result);
    }

    public function testUpdateDomainRecord(): void
    {
        $expectedResponse = [
            'domain_record' => [
                'id' => 12345,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.2',
                'ttl' => 1800,
                'priority' => null,
                'port' => null,
                'weight' => null,
                'flags' => null,
                'tag' => null,
            ],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->updateDomainRecord(
            'example.com',
            12345,
            'A',
            'www',
            '192.168.1.2',
            null,
            null,
            1800
        );

        $this->assertEquals($expectedResponse['domain_record'], $result);
    }

    public function testUpdateDomainRecordWithAllParams(): void
    {
        $expectedResponse = [
            'domain_record' => [
                'id' => 12345,
                'type' => 'SRV',
                'name' => '_service._tcp',
                'data' => 'server.example.com',
                'priority' => 10,
                'port' => 8080,
                'ttl' => 3600,
                'weight' => 5,
                'flags' => null,
                'tag' => 'service',
            ],
        ];

        $this->setClientResponse($expectedResponse);

        $result = $this->service->updateDomainRecord(
            'example.com',
            12345,
            'SRV',
            '_service._tcp',
            'server.example.com',
            10,
            8080,
            3600,
            5,
            null,
            'service'
        );

        $this->assertEquals($expectedResponse['domain_record'], $result);
    }

    public function testSyncDomains(): void
    {
        $domainsResponse = [
            'domains' => [
                ['name' => 'example.com', 'ttl' => 1800],
                ['name' => 'example.org', 'ttl' => 3600],
            ],
            'meta' => ['total' => 2],
            'links' => [],
        ];

        $this->setClientResponse($domainsResponse);

        // domainRepository 已经在匿名类中默认返回 null

        $result = $this->service->syncDomains();

        $this->assertCount(2, $result);
    }

    public function testSyncDomainRecords(): void
    {
        $recordsResponse = [
            'domain_records' => [
                [
                    'id' => 1,
                    'type' => 'A',
                    'name' => 'www',
                    'data' => '192.168.1.1',
                    'priority' => null,
                    'port' => null,
                    'ttl' => 1800,
                    'weight' => null,
                    'flags' => null,
                    'tag' => null,
                ],
                [
                    'id' => 2,
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
            ],
            'meta' => ['total' => 2],
            'links' => [],
        ];

        $this->setClientResponse($recordsResponse);

        // domainRecordRepository 已经在匿名类中默认返回 null

        $result = $this->service->syncDomainRecords('example.com');

        $this->assertCount(2, $result);
    }
}
