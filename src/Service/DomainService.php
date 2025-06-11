<?php

namespace DigitalOceanDomainBundle\Service;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use DigitalOceanDomainBundle\Request\CreateDomainRecordRequest;
use DigitalOceanDomainBundle\Request\CreateDomainRequest;
use DigitalOceanDomainBundle\Request\DeleteDomainRecordRequest;
use DigitalOceanDomainBundle\Request\DeleteDomainRequest;
use DigitalOceanDomainBundle\Request\GetDomainRecordRequest;
use DigitalOceanDomainBundle\Request\GetDomainRequest;
use DigitalOceanDomainBundle\Request\ListDomainRecordsRequest;
use DigitalOceanDomainBundle\Request\ListDomainsRequest;
use DigitalOceanDomainBundle\Request\UpdateDomainRecordRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\Symfony\AopDoctrineBundle\Attribute\Transactional;

class DomainService
{
    public function __construct(
        private readonly DigitalOceanClient $client,
        private readonly DigitalOceanConfigService $configService,
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainRepository $domainRepository,
        private readonly DomainRecordRepository $domainRecordRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 为请求设置API Key
     */
    private function prepareRequest($request): void
    {
        $config = $this->configService->getConfig();
        if ($config === null) {
            throw new \RuntimeException('未配置 DigitalOcean API Key');
        }

        $request->setApiKey($config->getApiKey());
    }

    /**
     * 获取域名列表
     */
    public function listDomains(int $page = 1, int $perPage = 20): array
    {
        $request = (new ListDomainsRequest())
            ->setPage($page)
            ->setPerPage($perPage);

        $this->prepareRequest($request);

        $response = $this->client->request($request);

        return [
            'domains' => $response['domains'] ?? [],
            'meta' => $response['meta'] ?? [],
            'links' => $response['links'] ?? [],
        ];
    }

    /**
     * 获取单个域名信息
     */
    public function getDomain(string $domainName): array
    {
        $request = new GetDomainRequest($domainName);
        $this->prepareRequest($request);

        $response = $this->client->request($request);

        return $response['domain'] ?? [];
    }

    /**
     * 创建新域名
     */
    public function createDomain(string $domainName, ?string $ipAddress = null): array
    {
        $request = new CreateDomainRequest($domainName, $ipAddress);
        $this->prepareRequest($request);

        $response = $this->client->request($request);

        return $response['domain'] ?? [];
    }

    /**
     * 删除域名
     */
    public function deleteDomain(string $domainName): bool
    {
        $request = new DeleteDomainRequest($domainName);
        $this->prepareRequest($request);

        try {
            $this->client->request($request);
            return true;
        } catch  (\Throwable $e) {
            $this->logger->error('删除域名失败', [
                'domainName' => $domainName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 获取域名记录列表
     */
    public function listDomainRecords(string $domainName, int $page = 1, int $perPage = 20): array
    {
        $request = new ListDomainRecordsRequest($domainName);
        $request->setPage($page)->setPerPage($perPage);

        $this->prepareRequest($request);

        $response = $this->client->request($request);

        return [
            'domain_records' => $response['domain_records'] ?? [],
            'meta' => $response['meta'] ?? [],
            'links' => $response['links'] ?? [],
        ];
    }

    /**
     * 获取单个域名记录
     */
    public function getDomainRecord(string $domainName, int $recordId): array
    {
        $request = new GetDomainRecordRequest($domainName, $recordId);
        $this->prepareRequest($request);

        $response = $this->client->request($request);

        return $response['domain_record'] ?? [];
    }

    /**
     * 创建域名记录
     */
    public function createDomainRecord(
        string $domainName,
        string $type,
        string $name,
        string $data,
        ?int $priority = null,
        ?int $port = null,
        ?int $ttl = null,
        ?int $weight = null,
        ?string $flags = null,
        ?string $tag = null
    ): array {
        $request = new CreateDomainRecordRequest($domainName, $type, $name, $data);

        if ($priority !== null) {
            $request->setPriority($priority);
        }

        if ($port !== null) {
            $request->setPort($port);
        }

        if ($ttl !== null) {
            $request->setTtl($ttl);
        }

        if ($weight !== null) {
            $request->setWeight($weight);
        }

        if ($flags !== null) {
            $request->setFlags($flags);
        }

        if ($tag !== null) {
            $request->setTag($tag);
        }

        $this->prepareRequest($request);

        $response = $this->client->request($request);

        return $response['domain_record'] ?? [];
    }

    /**
     * 更新域名记录
     */
    public function updateDomainRecord(
        string $domainName,
        int $recordId,
        string $type,
        string $name,
        string $data,
        ?int $priority = null,
        ?int $port = null,
        ?int $ttl = null,
        ?int $weight = null,
        ?string $flags = null,
        ?string $tag = null
    ): array {
        $request = new UpdateDomainRecordRequest($domainName, $recordId, $type, $name, $data);

        if ($priority !== null) {
            $request->setPriority($priority);
        }

        if ($port !== null) {
            $request->setPort($port);
        }

        if ($ttl !== null) {
            $request->setTtl($ttl);
        }

        if ($weight !== null) {
            $request->setWeight($weight);
        }

        if ($flags !== null) {
            $request->setFlags($flags);
        }

        if ($tag !== null) {
            $request->setTag($tag);
        }

        $this->prepareRequest($request);

        $response = $this->client->request($request);

        return $response['domain_record'] ?? [];
    }

    /**
     * 删除域名记录
     */
    public function deleteDomainRecord(string $domainName, int $recordId): bool
    {
        $request = new DeleteDomainRecordRequest($domainName, $recordId);
        $this->prepareRequest($request);

        try {
            $this->client->request($request);
            return true;
        } catch  (\Throwable $e) {
            $this->logger->error('删除域名记录失败', [
                'domainName' => $domainName,
                'recordId' => $recordId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 同步所有域名到数据库
     */
    #[Transactional]
    public function syncDomains(): array
    {
        $domainsData = $this->listDomains(1, 100)['domains'] ?? [];

        if (empty($domainsData)) {
            $this->logger->info('没有找到任何域名');
            return [];
        }

        $domains = [];

        foreach ($domainsData as $domainData) {
            $name = $domainData['name'] ?? '';

            if (empty($name)) {
                continue;
            }

            // 查找现有域名或创建新域名
            $domain = $this->domainRepository->findOneBy(['name' => $name]) ?? new Domain();

            // 更新域名信息
            $domain->setName($name);

            if (isset($domainData['ttl'])) {
                $domain->setTtl((string)$domainData['ttl']);
            }

            if (isset($domainData['zone_file'])) {
                $domain->setZoneFile($domainData['zone_file']);
            }

            $this->entityManager->persist($domain);
            $domains[] = $domain;
        }

        $this->entityManager->flush();

        $this->logger->info('DigitalOcean域名已同步', ['count' => count($domains)]);

        return $domains;
    }

    /**
     * 同步指定域名的所有记录到数据库
     */
    #[Transactional]
    public function syncDomainRecords(string $domainName): array
    {
        $recordsData = $this->listDomainRecords($domainName, 1, 100)['domain_records'] ?? [];

        if (empty($recordsData)) {
            $this->logger->info('没有找到任何域名记录', ['domainName' => $domainName]);
            return [];
        }

        $records = [];

        foreach ($recordsData as $recordData) {
            $recordId = $recordData['id'] ?? 0;
            
            if (empty($recordId)) {
                continue;
            }

            // 查找现有记录或创建新记录
            $record = $this->domainRecordRepository->findOneBy([
                'domainName' => $domainName,
                'recordId' => $recordId,
            ]) ?? new DomainRecord();

            // 更新记录信息
            $record->setDomainName($domainName);
            $record->setRecordId($recordId);
            
            if (isset($recordData['type'])) {
                $record->setType($recordData['type']);
            }
            
            if (isset($recordData['name'])) {
                $record->setName($recordData['name']);
            }
            
            if (isset($recordData['data'])) {
                $record->setData($recordData['data']);
            }
            
            if (isset($recordData['priority'])) {
                $record->setPriority($recordData['priority']);
            }
            
            if (isset($recordData['port'])) {
                $record->setPort($recordData['port']);
            }
            
            if (isset($recordData['ttl'])) {
                $record->setTtl($recordData['ttl']);
            }
            
            if (isset($recordData['weight'])) {
                $record->setWeight($recordData['weight']);
            }
            
            if (isset($recordData['flags'])) {
                $record->setFlags($recordData['flags']);
            }
            
            if (isset($recordData['tag'])) {
                $record->setTag($recordData['tag']);
            }

            $this->entityManager->persist($record);
            $records[] = $record;
        }

        $this->entityManager->flush();

        $this->logger->info('DigitalOcean域名记录已同步', [
            'domainName' => $domainName,
            'count' => count($records),
        ]);

        return $records;
    }
}
