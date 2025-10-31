<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 获取域名记录列表请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_list_records
 */
class ListDomainRecordsRequest extends DigitalOceanRequest
{
    private int $page = 1;

    private int $perPage = 20;

    public function __construct(
        private readonly string $domainName,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName . '/records';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        return [
            'query' => [
                'page' => $this->page,
                'per_page' => $this->perPage,
            ],
        ];
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
    }
}
