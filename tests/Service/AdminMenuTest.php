<?php

/**
 * 使用 Knp\Menu 的真实实现，避免在测试中手写接口实现。
 */

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Service;

use DigitalOceanDomainBundle\Entity\Domain;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Service\AdminMenu;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * AdminMenu 测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，满足抽象方法要求
    }

    public function testCanBeInstantiated(): void
    {
        $linkGenerator = new class implements LinkGeneratorInterface {
            public function getCurdListPage(string $entityClass): string
            {
                return '';
            }

            public function extractEntityFqcn(string $entityClass): string
            {
                return $entityClass;
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // 空实现，满足接口要求
            }
        };
        $container = self::getContainer();
        $container->set(LinkGeneratorInterface::class, $linkGenerator);

        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testInvokeAddsMenuItems(): void
    {
        $linkGenerator = new class implements LinkGeneratorInterface {
            private int $count = 0;

            public function getCurdListPage(string $entityClass): string
            {
                ++$this->count;

                return match ($entityClass) {
                    Domain::class => '/admin/domain',
                    DomainRecord::class => '/admin/domain-record',
                    default => '',
                };
            }

            public function extractEntityFqcn(string $entityClass): string
            {
                return $entityClass;
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // 空实现，满足接口要求
            }

            public function getCount(): int
            {
                return $this->count;
            }
        };
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        $factory = new MenuFactory();
        $root = $factory->createItem('root');

        $adminMenu = self::getService(AdminMenu::class);
        $adminMenu($root);

        $center = $root->getChild('DigitalOcean管理');
        $this->assertNotNull($center, '应创建 DigitalOcean管理 菜单');

        $domainItem = $center->getChild('域名管理');
        $recordItem = $center->getChild('域名记录管理');

        $this->assertNotNull($domainItem);
        $this->assertNotNull($recordItem);
        $this->assertSame('/admin/domain', $domainItem->getUri());
        $this->assertSame('/admin/domain-record', $recordItem->getUri());
        $this->assertSame(2, $linkGenerator->getCount());
    }

    public function testInvokeWithExistingDigitalOceanMenu(): void
    {
        $linkGenerator = new class implements LinkGeneratorInterface {
            private int $count = 0;

            public function getCurdListPage(string $entityClass): string
            {
                ++$this->count;

                return '/admin/test';
            }

            public function extractEntityFqcn(string $entityClass): string
            {
                return $entityClass;
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // 空实现，满足接口要求
            }

            public function getCount(): int
            {
                return $this->count;
            }
        };
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        $factory = new MenuFactory();
        $root = $factory->createItem('root');
        $root->addChild('DigitalOcean管理');

        $adminMenu = self::getService(AdminMenu::class);
        $adminMenu($root);

        $center = $root->getChild('DigitalOcean管理');
        $this->assertNotNull($center);

        $this->assertSame('/admin/test', $center->getChild('域名管理')?->getUri());
        $this->assertSame('/admin/test', $center->getChild('域名记录管理')?->getUri());
        $this->assertSame(2, $linkGenerator->getCount());
    }
}
