<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanDomainBundle\Command\UpdateDomainRecordCommand;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateDomainRecordCommandTest extends TestCase
{
    private DomainService&MockObject $domainService;
    private DomainRecordRepository&MockObject $domainRecordRepository;
    private UpdateDomainRecordCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainService::class);
        $this->domainRecordRepository = $this->createMock(DomainRecordRepository::class);
        
        $this->command = new UpdateDomainRecordCommand(
            $this->domainService,
            $this->domainRecordRepository
        );
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithLocalData(): void
    {
        $localRecord = $this->createMock(DomainRecord::class);
        $localRecord->method('getType')->willReturn('A');
        $localRecord->method('getName')->willReturn('www');
        $localRecord->method('getData')->willReturn('192.168.1.1');
        $localRecord->method('getPriority')->willReturn(null);
        $localRecord->method('getPort')->willReturn(null);
        $localRecord->method('getTtl')->willReturn(3600);
        $localRecord->method('getWeight')->willReturn(null);
        $localRecord->method('getFlags')->willReturn(null);
        $localRecord->method('getTag')->willReturn(null);
        
        $this->domainRecordRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'domainName' => 'example.com',
                'recordId' => 123,
            ])
            ->willReturn($localRecord);
            
        $this->domainService->expects($this->once())
            ->method('updateDomainRecord')
            ->with(
                'example.com',
                123,
                'A',
                'www',
                '192.168.1.1',
                null,
                null,
                3600,
                null,
                null,
                null
            )
            ->willReturn([
                'id' => 123,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.1',
                'ttl' => 3600,
            ]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--local' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功使用本地数据更新域名记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithLocalDataNotFound(): void
    {
        $this->domainRecordRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--local' => true,
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('找不到本地记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithRemoteData(): void
    {
        $currentRecord = [
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'ttl' => 3600,
        ];
        
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->with('example.com', 123)
            ->willReturn($currentRecord);
            
        $this->domainService->expects($this->once())
            ->method('updateDomainRecord')
            ->with(
                'example.com',
                123,
                'A',
                'www',
                '192.168.1.2',
                null,
                null,
                3600,
                null,
                null,
                null
            )
            ->willReturn([
                'id' => 123,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.2',
                'ttl' => 3600,
            ]);
            
        $this->domainService->expects($this->once())
            ->method('syncDomainRecords')
            ->with('example.com');

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--data' => '192.168.1.2',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功更新域名记录', $display);
        $this->assertStringContainsString('成功同步记录到本地数据库', $display);
    }

    public function testExecuteWithRemoteDataCancelled(): void
    {
        $currentRecord = [
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'ttl' => 3600,
        ];
        
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->willReturn($currentRecord);

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
            '--data' => '192.168.1.2',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('操作已取消', $this->commandTester->getDisplay());
    }

    public function testExecuteWithRemoteDataNotFound(): void
    {
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->willReturn([]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('找不到远程记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->willThrowException(new TestException('API error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('更新域名记录时发生错误: API error', $this->commandTester->getDisplay());
    }
}