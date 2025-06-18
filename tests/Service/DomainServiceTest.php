<?php

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DomainServiceTest extends TestCase
{
    private DomainService $service;
    private MockObject&DigitalOceanClient $client;
    private MockObject&DigitalOceanConfigService $configService;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&DomainRepository $domainRepository;
    private MockObject&DomainRecordRepository $domainRecordRepository;
    private MockObject&LoggerInterface $logger;
    private DigitalOceanConfig $config;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DigitalOceanClient::class);
        $this->configService = $this->createMock(DigitalOceanConfigService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->domainRepository = $this->createMock(DomainRepository::class);
        $this->domainRecordRepository = $this->createMock(DomainRecordRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->config = new DigitalOceanConfig();
        $this->config->setApiKey('test_api_key');

        $this->configService->method('getConfig')->willReturn($this->config);

        $this->service = new DomainService(
            $this->client,
            $this->configService,
            $this->entityManager,
            $this->domainRepository,
            $this->domainRecordRepository,
            $this->logger
        );
    }

    public function testListDomains(): void
    {
        $expectedResponse = [
            'domains' => [
                ['name' => 'example.com', 'ttl' => 1800],
                ['name' => 'example.org', 'ttl' => 3600]
            ],
            'meta' => ['total' => 2],
            'links' => []
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

        $result = $this->service->listDomains();

        $this->assertEquals($expectedResponse['domains'], $result['domains']);
        $this->assertEquals($expectedResponse['meta'], $result['meta']);
        $this->assertEquals($expectedResponse['links'], $result['links']);
    }

    public function testListDomainsWithPagination(): void
    {
        $expectedResponse = [
            'domains' => [
                ['name' => 'example.com', 'ttl' => 1800]
            ],
            'meta' => ['total' => 1],
            'links' => ['pages' => ['next' => 'https://api.digitalocean.com/v2/domains?page=2']]
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

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
                'zone_file' => '...'
            ]
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

        $result = $this->service->getDomain('example.com');

        $this->assertEquals($expectedResponse['domain'], $result);
    }

    public function testGetDomainWithEmptyResponse(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willReturn([]);

        $result = $this->service->getDomain('example.com');

        $this->assertEquals([], $result);
    }

    public function testCreateDomain(): void
    {
        $expectedResponse = [
            'domain' => [
                'name' => 'example.com',
                'ttl' => 1800,
                'zone_file' => '...'
            ]
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

        $result = $this->service->createDomain('example.com', '192.168.1.1');

        $this->assertEquals($expectedResponse['domain'], $result);
    }

    public function testDeleteDomain_Success(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willReturn(null);

        $result = $this->service->deleteDomain('example.com');

        $this->assertTrue($result);
    }

    public function testDeleteDomain_Failure(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->once())
            ->method('error');

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
                    'data' => '192.168.1.1'
                ],
                [
                    'id' => 2,
                    'type' => 'MX',
                    'name' => '@',
                    'data' => 'mail.example.com'
                ]
            ],
            'meta' => ['total' => 2],
            'links' => []
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

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
                'ttl' => 1800
            ]
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

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
                'ttl' => 1800
            ]
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

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
                'tag' => null
            ]
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($expectedResponse);

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

    public function testDeleteDomainRecord_Success(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willReturn(null);

        $result = $this->service->deleteDomainRecord('example.com', 12345);

        $this->assertTrue($result);
    }

    public function testDeleteDomainRecord_Failure(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->once())
            ->method('error');

        $result = $this->service->deleteDomainRecord('example.com', 12345);

        $this->assertFalse($result);
    }

    public function testPrepareRequestWithoutConfig(): void
    {
        // 模拟configService返回null
        $configService = $this->createMock(DigitalOceanConfigService::class);
        $configService->method('getConfig')->willReturn(null);

        $service = new DomainService(
            $this->client,
            $configService,
            $this->entityManager,
            $this->domainRepository,
            $this->domainRecordRepository,
            $this->logger
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('未配置 DigitalOcean API Key');

        // 调用会触发prepareRequest的方法
        $service->listDomains();
    }

    public function testSyncDomains(): void
    {
        $domainsResponse = [
            'domains' => [
                ['name' => 'example.com', 'ttl' => 1800],
                ['name' => 'example.org', 'ttl' => 3600]
            ],
            'meta' => ['total' => 2],
            'links' => []
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($domainsResponse);

        $this->domainRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

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
                    'tag' => null
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
                    'tag' => null
                ]
            ],
            'meta' => ['total' => 2],
            'links' => []
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->willReturn($recordsResponse);

        $this->domainRecordRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->syncDomainRecords('example.com');

        $this->assertCount(2, $result);
    }
}
