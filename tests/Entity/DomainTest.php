<?php

namespace DigitalOceanDomainBundle\Tests\Entity;

use DigitalOceanDomainBundle\Entity\Domain;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Domain::class)]
final class DomainTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Domain();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name_string' => ['name', 'example.com'];
        yield 'ttl_string' => ['ttl', '1800'];
        yield 'zoneFile_string' => ['zoneFile', 'zone file content'];
    }

    public function testToPlainArrayReturnsCorrectFormat(): void
    {
        $domain = new Domain();

        $name = 'example.com';
        $ttl = '1800';
        $zoneFile = 'zone file content';

        $domain->setName($name);
        $domain->setTtl($ttl);
        $domain->setZoneFile($zoneFile);

        $plainArray = $domain->toPlainArray();

        $this->assertEquals(0, $plainArray['id']);
        $this->assertEquals($name, $plainArray['name']);
        $this->assertEquals($ttl, $plainArray['ttl']);
        $this->assertEquals($zoneFile, $plainArray['zoneFile']);
    }

    public function testToAdminArrayReturnsCorrectFormat(): void
    {
        $domain = new Domain();

        $name = 'example.com';
        $ttl = '1800';
        $zoneFile = 'zone file content';

        $domain->setName($name);
        $domain->setTtl($ttl);
        $domain->setZoneFile($zoneFile);

        $adminArray = $domain->toAdminArray();

        $this->assertEquals(0, $adminArray['id']);
        $this->assertEquals($name, $adminArray['name']);
        $this->assertEquals($ttl, $adminArray['ttl']);
        $this->assertEquals($zoneFile, $adminArray['zoneFile']);
    }

    public function testToString(): void
    {
        $domain = new Domain();
        $name = 'example.com';
        $domain->setName($name);

        $this->assertEquals($name, (string) $domain);
    }
}
