<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 删除域名记录请求
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_delete_record
 */
class DeleteDomainRecordRequest extends DigitalOceanRequest
{
    private string $domainName;
    private int $recordId;

    public function __construct(string $domainName, int $recordId)
    {
        $this->domainName = $domainName;
        $this->recordId = $recordId;
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
