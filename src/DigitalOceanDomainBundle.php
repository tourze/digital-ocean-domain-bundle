<?php

namespace DigitalOceanDomainBundle;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class DigitalOceanDomainBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DigitalOceanAccountBundle::class => ['all' => true],
        ];
    }
}
