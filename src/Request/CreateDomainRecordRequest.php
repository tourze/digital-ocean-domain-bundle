<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 创建域名记录请求
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_create_record
 */
class CreateDomainRecordRequest extends DigitalOceanRequest
{
    private string $domainName;
    private string $type;
    private string $name;
    private string $data;
    private ?int $priority = null;
    private ?int $port = null;
    private ?int $ttl = null;
    private ?int $weight = null;
    private ?string $flags = null;
    private ?string $tag = null;

    public function __construct(string $domainName, string $type, string $name, string $data)
    {
        $this->domainName = $domainName;
        $this->type = $type;
        $this->name = $name;
        $this->data = $data;
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName . '/records';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    public function getRequestOptions(): ?array
    {
        $data = [
            'type' => $this->type,
            'name' => $this->name,
            'data' => $this->data,
        ];

        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }

        if ($this->port !== null) {
            $data['port'] = $this->port;
        }

        if ($this->ttl !== null) {
            $data['ttl'] = $this->ttl;
        }

        if ($this->weight !== null) {
            $data['weight'] = $this->weight;
        }

        if ($this->flags !== null) {
            $data['flags'] = $this->flags;
        }

        if ($this->tag !== null) {
            $data['tag'] = $this->tag;
        }

        return [
            'json' => $data,
        ];
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function setPort(?int $port): self
    {
        $this->port = $port;
        return $this;
    }

    public function setTtl(?int $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function setWeight(?int $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function setFlags(?string $flags): self
    {
        $this->flags = $flags;
        return $this;
    }

    public function setTag(?string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }
}
