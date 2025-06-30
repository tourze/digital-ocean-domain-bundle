<?php

namespace DigitalOceanDomainBundle\Tests\Unit\Exception;

use DigitalOceanDomainBundle\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

class ConfigurationExceptionTest extends TestCase
{
    public function testExceptionIsInstantiable(): void
    {
        $exception = new ConfigurationException('Test message');
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
    
    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new ConfigurationException('Test message', 500, $previous);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}