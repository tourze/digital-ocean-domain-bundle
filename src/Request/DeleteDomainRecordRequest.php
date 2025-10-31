<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 删除域名记录请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_delete_record
 */
class DeleteDomainRecordRequest extends DigitalOceanRequest
{
    public function __construct(
        private readonly string $domainName,
        private readonly int $recordId,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName . '/records/' . $this->recordId;
    }

    public function getRequestMethod(): ?string
    {
        return 'DELETE';
    }
}
