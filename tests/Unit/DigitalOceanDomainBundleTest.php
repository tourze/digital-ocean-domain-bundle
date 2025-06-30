<?php

namespace DigitalOceanDomainBundle\Tests\Unit;

use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DigitalOceanDomainBundleTest extends TestCase
{
    public function testBundleIsInstantiable(): void
    {
        $bundle = new DigitalOceanDomainBundle();
        
        $this->assertInstanceOf(Bundle::class, $bundle);
        $this->assertInstanceOf(DigitalOceanDomainBundle::class, $bundle);
    }
}