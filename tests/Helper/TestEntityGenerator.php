<?php

namespace DigitalOceanDomainBundle\Tests\Helper;

use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;

class TestEntityGenerator
{
    public static function createDomain(
        string $name = 'example.com',
        ?string $ttl = '1800',
        ?string $zoneFile = null,
    ): Domain {
        $domain = new Domain();
        $domain->setName($name);
        $domain->setTtl($ttl);
        $domain->setZoneFile($zoneFile);

        return $domain;
    }

    public static function createDomainRecord(
        string $domainName = 'example.com',
        int $recordId = 1,
        string $type = 'A',
        string $name = '@',
        string $data = '192.168.1.1',
        ?int $priority = null,
        ?int $port = null,
        ?int $ttl = 1800,
        ?int $weight = null,
        ?string $flags = null,
        ?string $tag = null,
    ): DomainRecord {
        $record = new DomainRecord();
        $record->setDomainName($domainName);
        $record->setRecordId($recordId);
        $record->setType($type);
        $record->setName($name);
        $record->setData($data);
        $record->setPriority($priority);
        $record->setPort($port);
        $record->setTtl($ttl);
        $record->setWeight($weight);
        $record->setFlags($flags);
        $record->setTag($tag);

        return $record;
    }
}
