<?php

namespace DigitalOceanDomainBundle\Tests\Command;

use DigitalOceanAccountBundle\DigitalOceanAccountBundle;
use DigitalOceanDomainBundle\Command\SyncDomainsCommand;
use DigitalOceanDomainBundle\DigitalOceanDomainBundle;
use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Service\DomainServiceInterface;
use DigitalOceanDomainBundle\Tests\Exception\TestException;
use DigitalOceanDomainBundle\Tests\Helper\TestDomainService;
use DigitalOceanDomainBundle\Tests\Helper\TestEntityGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncDomainsCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncDomainsCommandTest extends AbstractCommandTestCase
{
    private TestDomainService $domainService;

    private SyncDomainsCommand $command;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->domainService = new TestDomainService();

        // 从容器中获取Command服务，符合集成测试最佳实践
        $container = self::getContainer();
        $container->set(DomainServiceInterface::class, $this->domainService);
        $command = $container->get(SyncDomainsCommand::class);
        if (!$command instanceof SyncDomainsCommand) {
            throw new \RuntimeException('Failed to get SyncDomainsCommand instance');
        }
        $this->command = $command;

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccess(): void
    {
        $domain1 = TestEntityGenerator::createDomain('example.com');
        $domain2 = TestEntityGenerator::createDomain('test.com');
        $domain3 = TestEntityGenerator::createDomain('demo.com');

        $this->domainService->setSyncDomainsResponse([$domain1, $domain2, $domain3]);

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
        $this->domainService->setSyncDomainsResponse([]);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('没有找到任何域名', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        $this->domainService->setSyncDomainsException(new TestException('API connection failed'));

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('同步域名时发生错误: API connection failed', $this->commandTester->getDisplay());
    }
}
