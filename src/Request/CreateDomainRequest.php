<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 创建域名请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_create
 */
class CreateDomainRequest extends DigitalOceanRequest
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $ipAddress = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/domains';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        $data = [
            'name' => $this->name,
        ];

        if (null !== $this->ipAddress) {
            $data['ip_address'] = $this->ipAddress;
        }

        return [
            'json' => $data,
        ];
    }
}
