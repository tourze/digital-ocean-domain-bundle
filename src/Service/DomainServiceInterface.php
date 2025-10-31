<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Service;

/**
 * DigitalOcean域名服务接口
 */
interface DomainServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function listDomains(int $page = 1, int $perPage = 20): array;

    /**
     * @return array<string, mixed>
     */
    public function getDomain(string $domainName): array;

    /**
     * @return array<string, mixed>
     */
    public function createDomain(string $domainName, ?string $ipAddress = null): array;

    public function deleteDomain(string $domainName): bool;

    /**
     * @return array<string, mixed>
     */
    public function listDomainRecords(string $domainName, int $page = 1, int $perPage = 20): array;

    /**
     * @return array<string, mixed>
     */
    public function getDomainRecord(string $domainName, int $recordId): array;

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
    ): array;

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
    ): array;

    public function deleteDomainRecord(string $domainName, int $recordId): bool;

    /**
     * @return array<int, mixed>
     */
    public function syncDomains(): array;

    /**
     * @return array<int, mixed>
     */
    public function syncDomainRecords(string $domainName): array;
}
