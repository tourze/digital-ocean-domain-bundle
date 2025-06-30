<?php

namespace DigitalOceanDomainBundle\Tests\Repository;

use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use PHPUnit\Framework\TestCase;

class DomainRecordRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        // 测试 Repository 类存在且可以被加载
        $this->assertTrue(class_exists(DomainRecordRepository::class));
    }
}