<?php

namespace DigitalOceanDomainBundle\Tests\Exception;

use DigitalOceanDomainBundle\Exception\ConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ConfigurationException::class)]
final class ConfigurationExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return ConfigurationException::class;
    }

    protected function getExpectedBaseExceptionClass(): string
    {
        return \RuntimeException::class;
    }
}
