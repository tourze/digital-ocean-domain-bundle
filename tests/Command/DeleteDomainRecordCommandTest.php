<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanDomainBundle\Command\DeleteDomainRecordCommand;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use DigitalOceanDomainBundle\Tests\Service\TestDomainService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(DeleteDomainRecordCommand::class)]
#[RunTestsInSeparateProcesses]
final class DeleteDomainRecordCommandTest extends AbstractCommandTestCase
{
    private TestDomainService $domainService;

    private DeleteDomainRecordCommand $command;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 创建Mock服务并注入容器
        $this->domainService = new TestDomainService();
        $container = self::getContainer();
        $container->set('DigitalOceanDomainBundle\Service\DomainServiceInterface', $this->domainService);
        $container->set(TestDomainService::class, $this->domainService);

        // 从容器获取Command
        $this->command = self::getService(DeleteDomainRecordCommand::class);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccessWithLocalRecord(): void
    {
        $this->domainService->resetCalls();

        // 模拟本地记录存在
        $this->domainService->setFindOneByResponse([
            'recordId' => 123,
            'domainName' => 'example.com',
        ]);
        $this->domainService->setDeleteDomainRecordResponse(true);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $deleteCalls = $this->domainService->getDeleteDomainRecordCalls();
        $this->assertCount(1, $deleteCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'recordId' => 123,
        ], $deleteCalls[0]);
    }

    public function testExecuteSuccessWithoutLocalRecord(): void
    {
        $this->domainService->resetCalls();

        // 模拟本地记录不存在，但远程存在
        $this->domainService->setFindOneByResponse(null);
        $this->domainService->setGetDomainRecordResponse([
            'id' => 123,
            'type' => 'A',
            'name' => 'www',
            'data' => '192.168.1.1',
        ]);
        $this->domainService->setDeleteDomainRecordResponse(true);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $getDomainRecordCalls = $this->domainService->getGetDomainRecordCalls();
        $this->assertCount(1, $getDomainRecordCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'recordId' => 123,
        ], $getDomainRecordCalls[0]);

        $deleteCalls = $this->domainService->getDeleteDomainRecordCalls();
        $this->assertCount(1, $deleteCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'recordId' => 123,
        ], $deleteCalls[0]);
    }

    public function testExecuteCancelledByUser(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setFindOneByResponse(null);
        $this->domainService->setGetDomainRecordResponse([]);

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('操作已取消', $this->commandTester->getDisplay());

        // 验证方法调用
        $getDomainRecordCalls = $this->domainService->getGetDomainRecordCalls();
        $this->assertCount(1, $getDomainRecordCalls);
        // 应该没有调用删除方法
        $deleteCalls = $this->domainService->getDeleteDomainRecordCalls();
        $this->assertCount(0, $deleteCalls);
    }

    public function testExecuteFailure(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setFindOneByResponse(null);
        $this->domainService->setGetDomainRecordResponse([]);
        $this->domainService->setDeleteDomainRecordResponse(false);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('删除域名记录失败', $this->commandTester->getDisplay());

        // 验证方法调用
        $getDomainRecordCalls = $this->domainService->getGetDomainRecordCalls();
        $this->assertCount(1, $getDomainRecordCalls);
        $deleteCalls = $this->domainService->getDeleteDomainRecordCalls();
        $this->assertCount(1, $deleteCalls);
    }

    public function testExecuteException(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setFindOneByResponse(null);
        $this->domainService->setGetDomainRecordException(new TestException('API error'));
        $this->domainService->setDeleteDomainRecordException(new TestException('Delete error'));

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('删除域名记录时发生错误: Delete error', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setFindOneByResponse([
            'recordId' => 456,
            'domainName' => 'test-domain.com',
        ]);
        $this->domainService->setDeleteDomainRecordResponse(true);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'test-domain.com',
            'record_id' => '456',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $deleteCalls = $this->domainService->getDeleteDomainRecordCalls();
        $this->assertCount(1, $deleteCalls);
        $this->assertEquals([
            'domainName' => 'test-domain.com',
            'recordId' => 456,
        ], $deleteCalls[0]);
    }

    public function testArgumentRecordId(): void
    {
        $this->domainService->resetCalls();

        $this->domainService->setFindOneByResponse([
            'recordId' => 789,
            'domainName' => 'example.com',
        ]);
        $this->domainService->setDeleteDomainRecordResponse(true);

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '789',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());

        // 验证方法调用
        $deleteCalls = $this->domainService->getDeleteDomainRecordCalls();
        $this->assertCount(1, $deleteCalls);
        $this->assertEquals([
            'domainName' => 'example.com',
            'recordId' => 789,
        ], $deleteCalls[0]);
    }
}
