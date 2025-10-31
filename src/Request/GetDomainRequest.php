<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 获取单个域名请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#tag/Domains/operation/domains_get
 */
class GetDomainRequest extends DigitalOceanRequest
{
    public function __construct(
        private readonly string $domainName,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName;
    }
}
