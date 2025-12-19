<?php

namespace DigitalOceanDomainBundle\Service;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Exception\ConfigurationException;
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
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\Symfony\AopDoctrineBundle\Attribute\Transactional;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'digital_ocean_domain')]
readonly class DomainService implements DomainServiceInterface
{
    public function __construct(
        private DigitalOceanClient $client,
        private DigitalOceanConfigService $configService,
        private EntityManagerInterface $entityManager,
        private DomainRepository $domainRepository,
        private DomainRecordRepository $domainRecordRepository,
        private LoggerInterface $logger,
        private ResponseValidator $responseValidator = new ResponseValidator(),
        private DomainRecordMapper $domainRecordMapper = new DomainRecordMapper(),
    ) {
    }

    /**
     * 为请求设置API Key
     * @param object $request The request object that must have a setApiKey method
     */
    private function prepareRequest(object $request): void
    {
        try {
            $config = $this->configService->getConfig();
            if (null === $config) {
                throw new ConfigurationException('未配置 DigitalOcean API Key');
            }

            if (!method_exists($request, 'setApiKey')) {
                throw new \InvalidArgumentException('Request must have setApiKey method');
            }

            $apiKey = $config->getApiKey();
            if (null === $apiKey || '' === $apiKey) {
                throw new ConfigurationException('DigitalOcean API Key 不能为空');
            }

            $request->setApiKey($apiKey);
        } catch (\Exception $e) {
            $this->logger->error('准备API请求时发生错误', [
                'error' => $e->getMessage(),
                'request_class' => get_class($request),
            ]);
            throw $e;
        }
    }

    /**
     * 获取域名列表
     * @return array{domains: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>}
     */
    public function listDomains(int $page = 1, int $perPage = 20): array
    {
        $this->validatePaginationParams($page, $perPage);

        try {
            $request = new ListDomainsRequest();
            $request->setPage($page);
            $request->setPerPage($perPage);

            $this->prepareRequest($request);

            $response = $this->executeApiRequest($request);

            return $this->responseValidator->validateListDomainsResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('获取域名列表时发生错误', [
                'error' => $e->getMessage(),
                'page' => $page,
                'per_page' => $perPage,
            ]);
            throw $e;
        }
    }

    /**
     * 验证分页参数
     */
    private function validatePaginationParams(int $page, int $perPage): void
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('页码必须大于0');
        }

        if ($perPage < 1 || $perPage > 200) {
            throw new \InvalidArgumentException('每页数量必须在1-200之间');
        }
    }

    /**
     * 获取单个域名信息
     * @return array<string, mixed>
     */
    public function getDomain(string $domainName): array
    {
        $this->validateDomainName($domainName);

        try {
            $request = new GetDomainRequest($domainName);
            $this->prepareRequest($request);

            $response = $this->executeApiRequest($request);

            return $this->responseValidator->validateDomainResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('获取域名信息时发生错误', [
                'error' => $e->getMessage(),
                'domain_name' => $domainName,
            ]);
            throw $e;
        }
    }

    /**
     * 验证域名参数
     */
    private function validateDomainName(string $domainName): void
    {
        if ('' === trim($domainName)) {
            throw new \InvalidArgumentException('域名不能为空');
        }

        if (strlen($domainName) > 253) {
            throw new \InvalidArgumentException('域名长度不能超过253个字符');
        }
    }

    /**
     * 创建新域名
     * @return array<string, mixed>
     */
    public function createDomain(string $domainName, ?string $ipAddress = null): array
    {
        $request = new CreateDomainRequest($domainName, $ipAddress);
        $this->prepareRequest($request);

        $response = $this->executeApiRequest($request);

        return $this->responseValidator->validateDomainResponse($response);
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
        } catch (\Throwable $e) {
            $this->logger->error('删除域名失败', [
                'domainName' => $domainName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 获取域名记录列表
     * @return array{domain_records: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>}
     */
    public function listDomainRecords(string $domainName, int $page = 1, int $perPage = 20): array
    {
        $request = new ListDomainRecordsRequest($domainName);
        $request->setPage($page);
        $request->setPerPage($perPage);

        $this->prepareRequest($request);

        $response = $this->client->request($request);

        if (!is_array($response)) {
            throw new \RuntimeException('Invalid response from API');
        }

        // Type cast for strict typing requirement
        $typedResponse = [];
        foreach ($response as $key => $value) {
            $typedResponse[(string) $key] = $value;
        }

        return $this->responseValidator->validateListDomainRecordsResponse($typedResponse);
    }

    /**
     * 获取单个域名记录
     * @return array<string, mixed>
     */
    public function getDomainRecord(string $domainName, int $recordId): array
    {
        $request = new GetDomainRecordRequest($domainName, $recordId);
        $this->prepareRequest($request);

        $response = $this->client->request($request);

        if (!is_array($response)) {
            throw new \RuntimeException('Invalid response from API');
        }

        $domainRecord = $response['domain_record'] ?? [];
        if (!is_array($domainRecord)) {
            throw new \RuntimeException('Invalid domain_record data in response');
        }

        // Ensure proper typing for domain record array
        $validatedDomainRecord = [];
        foreach ($domainRecord as $key => $value) {
            $validatedDomainRecord[(string) $key] = $value;
        }

        return $validatedDomainRecord;
    }

    /**
     * 创建域名记录
     * @return array<string, mixed>
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
        ?string $tag = null,
    ): array {
        $request = new CreateDomainRecordRequest($domainName, $type, $name, $data);
        $this->setOptionalRequestParameters($request, $priority, $port, $ttl, $weight, $flags, $tag);
        $this->prepareRequest($request);

        return $this->executeDomainRecordRequest($request);
    }

    /**
     * 更新域名记录
     * @return array<string, mixed>
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
        ?string $tag = null,
    ): array {
        $request = new UpdateDomainRecordRequest($domainName, $recordId, $type, $name, $data);
        $this->setOptionalRequestParameters($request, $priority, $port, $ttl, $weight, $flags, $tag);
        $this->prepareRequest($request);

        return $this->executeDomainRecordRequest($request);
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
        } catch (\Throwable $e) {
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
     * @return list<Domain>
     */
    #[Transactional]
    public function syncDomains(): array
    {
        $domainsData = $this->listDomains(1, 100)['domains'];

        if (0 === count($domainsData)) {
            $this->logger->info('没有找到任何域名');

            return [];
        }

        $domains = $this->processDomainsData($domainsData);
        $this->entityManager->flush();

        $this->logger->info('DigitalOcean域名已同步', ['count' => count($domains)]);

        return $domains;
    }

    /**
     * @param list<array<string, mixed>> $domainsData
     * @return list<Domain>
     */
    private function processDomainsData(array $domainsData): array
    {
        $domains = [];

        foreach ($domainsData as $domainData) {
            $domain = $this->processSingleDomainData($domainData);
            if (null !== $domain) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    /**
     * @param mixed $domainData
     */
    private function processSingleDomainData(mixed $domainData): ?Domain
    {
        if (!is_array($domainData)) {
            return null;
        }

        $name = $domainData['name'] ?? '';
        if (!is_string($name) || '' === $name) {
            return null;
        }

        $domain = $this->findOrCreateDomain($name);
        /** @var array<string, mixed> $domainData */
        $this->updateDomainFromData($domain, $domainData);
        $this->entityManager->persist($domain);

        return $domain;
    }

    private function findOrCreateDomain(string $name): Domain
    {
        return $this->domainRepository->findOneBy(['name' => $name]) ?? new Domain();
    }

    /**
     * @param array<string, mixed> $domainData
     */
    private function updateDomainFromData(Domain $domain, array $domainData): void
    {
        if (!isset($domainData['name']) || !is_string($domainData['name'])) {
            throw new \InvalidArgumentException('Domain name must be a string');
        }
        $domain->setName($domainData['name']);

        if (isset($domainData['ttl'])) {
            $ttlValue = $domainData['ttl'];
            $domain->setTtl(is_scalar($ttlValue) ? (string) $ttlValue : '');
        }

        if (isset($domainData['zone_file']) && is_string($domainData['zone_file'])) {
            $domain->setZoneFile($domainData['zone_file']);
        }
    }

    /**
     * 同步指定域名的所有记录到数据库
     * @return list<DomainRecord>
     */
    #[Transactional]
    public function syncDomainRecords(string $domainName): array
    {
        $recordsData = $this->listDomainRecords($domainName, 1, 100)['domain_records'];

        if (0 === count($recordsData)) {
            $this->logger->info('没有找到任何域名记录', ['domainName' => $domainName]);

            return [];
        }

        $records = $this->processRecordsData($recordsData, $domainName);

        $this->entityManager->flush();

        $this->logger->info('DigitalOcean域名记录已同步', [
            'domainName' => $domainName,
            'count' => count($records),
        ]);

        return $records;
    }

    /**
     * @param list<array<string, mixed>> $recordsData
     * @return list<DomainRecord>
     */
    private function processRecordsData(array $recordsData, string $domainName): array
    {
        $records = [];

        foreach ($recordsData as $recordData) {
            $record = $this->processSingleRecordData($recordData, $domainName);
            if (null !== $record) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * @param mixed $recordData
     */
    private function processSingleRecordData(mixed $recordData, string $domainName): ?DomainRecord
    {
        if (!is_array($recordData)) {
            return null;
        }

        $recordId = $recordData['id'] ?? 0;
        if (!is_numeric($recordId) || 0 === (int) $recordId) {
            return null;
        }

        $record = $this->findOrCreateRecord($domainName, (int) $recordId);
        /** @var array<string, mixed> $recordData */
        $this->updateRecordFromData($record, $recordData, $domainName);
        $this->entityManager->persist($record);

        return $record;
    }

    private function findOrCreateRecord(string $domainName, int $recordId): DomainRecord
    {
        return $this->domainRecordRepository->findOneBy([
            'domainName' => $domainName,
            'recordId' => $recordId,
        ]) ?? new DomainRecord();
    }

    /**
     * @param array<string, mixed> $recordData
     */
    private function updateRecordFromData(DomainRecord $record, array $recordData, string $domainName): void
    {
        $this->domainRecordMapper->updateRecordFromData($record, $recordData, $domainName);
    }

    private function setOptionalRequestParameters(
        CreateDomainRecordRequest|UpdateDomainRecordRequest $request,
        ?int $priority,
        ?int $port,
        ?int $ttl,
        ?int $weight,
        ?string $flags,
        ?string $tag,
    ): void {
        $this->setRequestIntParams($request, $priority, $port, $ttl, $weight);
        $this->setRequestStringParams($request, $flags, $tag);
    }

    private function setRequestIntParams(
        CreateDomainRecordRequest|UpdateDomainRecordRequest $request,
        ?int $priority,
        ?int $port,
        ?int $ttl,
        ?int $weight,
    ): void {
        if (null !== $priority) {
            $request->setPriority($priority);
        }
        if (null !== $port) {
            $request->setPort($port);
        }
        if (null !== $ttl) {
            $request->setTtl($ttl);
        }
        if (null !== $weight) {
            $request->setWeight($weight);
        }
    }

    private function setRequestStringParams(
        CreateDomainRecordRequest|UpdateDomainRecordRequest $request,
        ?string $flags,
        ?string $tag,
    ): void {
        if (null !== $flags) {
            $request->setFlags($flags);
        }
        if (null !== $tag) {
            $request->setTag($tag);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function executeDomainRecordRequest(CreateDomainRecordRequest|UpdateDomainRecordRequest $request): array
    {
        $response = $this->client->request($request);

        if (!is_array($response)) {
            throw new \RuntimeException('Invalid response from API');
        }

        $domainRecord = $response['domain_record'] ?? [];
        if (!is_array($domainRecord)) {
            throw new \RuntimeException('Invalid domain_record data in response');
        }

        // Ensure all values are properly typed
        $validatedRecord = [];
        foreach ($domainRecord as $key => $value) {
            $validatedRecord[(string) $key] = $value;
        }

        return $validatedRecord;
    }

    /**
     * @return array<string, mixed>
     */
    private function executeApiRequest(ListDomainsRequest|GetDomainRequest|CreateDomainRequest|DeleteDomainRequest|ListDomainRecordsRequest|GetDomainRecordRequest|CreateDomainRecordRequest|UpdateDomainRecordRequest|DeleteDomainRecordRequest $request): array
    {
        $response = $this->client->request($request);

        if (!is_array($response)) {
            throw new \RuntimeException('Invalid response from API');
        }

        // Ensure all keys are strings for array<string, mixed> typing
        $validatedResponse = [];
        foreach ($response as $key => $value) {
            $validatedResponse[(string) $key] = $value;
        }

        return $validatedResponse;
    }
}
