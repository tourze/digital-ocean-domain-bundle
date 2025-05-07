<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 获取单个域名记录请求
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_get_record
 */
class GetDomainRecordRequest extends DigitalOceanRequest
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
}
