<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests;

use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DigitalOceanDomainBundle::class)]
#[RunTestsInSeparateProcesses]
final class DigitalOceanDomainBundleTest extends AbstractBundleTestCase
{
}
