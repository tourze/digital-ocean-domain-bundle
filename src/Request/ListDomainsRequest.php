<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 获取域名列表请求
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#tag/Domains/operation/domains_list
 */
class ListDomainsRequest extends DigitalOceanRequest
{
    private int $page = 1;
    private int $perPage = 20;

    public function getRequestPath(): string
    {
        return '/domains';
    }

    public function getRequestOptions(): ?array
    {
        return [
            'query' => [
                'page' => $this->page,
                'per_page' => $this->perPage,
            ],
        ];
    }

    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;
        return $this;
    }
}
