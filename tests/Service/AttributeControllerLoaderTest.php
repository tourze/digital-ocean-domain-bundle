<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanDomainBundle\Service\AttributeControllerLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * AttributeControllerLoader 测试
 *
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testCanBeInstantiated(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertInstanceOf(AttributeControllerLoader::class, $loader);
    }

    public function testLoadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->load(null);
        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    public function testSupportsReturnsFalse(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertFalse($loader->supports(null));
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();
        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    public function testLoadAndAutoloadReturnSameCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $loadCollection = $loader->load(null);
        $autoloadCollection = $loader->autoload();
        $this->assertSame($loadCollection, $autoloadCollection);
    }
}
