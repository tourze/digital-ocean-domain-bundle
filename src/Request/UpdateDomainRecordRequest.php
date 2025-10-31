<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 更新域名记录请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_update_record
 */
class UpdateDomainRecordRequest extends DigitalOceanRequest
{
    private ?int $priority = null;

    private ?int $port = null;

    private ?int $ttl = null;

    private ?int $weight = null;

    private ?string $flags = null;

    private ?string $tag = null;

    public function __construct(
        private readonly string $domainName,
        private readonly int $recordId,
        private readonly string $type,
        private readonly string $name,
        private readonly string $data,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName . '/records/' . $this->recordId;
    }

    public function getRequestMethod(): ?string
    {
        return 'PUT';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        $data = [
            'type' => $this->type,
            'name' => $this->name,
            'data' => $this->data,
        ];

        if (null !== $this->priority) {
            $data['priority'] = $this->priority;
        }

        if (null !== $this->port) {
            $data['port'] = $this->port;
        }

        if (null !== $this->ttl) {
            $data['ttl'] = $this->ttl;
        }

        if (null !== $this->weight) {
            $data['weight'] = $this->weight;
        }

        if (null !== $this->flags) {
            $data['flags'] = $this->flags;
        }

        if (null !== $this->tag) {
            $data['tag'] = $this->tag;
        }

        return [
            'json' => $data,
        ];
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    public function setTtl(?int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function setWeight(?int $weight): void
    {
        $this->weight = $weight;
    }

    public function setFlags(?string $flags): void
    {
        $this->flags = $flags;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }
}
