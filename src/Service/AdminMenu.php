<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Service;

use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        $digitalOceanCenter = $item->getChild('DigitalOcean管理');
        if (null === $digitalOceanCenter) {
            $digitalOceanCenter = $item->addChild('DigitalOcean管理');
        }

        // 域名管理
        $digitalOceanCenter->addChild('域名管理')->setUri($this->linkGenerator->getCurdListPage(Domain::class));

        // 域名记录管理
        $digitalOceanCenter->addChild('域名记录管理')->setUri($this->linkGenerator->getCurdListPage(DomainRecord::class));
    }
}
