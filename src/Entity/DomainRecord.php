<?php

namespace DigitalOceanDomainBundle\Entity;

use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * DigitalOcean域名记录实体
 *
 * @implements PlainArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: DomainRecordRepository::class)]
#[ORM\Table(name: 'ims_digital_ocean_domain_record', options: ['comment' => 'DigitalOcean域名记录'])]
class DomainRecord implements PlainArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '域名'])]
    #[Assert\NotBlank(message: '域名不能为空')]
    #[Assert\Length(max: 255, maxMessage: '域名长度不能超过255个字符')]
    private string $domainName;

    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '记录ID'])]
    #[Assert\NotBlank(message: '记录ID不能为空')]
    #[Assert\PositiveOrZero(message: '记录ID必须为非负整数')]
    private int $recordId;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '记录类型'])]
    #[Assert\NotBlank(message: '记录类型不能为空')]
    #[Assert\Length(max: 100, maxMessage: '记录类型长度不能超过100个字符')]
    private string $type;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '记录名称'])]
    #[Assert\NotBlank(message: '记录名称不能为空')]
    #[Assert\Length(max: 255, maxMessage: '记录名称长度不能超过255个字符')]
    private string $name;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '记录值'])]
    #[Assert\NotBlank(message: '记录值不能为空')]
    #[Assert\Length(max: 65535, maxMessage: '记录值长度不能超过65535个字符')]
    private string $data;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '优先级'])]
    #[Assert\PositiveOrZero(message: '优先级必须为非负整数')]
    private ?int $priority = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '端口'])]
    #[Assert\PositiveOrZero(message: '端口必须为非负整数')]
    #[Assert\LessThanOrEqual(value: 65535, message: '端口号不能超过65535')]
    private ?int $port = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => 'TTL'])]
    #[Assert\PositiveOrZero(message: 'TTL必须为非负整数')]
    private ?int $ttl = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '权重'])]
    #[Assert\PositiveOrZero(message: '权重必须为非负整数')]
    private ?int $weight = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '标志位'])]
    #[Assert\Length(max: 10, maxMessage: '标志位长度不能超过10个字符')]
    private ?string $flags = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '标签'])]
    #[Assert\Length(max: 10, maxMessage: '标签长度不能超过10个字符')]
    private ?string $tag = null;

    #[ORM\ManyToOne(targetEntity: DigitalOceanConfig::class)]
    #[ORM\JoinColumn(name: 'config_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?DigitalOceanConfig $config = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    public function setDomainName(string $domainName): void
    {
        $this->domainName = $domainName;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function setRecordId(int $recordId): void
    {
        $this->recordId = $recordId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function setTtl(?int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): void
    {
        $this->weight = $weight;
    }

    public function getFlags(): ?string
    {
        return $this->flags;
    }

    public function setFlags(?string $flags): void
    {
        $this->flags = $flags;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getConfig(): ?DigitalOceanConfig
    {
        return $this->config;
    }

    public function setConfig(?DigitalOceanConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlainArray(): array
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

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return $this->toPlainArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrievePlainArray(): array
    {
        return $this->toPlainArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return $this->toAdminArray();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
