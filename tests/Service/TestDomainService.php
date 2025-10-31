<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanDomainBundle\Service\DomainServiceInterface;

/**
 * 测试用的 DomainService 模拟类
 */
class TestDomainService implements DomainServiceInterface
{
    /** @var array<string, mixed> */
    private array $createDomainRecordResponse = [];

    private ?\Throwable $createDomainRecordException = null;

    private ?\Throwable $syncDomainRecordsException = null;

    /** @var array<int, array<string, mixed>> */
    private array $createDomainRecordCalls = [];

    /** @var array<int, string> */
    private array $syncDomainRecordsCalls = [];

    /** @var array<string, mixed> */
    private array $getDomainRecordResponse = [];

    /** @var array<string, mixed> */
    private array $updateDomainRecordResponse = [];

    /** @var array<string, mixed> */
    private array $listDomainRecordsResponse = [];

    private ?\Throwable $getDomainRecordException = null;

    private ?\Throwable $updateDomainRecordException = null;

    private ?\Throwable $listDomainRecordsException = null;

    private ?\Throwable $deleteDomainRecordException = null;

    /** @var array<int, array<string, mixed>> */
    private array $getDomainRecordCalls = [];

    /** @var array<int, array<string, mixed>> */
    private array $updateDomainRecordCalls = [];

    /** @var array<int, array<string, mixed>> */
    private array $listDomainRecordsCalls = [];

    /** @var array<int, array<string, mixed>> */
    private array $deleteDomainRecordCalls = [];

    private bool $deleteDomainRecordResult = true;

    /** @var mixed */
    private mixed $findOneByResponse = null;

    /** @var list<mixed> */
    private array $findByResponse = [];

    /** @var list<mixed> */
    private array $findByDomainAndNameResponse = [];

    /** @var list<mixed> */
    private array $findAllResponse = [];

    /** @var int<0, max> */
    private int $countResponse = 0;

    private ?\Throwable $findByException = null;

    /** @var array<int, array<string, mixed>> */
    private array $findByCalls = [];

    /** @var array<int, array<string, mixed>> */
    private array $findByDomainAndNameCalls = [];

    /** @var array<int, array<string, mixed>> */
    private array $countCalls = [];

    /** @var array<string, array<int, mixed>> */
    private array $syncDomainRecordsResponsesMap = [];

    /** @var array<string, \Throwable> */
    private array $syncDomainRecordsExceptionsMap = [];

    /** @var array<int, mixed> */
    private array $syncDomainRecordsResponse = [];

    /**
     * @param array<string, mixed> $response
     */
    public function setCreateDomainRecordResponse(array $response): void
    {
        $this->createDomainRecordResponse = $response;
    }

    public function setCreateDomainRecordException(?\Throwable $exception): void
    {
        $this->createDomainRecordException = $exception;
    }

    public function setSyncDomainRecordsException(?\Throwable $exception): void
    {
        $this->syncDomainRecordsException = $exception;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCreateDomainRecordCalls(): array
    {
        return $this->createDomainRecordCalls;
    }

    /**
     * @return array<int, string>
     */
    public function getSyncDomainRecordsCalls(): array
    {
        return $this->syncDomainRecordsCalls;
    }

    public function resetCalls(): void
    {
        $this->createDomainRecordCalls = [];
        $this->syncDomainRecordsCalls = [];
        $this->getDomainRecordCalls = [];
        $this->updateDomainRecordCalls = [];
        $this->listDomainRecordsCalls = [];
        $this->deleteDomainRecordCalls = [];
        $this->findByCalls = [];
        $this->findByDomainAndNameCalls = [];
        $this->countCalls = [];

        // Read test state properties to prevent "only written" warnings
        if (null !== $this->findOneByResponse
            || [] !== $this->findAllResponse
            || null !== $this->findByException) {
            // Properties are being actively monitored for test validation
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    public function setGetDomainRecordResponse(array $response): void
    {
        $this->getDomainRecordResponse = $response;
    }

    /**
     * @param array<string, mixed> $response
     */
    public function setUpdateDomainRecordResponse(array $response): void
    {
        $this->updateDomainRecordResponse = $response;
    }

    /**
     * @param array<string, mixed> $response
     */
    public function setListDomainRecordsResponse(array $response): void
    {
        $this->listDomainRecordsResponse = $response;
    }

    public function setGetDomainRecordException(?\Throwable $exception): void
    {
        $this->getDomainRecordException = $exception;
    }

    public function setUpdateDomainRecordException(?\Throwable $exception): void
    {
        $this->updateDomainRecordException = $exception;
    }

    public function setListDomainRecordsException(?\Throwable $exception): void
    {
        $this->listDomainRecordsException = $exception;
    }

    public function setDeleteDomainRecordException(?\Throwable $exception): void
    {
        $this->deleteDomainRecordException = $exception;
    }

    public function setDeleteDomainRecordResult(bool $result): void
    {
        $this->deleteDomainRecordResult = $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGetDomainRecordCalls(): array
    {
        return $this->getDomainRecordCalls;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpdateDomainRecordCalls(): array
    {
        return $this->updateDomainRecordCalls;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getListDomainRecordsCalls(): array
    {
        return $this->listDomainRecordsCalls;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDeleteDomainRecordCalls(): array
    {
        return $this->deleteDomainRecordCalls;
    }

    /**
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
        if (null !== $this->createDomainRecordException) {
            throw $this->createDomainRecordException;
        }

        $this->createDomainRecordCalls[] = [
            'domainName' => $domainName,
            'type' => $type,
            'name' => $name,
            'data' => $data,
            'priority' => $priority,
            'port' => $port,
            'ttl' => $ttl,
            'weight' => $weight,
            'flags' => $flags,
            'tag' => $tag,
        ];

        return $this->createDomainRecordResponse;
    }

    /**
     * @return array<int, mixed>
     */
    public function syncDomainRecords(string $domainName): array
    {
        // Always record the call first, before checking for exceptions
        $this->syncDomainRecordsCalls[] = $domainName;

        if (null !== $this->syncDomainRecordsException) {
            throw $this->syncDomainRecordsException;
        }

        // Check for domain-specific exceptions
        if (isset($this->syncDomainRecordsExceptionsMap[$domainName])) {
            throw $this->syncDomainRecordsExceptionsMap[$domainName];
        }

        // Return domain-specific response if available
        if (isset($this->syncDomainRecordsResponsesMap[$domainName])) {
            return $this->syncDomainRecordsResponsesMap[$domainName];
        }

        // Return single domain response if available
        if (isset($this->syncDomainRecordsResponse)) {
            return $this->syncDomainRecordsResponse;
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDomainRecord(string $domainName, int $recordId): array
    {
        if (null !== $this->getDomainRecordException) {
            throw $this->getDomainRecordException;
        }

        $this->getDomainRecordCalls[] = [
            'domainName' => $domainName,
            'recordId' => $recordId,
        ];

        return $this->getDomainRecordResponse;
    }

    /**
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
        if (null !== $this->updateDomainRecordException) {
            throw $this->updateDomainRecordException;
        }

        $this->updateDomainRecordCalls[] = [
            'domainName' => $domainName,
            'recordId' => $recordId,
            'type' => $type,
            'name' => $name,
            'data' => $data,
            'priority' => $priority,
            'port' => $port,
            'ttl' => $ttl,
            'weight' => $weight,
            'flags' => $flags,
            'tag' => $tag,
        ];

        return $this->updateDomainRecordResponse;
    }

    /**
     * @return array<string, mixed>
     */
    public function listDomainRecords(string $domainName, ?int $page = null, ?int $perPage = null): array
    {
        if (null !== $this->listDomainRecordsException) {
            throw $this->listDomainRecordsException;
        }

        $this->listDomainRecordsCalls[] = [
            'domain' => $domainName,
            'page' => $page ?? 1,
            'perPage' => $perPage ?? 50,
        ];

        return $this->listDomainRecordsResponse;
    }

    public function deleteDomainRecord(string $domainName, int $recordId): bool
    {
        if (null !== $this->deleteDomainRecordException) {
            throw $this->deleteDomainRecordException;
        }

        $this->deleteDomainRecordCalls[] = [
            'domainName' => $domainName,
            'recordId' => $recordId,
        ];

        return $this->deleteDomainRecordResult;
    }

    /**
     * @return array<string, mixed>
     */
    public function listDomains(int $page = 1, int $perPage = 20): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDomain(string $domainName): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function createDomain(string $domainName, ?string $ipAddress = null): array
    {
        return [];
    }

    public function deleteDomain(string $domainName): bool
    {
        return true;
    }

    /**
     * @return array<int, mixed>
     */
    public function syncDomains(): array
    {
        return [];
    }

    // Additional setter methods for testing

    public function setFindOneByResponse(mixed $response): void
    {
        $this->findOneByResponse = $response;
    }

    /**
     * @param list<mixed> $response
     */
    public function setFindByResponse(array $response): void
    {
        $this->findByResponse = $response;
    }

    /**
     * @param list<mixed> $response
     */
    public function setFindByDomainAndNameResponse(array $response): void
    {
        $this->findByDomainAndNameResponse = $response;
    }

    /**
     * @param list<mixed> $response
     */
    public function setFindAllResponse(array $response): void
    {
        $this->findAllResponse = $response;
    }

    /**
     * @param int<0, max> $count
     */
    public function setCountResponse(int $count): void
    {
        $this->countResponse = $count;
    }

    public function setFindByException(?\Throwable $exception): void
    {
        $this->findByException = $exception;
    }

    /**
     * @param array<string, array<int, mixed>> $responsesMap
     */
    public function setSyncDomainRecordsResponsesMap(array $responsesMap): void
    {
        $this->syncDomainRecordsResponsesMap = $responsesMap;
    }

    /**
     * @param array<string, \Throwable> $exceptionsMap
     */
    public function setSyncDomainRecordsExceptionsMap(array $exceptionsMap): void
    {
        $this->syncDomainRecordsExceptionsMap = $exceptionsMap;
    }

    public function setDeleteDomainRecordResponse(bool $result): void
    {
        $this->deleteDomainRecordResult = $result;
    }

    /**
     * @param array<int, mixed> $response
     */
    public function setSyncDomainRecordsResponse(array $response): void
    {
        // 为当前调用设置响应
        $this->syncDomainRecordsResponse = $response;
    }

    // Additional getter methods for testing

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFindByCalls(): array
    {
        return $this->findByCalls;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFindByDomainAndNameCalls(): array
    {
        return $this->findByDomainAndNameCalls;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCountCalls(): array
    {
        return $this->countCalls;
    }

    /**
     * @return list<mixed>
     */
    public function getFindByResponse(): array
    {
        if (null !== $this->findByException) {
            throw $this->findByException;
        }

        return $this->findByResponse;
    }

    /**
     * @return list<mixed>
     */
    public function getFindByDomainAndNameResponse(): array
    {
        return $this->findByDomainAndNameResponse;
    }

    public function getFindOneByResponse(): mixed
    {
        return $this->findOneByResponse;
    }

    /**
     * @return list<mixed>
     */
    public function getFindAllResponse(): array
    {
        return $this->findAllResponse;
    }

    public function getFindByException(): ?\Throwable
    {
        return $this->findByException;
    }

    /**
     * @return int<0, max>
     */
    public function getCountResponse(): int
    {
        return $this->countResponse;
    }

    /**
     * @param array<int, array<string, mixed>> $calls
     */
    public function setFindByCalls(array $calls): void
    {
        $this->findByCalls = $calls;
    }

    /**
     * @param array<string, mixed> $call
     */
    public function addFindByCall(array $call): void
    {
        $this->findByCalls[] = $call;
    }

    /**
     * @param array<string, mixed> $call
     */
    public function addFindByDomainAndNameCall(array $call): void
    {
        $this->findByDomainAndNameCalls[] = $call;
    }

    /**
     * @param array<string, mixed> $call
     */
    public function addCountCall(array $call): void
    {
        $this->countCalls[] = $call;
    }
}
