<?php

namespace DigitalOceanDomainBundle\Tests\DependencyInjection;

use DigitalOceanDomainBundle\DependencyInjection\DigitalOceanDomainExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DigitalOceanDomainExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new DigitalOceanDomainExtension();
        
        $extension->load([], $container);
        
        // 验证扩展加载成功 - 检查服务是否被加载
        $this->assertTrue($container->has('DigitalOceanDomainBundle\Service\DomainService'));
    }
}