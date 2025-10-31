<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 删除域名请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_delete
 */
class DeleteDomainRequest extends DigitalOceanRequest
{
    public function __construct(
        private readonly string $domainName,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName;
    }

    public function getRequestMethod(): ?string
    {
        return 'DELETE';
    }
}
