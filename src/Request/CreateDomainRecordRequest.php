<?php

namespace DigitalOceanDomainBundle\Request;

use DigitalOceanAccountBundle\Request\DigitalOceanRequest;

/**
 * 创建域名记录请求
 *
 * @see https://docs.digitalocean.com/reference/api/digitalocean/#operation/domains_create_record
 */
class CreateDomainRecordRequest extends DigitalOceanRequest
{
    private ?int $priority = null;

    private ?int $port = null;

    private ?int $ttl = null;

    private ?int $weight = null;

    private ?string $flags = null;

    private ?string $tag = null;

    public function __construct(
        private readonly string $domainName,
        private readonly string $type,
        private readonly string $name,
        private readonly string $data,
    ) {
        // 参数验证
        if (empty(trim($this->domainName))) {
            throw new \InvalidArgumentException('Domain name cannot be empty');
        }

        if (empty(trim($this->type))) {
            throw new \InvalidArgumentException('Record type cannot be empty');
        }

        if (empty(trim($this->name))) {
            throw new \InvalidArgumentException('Record name cannot be empty');
        }

        if (empty(trim($this->data))) {
            throw new \InvalidArgumentException('Record data cannot be empty');
        }

        // 验证记录类型
        $validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'NS', 'CAA'];
        if (!in_array(strtoupper($this->type), $validTypes, true)) {
            throw new \InvalidArgumentException('Invalid record type: ' . $this->type);
        }

        // 验证域名长度
        if (strlen($this->domainName) > 253) {
            throw new \InvalidArgumentException('Domain name cannot exceed 253 characters');
        }

        // 验证记录名称长度
        if (strlen($this->name) > 255) {
            throw new \InvalidArgumentException('Record name cannot exceed 255 characters');
        }

        // 验证数据长度
        if (strlen($this->data) > 65535) {
            throw new \InvalidArgumentException('Record data cannot exceed 65535 characters');
        }
    }

    public function getRequestPath(): string
    {
        return '/domains/' . $this->domainName . '/records';
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
        if ($priority !== null && ($priority < 0 || $priority > 65535)) {
            throw new \InvalidArgumentException('Priority must be between 0 and 65535');
        }
        $this->priority = $priority;
    }

    public function setPort(?int $port): void
    {
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException('Port must be between 1 and 65535');
        }
        $this->port = $port;
    }

    public function setTtl(?int $ttl): void
    {
        if ($ttl !== null && ($ttl < 60 || $ttl > 86400)) {
            throw new \InvalidArgumentException('TTL must be between 60 and 86400 seconds');
        }
        $this->ttl = $ttl;
    }

    public function setWeight(?int $weight): void
    {
        if ($weight !== null && ($weight < 0 || $weight > 65535)) {
            throw new \InvalidArgumentException('Weight must be between 0 and 65535');
        }
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
