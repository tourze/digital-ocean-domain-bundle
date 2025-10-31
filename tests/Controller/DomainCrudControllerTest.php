<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Controller;

use DigitalOceanDomainBundle\Controller\DomainCrudController;
use DigitalOceanDomainBundle\Entity\Domain;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * DomainCrudController 测试
 *
 * @internal
 */
#[CoversClass(DomainCrudController::class)]
#[RunTestsInSeparateProcesses]
final class DomainCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): DomainCrudController
    {
        return self::getService(DomainCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID列' => ['ID'];
        yield '域名列' => ['域名'];
        yield '创建时间列' => ['创建时间'];
        yield '更新时间列' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield '域名字段' => ['name'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield '域名字段' => ['name'];
    }

    public function testControllerCanBeInstantiated(): void
    {
        $client = self::createClientWithDatabase();
        $controller = self::getService(DomainCrudController::class);
        $this->assertInstanceOf(DomainCrudController::class, $controller);
    }

    public function testEntityFqcnIsCorrect(): void
    {
        $this->assertSame(
            Domain::class,
            DomainCrudController::getEntityFqcn()
        );
    }

    public function testCrudConfigurationIsValid(): void
    {
        $client = self::createClientWithDatabase();
        $controller = self::getService(DomainCrudController::class);

        // 验证配置方法返回正确的类型
        $fields = iterator_to_array($controller->configureFields('index'));
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);

        $crud = $controller->configureCrud(Crud::new());
        $this->assertInstanceOf(Crud::class, $crud);

        $filters = $controller->configureFilters(Filters::new());
        $this->assertInstanceOf(Filters::class, $filters);
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新建页面并提交空表单，验证422
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));
        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="Domain"]')->form();
        $client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        // 获取验证器服务
        $validator = self::getService(ValidatorInterface::class);

        // 测试空Domain实体的验证错误
        $domain = new Domain();

        // 验证必填字段
        $violations = $validator->validate($domain);
        $this->assertGreaterThan(0, $violations->count(), '空Domain实体应该有验证错误');

        // 验证具体的必填字段错误
        $nameViolations = $validator->validateProperty($domain, 'name');
        $this->assertGreaterThan(0, $nameViolations->count(), 'name字段应该有验证错误');

        $firstViolation = $nameViolations->get(0);
        $this->assertNotNull($firstViolation, 'name字段应该有第一个验证错误');
        $this->assertEquals('域名不能为空', $firstViolation->getMessage());

        // 测试有效的Domain实体
        $validDomain = new Domain();
        $validDomain->setName('example.com');

        $violations = $validator->validate($validDomain);
        $this->assertCount(0, $violations, '有效的Domain实体应该通过验证');
    }
}
