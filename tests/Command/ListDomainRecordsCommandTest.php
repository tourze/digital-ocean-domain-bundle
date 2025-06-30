<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanDomainBundle\Command\ListDomainRecordsCommand;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListDomainRecordsCommandTest extends TestCase
{
    private DomainService&MockObject $domainService;
    private DomainRecordRepository&MockObject $domainRecordRepository;
    private ListDomainRecordsCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainService::class);
        $this->domainRecordRepository = $this->createMock(DomainRecordRepository::class);
        
        $this->command = new ListDomainRecordsCommand(
            $this->domainService,
            $this->domainRecordRepository
        );
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithRemoteApi(): void
    {
        $this->domainService->expects($this->once())
            ->method('listDomainRecords')
            ->with('example.com', 1, 50)
            ->willReturn([
                'domain_records' => [
                    [
                        'id' => 123,
                        'type' => 'A',
                        'name' => 'www',
                        'data' => '192.168.1.1',
                        'ttl' => 3600,
                    ],
                    [
                        'id' => 124,
                        'type' => 'MX',
                        'name' => '@',
                        'data' => 'mail.example.com',
                        'ttl' => 3600,
                    ],
                ],
                'meta' => ['total' => 2],
            ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--remote' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
        $this->assertStringContainsString('mail.example.com', $display);
        $this->assertStringContainsString('共 2 条记录', $display);
    }

    public function testExecuteWithLocalDatabase(): void
    {
        $record1 = $this->createMock(DomainRecord::class);
        $record1->method('getRecordId')->willReturn(123);
        $record1->method('getType')->willReturn('A');
        $record1->method('getName')->willReturn('www');
        $record1->method('getData')->willReturn('192.168.1.1');
        $record1->method('getTtl')->willReturn(3600);
        
        $record2 = $this->createMock(DomainRecord::class);
        $record2->method('getRecordId')->willReturn(124);
        $record2->method('getType')->willReturn('CNAME');
        $record2->method('getName')->willReturn('blog');
        $record2->method('getData')->willReturn('www.example.com');
        $record2->method('getTtl')->willReturn(3600);
        
        $this->domainRecordRepository->expects($this->once())
            ->method('findBy')
            ->with(['domainName' => 'example.com'], ['recordId' => 'ASC'], 50, 0)
            ->willReturn([$record1, $record2]);
            
        $this->domainRecordRepository->expects($this->once())
            ->method('count')
            ->with(['domainName' => 'example.com'])
            ->willReturn(2);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
        $this->assertStringContainsString('blog', $display);
        $this->assertStringContainsString('共 2 条记录', $display);
    }

    public function testExecuteWithTypeFilter(): void
    {
        $this->domainService->expects($this->once())
            ->method('listDomainRecords')
            ->willReturn([
                'domain_records' => [
                    [
                        'id' => 123,
                        'type' => 'A',
                        'name' => 'www',
                        'data' => '192.168.1.1',
                        'ttl' => 3600,
                    ],
                    [
                        'id' => 125,
                        'type' => 'CNAME',
                        'name' => 'blog',
                        'data' => 'www.example.com',
                        'ttl' => 3600,
                    ],
                ],
            ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--remote' => true,
            '--type' => 'A',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
        $this->assertStringNotContainsString('blog', $display);
    }

    public function testExecuteWithNameFilter(): void
    {
        $record = $this->createMock(DomainRecord::class);
        $record->method('getRecordId')->willReturn(123);
        $record->method('getType')->willReturn('A');
        $record->method('getName')->willReturn('www');
        $record->method('getData')->willReturn('192.168.1.1');
        $record->method('getTtl')->willReturn(3600);
        
        $this->domainRecordRepository->expects($this->once())
            ->method('findByDomainAndName')
            ->with('example.com', 'www', null)
            ->willReturn([$record]);
            
        $this->domainRecordRepository->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->commandTester->execute([
            'domain' => 'example.com',
            '--name' => 'www',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('www', $display);
        $this->assertStringContainsString('192.168.1.1', $display);
    }

    public function testExecuteWithNoRecords(): void
    {
        $this->domainRecordRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('没有找到任何记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->domainRecordRepository->expects($this->once())
            ->method('findBy')
            ->willThrowException(new TestException('Database error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('获取域名记录时发生错误: Database error', $this->commandTester->getDisplay());
    }
}