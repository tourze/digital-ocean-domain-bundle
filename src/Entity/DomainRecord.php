<?php

namespace DigitalOceanDomainBundle\Entity;

use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;

/**
 * DigitalOcean域名记录实体
 */
#[ORM\Entity(repositoryClass: DomainRecordRepository::class)]
#[ORM\Table(name: 'ims_digital_ocean_domain_record', options: ['comment' => 'DigitalOcean域名记录'])]
class DomainRecord implements PlainArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    #[ListColumn(order: -1)]
    #[ExportColumn]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    #[ListColumn]
    #[ExportColumn]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '域名'])]
    private string $domainName;

    #[ListColumn]
    #[ExportColumn]
    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '记录ID'])]
    private int $recordId;

    #[ListColumn]
    #[ExportColumn]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '记录类型'])]
    private string $type;

    #[ListColumn]
    #[ExportColumn]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '记录名称'])]
    private string $name;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\Column(type: Types::TEXT, options: ['comment' => '记录值'])]
    private string $data;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '优先级'])]
    private ?int $priority = null;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '端口'])]
    private ?int $port = null;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => 'TTL'])]
    private ?int $ttl = null;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '权重'])]
    private ?int $weight = null;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '标志位'])]
    private ?string $flags = null;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '标签'])]
    private ?string $tag = null;

    #[ListColumn]
    #[ExportColumn]
    #[ORM\ManyToOne(targetEntity: DigitalOceanConfig::class)]
    #[ORM\JoinColumn(name: 'config_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?DigitalOceanConfig $config = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    public function setDomainName(string $domainName): self
    {
        $this->domainName = $domainName;
        return $this;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function setRecordId(int $recordId): self
    {
        $this->recordId = $recordId;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): self
    {
        $this->port = $port;
        return $this;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function setTtl(?int $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getFlags(): ?string
    {
        return $this->flags;
    }

    public function setFlags(?string $flags): self
    {
        $this->flags = $flags;
        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getConfig(): ?DigitalOceanConfig
    {
        return $this->config;
    }

    public function setConfig(?DigitalOceanConfig $config): self
    {
        $this->config = $config;
        return $this;
    }public function toPlainArray(): array
    {
        return [
            'id' => $this->getId(),
            'domainName' => $this->getDomainName(),
            'recordId' => $this->getRecordId(),
            'type' => $this->getType(),
            'name' => $this->getName(),
            'data' => $this->getData(),
            'priority' => $this->getPriority(),
            'port' => $this->getPort(),
            'ttl' => $this->getTtl(),
            'weight' => $this->getWeight(),
            'flags' => $this->getFlags(),
            'tag' => $this->getTag(),
            'config' => $this->getConfig()?->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function toAdminArray(): array
    {
        return $this->toPlainArray();
    }

    public function retrievePlainArray(): array
    {
        return $this->toPlainArray();
    }

    public function retrieveAdminArray(): array
    {
        return $this->toAdminArray();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
