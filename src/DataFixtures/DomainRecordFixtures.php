<?php

namespace DigitalOceanDomainBundle\DataFixtures;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DomainRecordFixtures extends Fixture implements DependentFixtureInterface
{
    public const DOMAIN_RECORD_WWW_MYCOMPANY_COM_REFERENCE = 'domain-record-www-mycompany-com';
    public const DOMAIN_RECORD_MX_MYCOMPANY_COM_REFERENCE = 'domain-record-mx-mycompany-com';
    public const DOMAIN_RECORD_API_TESTSITE_ORG_REFERENCE = 'domain-record-api-testsite-org';
    public const DOMAIN_RECORD_TXT_WEBAPP_NET_REFERENCE = 'domain-record-txt-webapp-net';
    public const DOMAIN_RECORD_SRV_MYCOMPANY_COM_REFERENCE = 'domain-record-srv-mycompany-com';

    public function load(ObjectManager $manager): void
    {
        $record1 = new DomainRecord();
        $record1->setDomainName('mycompany.com');
        $record1->setRecordId(1001);
        $record1->setType('A');
        $record1->setName('www');
        $record1->setData('192.168.1.100');
        $record1->setTtl(3600);
        $manager->persist($record1);

        $record2 = new DomainRecord();
        $record2->setDomainName('mycompany.com');
        $record2->setRecordId(1002);
        $record2->setType('MX');
        $record2->setName('@');
        $record2->setData('mail.mycompany.com');
        $record2->setPriority(10);
        $record2->setTtl(3600);
        $manager->persist($record2);

        $record3 = new DomainRecord();
        $record3->setDomainName('testsite.org');
        $record3->setRecordId(2001);
        $record3->setType('CNAME');
        $record3->setName('api');
        $record3->setData('server.testsite.org');
        $record3->setTtl(1800);
        $manager->persist($record3);

        $record4 = new DomainRecord();
        $record4->setDomainName('webapp.net');
        $record4->setRecordId(3001);
        $record4->setType('TXT');
        $record4->setName('_verification');
        $record4->setData('v=spf1 include:_spf.google.com ~all');
        $record4->setTtl(7200);
        $manager->persist($record4);

        $record5 = new DomainRecord();
        $record5->setDomainName('mycompany.com');
        $record5->setRecordId(1003);
        $record5->setType('SRV');
        $record5->setName('_sip._tcp');
        $record5->setData('sip.mycompany.com');
        $record5->setPriority(10);
        $record5->setWeight(60);
        $record5->setPort(5060);
        $record5->setTtl(3600);
        $manager->persist($record5);

        $manager->flush();

        $this->addReference(self::DOMAIN_RECORD_WWW_MYCOMPANY_COM_REFERENCE, $record1);
        $this->addReference(self::DOMAIN_RECORD_MX_MYCOMPANY_COM_REFERENCE, $record2);
        $this->addReference(self::DOMAIN_RECORD_API_TESTSITE_ORG_REFERENCE, $record3);
        $this->addReference(self::DOMAIN_RECORD_TXT_WEBAPP_NET_REFERENCE, $record4);
        $this->addReference(self::DOMAIN_RECORD_SRV_MYCOMPANY_COM_REFERENCE, $record5);
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
        ];
    }
}
