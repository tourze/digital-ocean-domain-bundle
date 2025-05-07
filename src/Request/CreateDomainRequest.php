<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 创建域名请求
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_create
 */
class CreateDomainRequest extends DigitalOceanRequest
{
    private string $name;
    private ?string $ipAddress = null;

    public function __construct(string $name, ?string $ipAddress = null)
    {
        $this->name = $name;
        $this->ipAddress = $ipAddress;
    }

    public function getRequestPath(): string
    {
        return '/domains';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    public function getRequestOptions(): ?array
    {
        $data = [
            'name' => $this->name,
        ];

        if ($this->ipAddress !== null) {
            $data['ip_address'] = $this->ipAddress;
        }

        return [
            'json' => $data,
        ];
    }
}
