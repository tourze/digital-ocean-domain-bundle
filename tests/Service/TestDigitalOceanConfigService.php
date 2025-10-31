<?php

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;

/**
 * 测试用的 DigitalOceanConfigService 模拟类
 */
class TestDigitalOceanConfigService
{
    public function __construct(
        private readonly DigitalOceanConfig $config,
    ) {
    }

    public function getConfig(): ?DigitalOceanConfig
    {
        return $this->config;
    }
}
