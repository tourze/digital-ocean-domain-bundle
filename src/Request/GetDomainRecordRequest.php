<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 获取单个域名记录请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_get_record
 */
class GetDomainRecordRequest extends DigitalOceanRequest
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
}
