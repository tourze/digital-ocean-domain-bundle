<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\DependencyInjection;

use DigitalOceanDomainBundle\DependencyInjection\DigitalOceanDomainExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DigitalOceanDomainExtension::class)]
final class DigitalOceanDomainExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private DigitalOceanDomainExtension $extension;

    private ContainerBuilder $container;

    public function testLoadRegistersCoreServices(): void
    {
        $this->extension->load([], $this->container);

        // 验证扩展加载成功 - 检查服务是否被加载
        $this->assertTrue($this->container->has('DigitalOceanDomainBundle\Service\DomainService'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new DigitalOceanDomainExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }
}
