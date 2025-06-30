<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanDomainBundle\Command\SyncDomainsCommand;
use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Service\DomainService;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncDomainsCommandTest extends TestCase
{
    private DomainService&MockObject $domainService;
    private SyncDomainsCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainService::class);
        $this->command = new SyncDomainsCommand($this->domainService);
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccess(): void
    {
        $domain1 = $this->createMock(Domain::class);
        $domain1->method('getName')->willReturn('example.com');
        
        $domain2 = $this->createMock(Domain::class);
        $domain2->method('getName')->willReturn('test.com');
        
        $domain3 = $this->createMock(Domain::class);
        $domain3->method('getName')->willReturn('demo.com');
        
        $this->domainService->expects($this->once())
            ->method('syncDomains')
            ->willReturn([$domain1, $domain2, $domain3]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步 3 个域名', $display);
        $this->assertStringContainsString('example.com', $display);
        $this->assertStringContainsString('test.com', $display);
        $this->assertStringContainsString('demo.com', $display);
    }

    public function testExecuteWithNoDomains(): void
    {
        $this->domainService->expects($this->once())
            ->method('syncDomains')
            ->willReturn([]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('没有找到任何域名', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->domainService->expects($this->once())
            ->method('syncDomains')
            ->willThrowException(new TestException('API connection failed'));

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('同步域名时发生错误: API connection failed', $this->commandTester->getDisplay());
    }
}