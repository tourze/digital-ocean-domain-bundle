<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 获取单个域名请求
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#tag/Domains/operation/domains_get
 */
class GetDomainRequest extends DigitalOceanRequest
{
    private string $domainName;

    public function __construct(string $domainName)
    {
        $this->domainName = $domainName;
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName;
    }
}
