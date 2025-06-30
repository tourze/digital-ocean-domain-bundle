<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanDomainBundle\Command\CreateDomainRecordCommand;
use DigitalOceanDomainBundle\Service\DomainService;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateDomainRecordCommandTest extends TestCase
{
    private DomainService&MockObject $domainService;
    private CreateDomainRecordCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainService::class);
        $this->command = new CreateDomainRecordCommand($this->domainService);
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccess(): void
    {
        $recordData = [
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
            'priority' => null,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];
        
        $this->domainService->expects($this->once())
            ->method('createDomainRecord')
            ->with(
                'example.com',
                'A',
                'www',
                '192.168.1.1',
                null,
                null,
                null,
                null,
                null,
                null
            )
            ->willReturn($recordData);
            
        $this->domainService->expects($this->once())
            ->method('syncDomainRecords')
            ->with('example.com');

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testExecuteWithOptions(): void
    {
        $recordData = [
            'id' => 124,
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            'priority' => 10,
            'port' => null,
            'ttl' => 3600,
            'weight' => null,
            'flags' => null,
            'tag' => null,
        ];
        
        $this->domainService->expects($this->once())
            ->method('createDomainRecord')
            ->with(
                'example.com',
                'MX',
                '@',
                'mail.example.com',
                10,
                null,
                3600,
                null,
                null,
                null
            )
            ->willReturn($recordData);
            
        $this->domainService->expects($this->once())
            ->method('syncDomainRecords')
            ->with('example.com');

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'MX',
            'name' => '@',
            'data' => 'mail.example.com',
            '--priority' => '10',
            '--ttl' => '3600',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功添加域名记录', $this->commandTester->getDisplay());
    }

    public function testExecuteFailure(): void
    {
        $this->domainService->expects($this->once())
            ->method('createDomainRecord')
            ->willReturn([]);

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('添加域名记录失败', $this->commandTester->getDisplay());
    }

    public function testExecuteException(): void
    {
        $this->domainService->expects($this->once())
            ->method('createDomainRecord')
            ->willThrowException(new TestException('API error'));

        $this->commandTester->execute([
            'domain' => 'example.com',
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('添加域名记录时发生错误: API error', $this->commandTester->getDisplay());
    }
}