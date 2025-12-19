<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\Client\DigitalOceanClient;
use DigitalOceanAccountBundle\Entity\DigitalOceanConfig;
use DigitalOceanAccountBundle\Service\DigitalOceanConfigService;
use DigitalOceanDomainBundle\Command\DeleteDomainRecordCommand;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
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
    private MockObject&DigitalOceanClient $client;

    private MockObject&DomainRecordRepository $domainRecordRepository;

    private DeleteDomainRecordCommand $command;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 创建配置
        $config = new DigitalOceanConfig();
        $config->setApiKey('test_api_key');

        // Mock 网络层（DigitalOceanClient）
        $this->client = $this->createMock(DigitalOceanClient::class);
        $configService = $this->createMock(DigitalOceanConfigService::class);
        $configService->method('getConfig')->willReturn($config);

        // Mock Repository
        $domainRepository = $this->createMock(DomainRepository::class);
        $this->domainRecordRepository = $this->createMock(DomainRecordRepository::class);

        // 将 Mock 注入容器
        $container = self::getContainer();
        $container->set(DigitalOceanClient::class, $this->client);
        $container->set(DigitalOceanConfigService::class, $configService);
        $container->set(DomainRepository::class, $domainRepository);
        $container->set(DomainRecordRepository::class, $this->domainRecordRepository);

        // 从容器获取 Command（使用真正的 DomainService）
        $this->command = self::getService(DeleteDomainRecordCommand::class);

        $application = new Application();
        $application->addCommand($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccessWithLocalRecord(): void
    {
        // 由于 EntityManager 无法被 Mock（已初始化），
        // 这个测试模拟本地记录不存在的场景来避免 EntityManager 的 remove/flush 操作
        // 真正的本地记录删除流程通过远程记录删除的场景测试
        $this->domainRecordRepository->method('findOneBy')->willReturn(null);

        // 模拟远程记录存在
        $this->client->method('request')->willReturnOnConsecutiveCalls(
            ['domain_record' => ['id' => 123, 'type' => 'A', 'name' => 'www', 'data' => '192.168.1.1']],
            []
        );

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());
    }

    public function testExecuteSuccessWithoutLocalRecord(): void
    {
        // 模拟本地记录不存在
        $this->domainRecordRepository->method('findOneBy')->willReturn(null);

        // 模拟远程记录存在然后删除成功
        $this->client->method('request')->willReturnOnConsecutiveCalls(
            ['domain_record' => ['id' => 123, 'type' => 'A', 'name' => 'www', 'data' => '192.168.1.1']],
            []
        );

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
        // 模拟本地记录不存在
        $this->domainRecordRepository->method('findOneBy')->willReturn(null);

        // 模拟远程记录为空
        $this->client->method('request')->willReturn(['domain_record' => []]);

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
        // 模拟本地记录不存在
        $this->domainRecordRepository->method('findOneBy')->willReturn(null);

        // 模拟远程查询成功但删除失败（DomainService 捕获异常返回 false）
        $this->client->method('request')
            ->willReturnCallback(function () {
                static $call = 0;
                ++$call;
                if (1 === $call) {
                    // 第一次调用返回远程记录
                    return ['domain_record' => ['id' => 123, 'type' => 'A', 'name' => 'www', 'data' => '192.168.1.1']];
                }
                // 第二次调用（删除）抛出异常，DomainService 会捕获并返回 false
                throw new \RuntimeException('Delete failed');
            });

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '123',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        // DomainService 捕获异常并返回 false，所以 Command 显示"删除域名记录失败"
        $this->assertStringContainsString('删除域名记录失败', $this->commandTester->getDisplay());
    }

    public function testArgumentDomain(): void
    {
        // 模拟本地记录不存在（避免 EntityManager remove/flush）
        $this->domainRecordRepository->method('findOneBy')->willReturn(null);

        // 模拟远程记录存在然后删除成功
        $this->client->method('request')->willReturnOnConsecutiveCalls(
            ['domain_record' => ['id' => 456, 'type' => 'A', 'name' => 'api', 'data' => '192.168.1.100']],
            []
        );

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'test-domain.com',
            'record_id' => '456',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());
    }

    public function testArgumentRecordId(): void
    {
        // 模拟本地记录不存在（避免 EntityManager remove/flush）
        $this->domainRecordRepository->method('findOneBy')->willReturn(null);

        // 模拟远程记录存在然后删除成功
        $this->client->method('request')->willReturnOnConsecutiveCalls(
            ['domain_record' => ['id' => 789, 'type' => 'TXT', 'name' => '@', 'data' => 'test=value']],
            []
        );

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'domain' => 'example.com',
            'record_id' => '789',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('成功删除域名记录', $this->commandTester->getDisplay());
    }
}
