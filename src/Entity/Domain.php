<?php

namespace DigitalOceanDomainBundle\Entity;

use DigitalOceanDomainBundle\Repository\DomainRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * @implements PlainArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[ORM\Table(name: 'ims_digital_ocean_domain', options: ['comment' => 'DigitalOcean域名'])]
class Domain implements PlainArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '域名'])]
    #[IndexColumn]
    #[Assert\NotBlank(message: '域名不能为空')]
    #[Assert\Length(max: 255, maxMessage: '域名长度不能超过255个字符')]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'TTL'])]
    #[Assert\Length(max: 65535, maxMessage: 'TTL长度不能超过65535个字符')]
    private ?string $ttl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Zone文件'])]
    #[Assert\Length(max: 65535, maxMessage: 'Zone文件长度不能超过65535个字符')]
    private ?string $zoneFile = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTtl(): ?string
    {
        return $this->ttl;
    }

    public function setTtl(?string $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getZoneFile(): ?string
    {
        return $this->zoneFile;
    }

    public function setZoneFile(?string $zoneFile): void
    {
        $this->zoneFile = $zoneFile;
    }

    /**
     * @return array<string, mixed>
     */
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
        return $this->getName();
    }
}
