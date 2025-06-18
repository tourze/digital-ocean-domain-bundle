<?php

namespace DigitalOceanDomainBundle\Tests\Entity;

use DigitalOceanDomainBundle\Entity\Domain;
use PHPUnit\Framework\TestCase;

class DomainTest extends TestCase
{
    public function testConstruction_withDefaultValues(): void
    {
        $domain = new Domain();

        $this->assertEquals(0, $domain->getId());
        $this->assertNull($domain->getCreateTime());
        $this->assertNull($domain->getUpdateTime());
        $this->assertNull($domain->getTtl());
        $this->assertNull($domain->getZoneFile());
    }

    public function testGettersAndSetters_withValidValues(): void
    {
        $domain = new Domain();

        $name = 'example.com';
        $ttl = '1800';
        $zoneFile = 'zone file content';
        $createTime = new \DateTimeImmutable('2023-01-01');
        $updateTime = new \DateTimeImmutable('2023-01-02');

        $domain->setName($name)
            ->setTtl($ttl)
            ->setZoneFile($zoneFile);

        $domain->setCreateTime($createTime);
        $domain->setUpdateTime($updateTime);

        $this->assertEquals($name, $domain->getName());
        $this->assertEquals($ttl, $domain->getTtl());
        $this->assertEquals($zoneFile, $domain->getZoneFile());
        $this->assertEquals($createTime, $domain->getCreateTime());
        $this->assertEquals($updateTime, $domain->getUpdateTime());
    }

    public function testToPlainArray_returnsCorrectFormat(): void
    {
        $domain = new Domain();

        $name = 'example.com';
        $ttl = '1800';
        $zoneFile = 'zone file content';

        $domain->setName($name)
            ->setTtl($ttl)
            ->setZoneFile($zoneFile);

        $plainArray = $domain->toPlainArray();

        $this->assertEquals(0, $plainArray['id']);
        $this->assertEquals($name, $plainArray['name']);
        $this->assertEquals($ttl, $plainArray['ttl']);
        $this->assertEquals($zoneFile, $plainArray['zoneFile']);
    }

    public function testToAdminArray_returnsCorrectFormat(): void
    {
        $domain = new Domain();

        $name = 'example.com';
        $ttl = '1800';
        $zoneFile = 'zone file content';

        $domain->setName($name)
            ->setTtl($ttl)
            ->setZoneFile($zoneFile);

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

        $this->assertEquals($name, (string)$domain);
    }
}
