<?php

namespace DigitalOceanDomainBundle\Tests\Repository;

use DigitalOceanDomainBundle\Repository\DomainRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class DomainRepositoryTest extends TestCase
{
    public function testConstruction_createsRepository(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new DomainRepository($registry);

        $this->assertInstanceOf(DomainRepository::class, $repository);
    }
}
