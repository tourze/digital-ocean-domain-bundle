<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Service;

/**
 * 响应验证器
 */
class ResponseValidator
{
    /**
     * @param array<string, mixed> $response
     * @return array{domains: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>}
     */
    public function validateListDomainsResponse(array $response): array
    {
        $domains = $response['domains'] ?? [];
        $meta = $response['meta'] ?? [];
        $links = $response['links'] ?? [];

        $this->validateResponseArrays($domains, $meta, $links);

        return [
            'domains' => $this->validateArrayWithStringKeys(is_array($domains) ? $domains : []),
            'meta' => $this->convertToStringKeyedArray(is_array($meta) ? $meta : []),
            'links' => $this->convertToStringKeyedArray(is_array($links) ? $links : []),
        ];
    }

    /**
     * @param array<string, mixed> $response
     * @return array{domain_records: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>}
     */
    public function validateListDomainRecordsResponse(array $response): array
    {
        $domainRecords = $response['domain_records'] ?? [];
        $meta = $response['meta'] ?? [];
        $links = $response['links'] ?? [];

        $this->validateResponseArrays($domainRecords, $meta, $links);

        return [
            'domain_records' => $this->validateArrayWithStringKeys(is_array($domainRecords) ? $domainRecords : []),
            'meta' => $this->convertToStringKeyedArray(is_array($meta) ? $meta : []),
            'links' => $this->convertToStringKeyedArray(is_array($links) ? $links : []),
        ];
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public function validateDomainResponse(array $response): array
    {
        $domain = $response['domain'] ?? [];
        if (!is_array($domain)) {
            throw new \RuntimeException('Invalid domain data in response');
        }

        // Ensure proper typing for domain array
        $validatedDomain = [];
        foreach ($domain as $key => $value) {
            $validatedDomain[(string) $key] = $value;
        }

        return $validatedDomain;
    }

    /**
     * @param mixed $arrayData1
     * @param mixed $arrayData2
     * @param mixed $arrayData3
     */
    private function validateResponseArrays(mixed $arrayData1, mixed $arrayData2, mixed $arrayData3): void
    {
        if (!is_array($arrayData1)) {
            throw new \RuntimeException('Invalid first array data in response');
        }
        if (!is_array($arrayData2)) {
            throw new \RuntimeException('Invalid meta data in response');
        }
        if (!is_array($arrayData3)) {
            throw new \RuntimeException('Invalid links data in response');
        }
    }

    /**
     * @param array<mixed, mixed> $sourceArray
     * @return list<array<string, mixed>>
     */
    private function validateArrayWithStringKeys(array $sourceArray): array
    {
        $validatedArray = [];
        foreach ($sourceArray as $item) {
            if (is_array($item)) {
                $validatedArray[] = $this->convertToStringKeyedArray($item);
            }
        }

        return $validatedArray;
    }

    /**
     * @param array<mixed, mixed> $sourceArray
     * @return array<string, mixed>
     */
    private function convertToStringKeyedArray(array $sourceArray): array
    {
        $validatedArray = [];
        foreach ($sourceArray as $key => $value) {
            $validatedArray[(string) $key] = $value;
        }

        return $validatedArray;
    }
}
