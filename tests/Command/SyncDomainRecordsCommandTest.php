<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanDomainBundle\Command\SyncDomainRecordsCommand;
use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncDomainRecordsCommandTest extends TestCase
{
    private DomainService&MockObject $domainService;
    private DomainRepository&MockObject $domainRepository;
    private SyncDomainRecordsCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainService::class);
        $this->domainRepository = $this->createMock(DomainRepository::class);
        
        $this->command = new SyncDomainRecordsCommand(
            $this->domainService,
            $this->domainRepository
        );
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithSpecificDomain(): void
    {
        $record1 = $this->createMock(DomainRecord::class);
        $record1->method('getDomainName')->willReturn('example.com');
        $record1->method('getRecordId')->willReturn(123);
        $record1->method('getType')->willReturn('A');
        $record1->method('getName')->willReturn('www');
        $record1->method('getData')->willReturn('192.168.1.1');
        
        $record2 = $this->createMock(DomainRecord::class);
        $record2->method('getDomainName')->willReturn('example.com');
        $record2->method('getRecordId')->willReturn(124);
        $record2->method('getType')->willReturn('MX');
        $record2->method('getName')->willReturn('@');
        $record2->method('getData')->willReturn('mail.example.com');
        
        $this->domainService->expects($this->once())
            ->method('syncDomainRecords')
            ->with('example.com')
            ->willReturn([$record1, $record2]);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步 2 条记录', $display);
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('mail.example.com', $display);
    }

    public function testExecuteWithAllDomains(): void
    {
        $domain1 = $this->createMock(Domain::class);
        $domain1->method('getName')->willReturn('example.com');
        
        $domain2 = $this->createMock(Domain::class);
        $domain2->method('getName')->willReturn('test.com');
        
        $this->domainRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$domain1, $domain2]);
            
        $record1 = $this->createMock(DomainRecord::class);
        $record1->method('getDomainName')->willReturn('example.com');
        $record1->method('getRecordId')->willReturn(123);
        $record1->method('getType')->willReturn('A');
        $record1->method('getName')->willReturn('www');
        $record1->method('getData')->willReturn('192.168.1.1');
        
        $record2 = $this->createMock(DomainRecord::class);
        $record2->method('getDomainName')->willReturn('test.com');
        $record2->method('getRecordId')->willReturn(125);
        $record2->method('getType')->willReturn('A');
        $record2->method('getName')->willReturn('@');
        $record2->method('getData')->willReturn('192.168.1.2');
            
        $this->domainService->expects($this->exactly(2))
            ->method('syncDomainRecords')
            ->willReturnMap([
                ['example.com', [$record1]],
                ['test.com', [$record2]],
            ]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步总计 2 条记录', $display);
    }

    public function testExecuteWithNoDomains(): void
    {
        $this->domainRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('没有找到任何域名', $this->commandTester->getDisplay());
    }

    public function testExecuteWithPartialErrors(): void
    {
        $domain1 = $this->createMock(Domain::class);
        $domain1->method('getName')->willReturn('example.com');
        
        $domain2 = $this->createMock(Domain::class);
        $domain2->method('getName')->willReturn('test.com');
        
        $this->domainRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$domain1, $domain2]);
            
        $record = $this->createMock(DomainRecord::class);
        $record->method('getDomainName')->willReturn('example.com');
        $record->method('getRecordId')->willReturn(123);
        $record->method('getType')->willReturn('A');
        $record->method('getName')->willReturn('www');
        $record->method('getData')->willReturn('192.168.1.1');
            
        $this->domainService->expects($this->exactly(2))
            ->method('syncDomainRecords')
            ->willReturnCallback(function ($domain) use ($record) {
                if ($domain === 'example.com') {
                    return [$record];
                } else {
                    throw new TestException('API error');
                }
            });

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功同步总计 1 条记录', $display);
        $this->assertStringContainsString('同步过程中发生 1 个错误', $display);
        $this->assertStringContainsString('API error', $display);
    }

    public function testExecuteWithSpecificDomainException(): void
    {
        $this->domainService->expects($this->once())
            ->method('syncDomainRecords')
            ->willThrowException(new TestException('Sync error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('同步域名记录时发生错误: Sync error', $this->commandTester->getDisplay());
    }
}