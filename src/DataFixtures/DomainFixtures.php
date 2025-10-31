<?php

namespace DigitalOceanDomainBundle\DataFixtures;

use DigitalOceanDomainBundle\Entity\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DomainFixtures extends Fixture
{
    public const DOMAIN_MYCOMPANY_COM_REFERENCE = 'domain-mycompany-com';
    public const DOMAIN_TESTSITE_ORG_REFERENCE = 'domain-testsite-org';
    public const DOMAIN_WEBAPP_NET_REFERENCE = 'domain-webapp-net';

    public function load(ObjectManager $manager): void
    {
        $domain1 = new Domain();
        $domain1->setName('mycompany.com');
        $domain1->setTtl('1800');
        $domain1->setZoneFile('$ORIGIN mycompany.com.\n$TTL 1800\n');
        $manager->persist($domain1);

        $domain2 = new Domain();
        $domain2->setName('testsite.org');
        $domain2->setTtl('3600');
        $domain2->setZoneFile('$ORIGIN testsite.org.\n$TTL 3600\n');
        $manager->persist($domain2);

        $domain3 = new Domain();
        $domain3->setName('webapp.net');
        $domain3->setTtl('7200');
        $manager->persist($domain3);

        $manager->flush();

        $this->addReference(self::DOMAIN_MYCOMPANY_COM_REFERENCE, $domain1);
        $this->addReference(self::DOMAIN_TESTSITE_ORG_REFERENCE, $domain2);
        $this->addReference(self::DOMAIN_WEBAPP_NET_REFERENCE, $domain3);
    }
}
