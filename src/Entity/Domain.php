<?php

namespace DigitalOceanDomainBundle\Entity;

use DigitalOceanDomainBundle\Repository\DomainRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[ORM\Table(name: 'ims_digital_ocean_domain', options: ['comment' => 'DigitalOcean域名'])]
class Domain implements PlainArrayInterface, AdminArrayInterface, \Stringable
{
    #[ListColumn(order: -1)]
    #[ExportColumn]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '域名'])]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'TTL'])]
    private ?string $ttl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Zone文件'])]
    private ?string $zoneFile = null;

    use TimestampableAware;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getTtl(): ?string
    {
        return $this->ttl;
    }

    public function setTtl(?string $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function getZoneFile(): ?string
    {
        return $this->zoneFile;
    }

    public function setZoneFile(?string $zoneFile): self
    {
        $this->zoneFile = $zoneFile;
        return $this;
    }

    public function toPlainArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'ttl' => $this->getTtl(),
            'zoneFile' => $this->getZoneFile(),
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
        return $this->getName();
    }
}
