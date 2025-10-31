<?php

namespace DigitalOceanDomainBundle\Tests\Helper;

use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Service\DomainServiceInterface;

class TestDomainService implements DomainServiceInterface
{
    /** @var list<Domain> */
    private array $syncDomainsResponse = [];

    private ?\Throwable $syncDomainsException = null;

    /** @var array<string, mixed> */
    private array $getDomainRecordResponse = [];

    private ?\Throwable $getDomainRecordException = null;

    /** @var array<string, mixed> */
    private array $updateDomainRecordResponse = [];

    private ?\Throwable $updateDomainRecordException = null;

    /** @var array<array<string, mixed>> */
    private array $getDomainRecordCalls = [];

    /** @var array<array<string, mixed>> */
    private array $updateDomainRecordCalls = [];

    /** @var array<array<string, mixed>> */
    private array $syncDomainRecordsCalls = [];

    /**
     * @param list<Domain> $response
     */
    public function setSyncDomainsResponse(array $response): void
    {
        $this->syncDomainsResponse = $response;
    }

    public function setSyncDomainsException(?\Throwable $exception): void
    {
        $this->syncDomainsException = $exception;
    }

    /**
     * @param array<string, mixed> $response
     */
    public function setGetDomainRecordResponse(array $response): void
    {
        $this->getDomainRecordResponse = $response;
    }

    public function setGetDomainRecordException(?\Throwable $exception): void
    {
        $this->getDomainRecordException = $exception;
    }

    /**
     * @param array<string, mixed> $response
     */
    public function setUpdateDomainRecordResponse(array $response): void
    {
        $this->updateDomainRecordResponse = $response;
    }

    public function setUpdateDomainRecordException(?\Throwable $exception): void
    {
        $this->updateDomainRecordException = $exception;
    }

    public function resetCalls(): void
    {
        $this->getDomainRecordCalls = [];
        $this->updateDomainRecordCalls = [];
        $this->syncDomainRecordsCalls = [];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getGetDomainRecordCalls(): array
    {
        return $this->getDomainRecordCalls;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getUpdateDomainRecordCalls(): array
    {
        return $this->updateDomainRecordCalls;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getSyncDomainRecordsCalls(): array
    {
        return $this->syncDomainRecordsCalls;
    }

    /** @return list<Domain> */
    public function syncDomains(): array
    {
        if (null !== $this->syncDomainsException) {
            throw $this->syncDomainsException;
        }

        return $this->syncDomainsResponse;
    }

    /** @return array<string, mixed> */
    public function listDomains(int $page = 1, int $perPage = 20): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getDomain(string $domainName): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function createDomain(string $domainName, ?string $ipAddress = null): array
    {
        return [];
    }

    public function deleteDomain(string $domainName): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function listDomainRecords(string $domainName, int $page = 1, int $perPage = 20): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getDomainRecord(string $domainName, int $recordId): array
    {
        $this->getDomainRecordCalls[] = ['domainName' => $domainName, 'recordId' => $recordId];

        if (null !== $this->getDomainRecordException) {
            throw $this->getDomainRecordException;
        }

        return $this->getDomainRecordResponse;
    }

    /** @return array<string, mixed> */
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
        return [];
    }

    /** @return array<string, mixed> */
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

        if (null !== $this->updateDomainRecordException) {
            throw $this->updateDomainRecordException;
        }

        return $this->updateDomainRecordResponse;
    }

    public function deleteDomainRecord(string $domainName, int $recordId): bool
    {
        return true;
    }

    /** @return list<DomainRecord> */
    public function syncDomainRecords(string $domainName): array
    {
        $this->syncDomainRecordsCalls[] = ['domainName' => $domainName];

        return [];
    }
}
