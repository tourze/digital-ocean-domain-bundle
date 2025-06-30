<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanDomainBundle\Command\DeleteDomainRecordCommand;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteDomainRecordCommandTest extends TestCase
{
    private DomainService&MockObject $domainService;
    private DomainRecordRepository&MockObject $domainRecordRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private DeleteDomainRecordCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainService::class);
        $this->domainRecordRepository = $this->createMock(DomainRecordRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->command = new DeleteDomainRecordCommand(
            $this->domainService,
            $this->domainRecordRepository,
            $this->entityManager
        );
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccessWithLocalRecord(): void
    {
        $localRecord = $this->createMock(DomainRecord::class);
        $localRecord->expects($this->once())->method('getRecordId')->willReturn(123);
        $localRecord->expects($this->once())->method('getDomainName')->willReturn('example.com');
        $localRecord->expects($this->once())->method('getType')->willReturn('A');
        $localRecord->expects($this->once())->method('getName')->willReturn('www');
        $localRecord->expects($this->once())->method('getData')->willReturn('192.168.1.1');
        
        $this->domainRecordRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'domainName' => 'example.com',
                'recordId' => 123,
            ])
            ->willReturn($localRecord);
            
        $this->domainService->expects($this->once())
            ->method('deleteDomainRecord')
            ->with('example.com', 123)
            ->willReturn(true);
            
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($localRecord);
            
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());
        $this->assertStringContainsString('成功删除本地数据库记录', $this->commandTester->getDisplay());
    }

    public function testExecuteSuccessWithoutLocalRecord(): void
    {
        $this->domainRecordRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
            
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->with('example.com', 123)
            ->willReturn([
                'id' => 123,
                'type' => 'A',
                'name' => 'www',
                'data' => '192.168.1.1',
            ]);
            
        $this->domainService->expects($this->once())
            ->method('deleteDomainRecord')
            ->with('example.com', 123)
            ->willReturn(true);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());
    }

    public function testExecuteCancelledByUser(): void
    {
        $this->domainRecordRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
            
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->willReturn([]);

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('操作已取消', $this->commandTester->getDisplay());
    }

    public function testExecuteFailure(): void
    {
        $this->domainRecordRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
            
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->willReturn([]);
            
        $this->domainService->expects($this->once())
            ->method('deleteDomainRecord')
            ->willReturn(false);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('删除域名记录失败', $this->commandTester->getDisplay());
    }

    public function testExecuteException(): void
    {
        $this->domainRecordRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
            
        $this->domainService->expects($this->once())
            ->method('getDomainRecord')
            ->willThrowException(new TestException('API error'));
            
        $this->domainService->expects($this->once())
            ->method('deleteDomainRecord')
            ->willThrowException(new TestException('Delete error'));

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('删除域名记录时发生错误: Delete error', $this->commandTester->getDisplay());
    }
}