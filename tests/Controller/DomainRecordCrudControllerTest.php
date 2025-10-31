<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Tests\Controller;

use DigitalOceanDomainBundle\Controller\DomainRecordCrudController;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * DomainRecordCrudController 测试
 *
 * @internal
 */
#[CoversClass(DomainRecordCrudController::class)]
#[RunTestsInSeparateProcesses]
final class DomainRecordCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): DomainRecordCrudController
    {
        return self::getService(DomainRecordCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID列' => ['ID'];
        yield '域名列' => ['域名'];
        yield '记录ID列' => ['记录ID'];
        yield '记录类型列' => ['记录类型'];
        yield '记录名称列' => ['记录名称'];
        yield '记录值列' => ['记录值'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield '域名字段' => ['domainName'];
        yield '记录ID字段' => ['recordId'];
        yield '记录类型字段' => ['type'];
        yield '记录名称字段' => ['name'];
        yield '记录值字段' => ['data'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield '域名字段' => ['domainName'];
        yield '记录ID字段' => ['recordId'];
        yield '记录类型字段' => ['type'];
        yield '记录名称字段' => ['name'];
        yield '记录值字段' => ['data'];
    }

    public function testControllerCanBeInstantiated(): void
    {
        $client = self::createClientWithDatabase();
        $controller = self::getService(DomainRecordCrudController::class);
        $this->assertInstanceOf(DomainRecordCrudController::class, $controller);
    }

    public function testEntityFqcnIsCorrect(): void
    {
        $this->assertSame(
            DomainRecord::class,
            DomainRecordCrudController::getEntityFqcn()
        );
    }

    public function testCrudConfigurationIsValid(): void
    {
        $client = self::createClientWithDatabase();
        $controller = self::getService(DomainRecordCrudController::class);

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
        $form = $crawler->filter('form[name="DomainRecord"]')->form();
        $client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        // 获取验证器服务
        $validator = self::getService(ValidatorInterface::class);

        // 测试空DomainRecord实体的验证错误
        $domainRecord = new DomainRecord();

        // 验证必填字段
        $violations = $validator->validate($domainRecord);
        $this->assertGreaterThan(0, $violations->count(), '空DomainRecord实体应该有验证错误');

        // 验证具体的必填字段错误
        $domainNameViolations = $validator->validateProperty($domainRecord, 'domainName');
        $this->assertGreaterThan(0, $domainNameViolations->count(), 'domainName字段应该有验证错误');

        $firstDomainNameViolation = $domainNameViolations->get(0);
        $this->assertNotNull($firstDomainNameViolation, 'domainName字段应该有第一个验证错误');
        $this->assertEquals('域名不能为空', $firstDomainNameViolation->getMessage());

        $recordIdViolations = $validator->validateProperty($domainRecord, 'recordId');
        $this->assertGreaterThan(0, $recordIdViolations->count(), 'recordId字段应该有验证错误');

        $firstRecordIdViolation = $recordIdViolations->get(0);
        $this->assertNotNull($firstRecordIdViolation, 'recordId字段应该有第一个验证错误');
        $this->assertEquals('记录ID不能为空', $firstRecordIdViolation->getMessage());

        $typeViolations = $validator->validateProperty($domainRecord, 'type');
        $this->assertGreaterThan(0, $typeViolations->count(), 'type字段应该有验证错误');

        $firstTypeViolation = $typeViolations->get(0);
        $this->assertNotNull($firstTypeViolation, 'type字段应该有第一个验证错误');
        $this->assertEquals('记录类型不能为空', $firstTypeViolation->getMessage());

        $nameViolations = $validator->validateProperty($domainRecord, 'name');
        $this->assertGreaterThan(0, $nameViolations->count(), 'name字段应该有验证错误');

        $firstNameViolation = $nameViolations->get(0);
        $this->assertNotNull($firstNameViolation, 'name字段应该有第一个验证错误');
        $this->assertEquals('记录名称不能为空', $firstNameViolation->getMessage());

        $dataViolations = $validator->validateProperty($domainRecord, 'data');
        $this->assertGreaterThan(0, $dataViolations->count(), 'data字段应该有验证错误');

        $firstDataViolation = $dataViolations->get(0);
        $this->assertNotNull($firstDataViolation, 'data字段应该有第一个验证错误');
        $this->assertEquals('记录值不能为空', $firstDataViolation->getMessage());

        // 测试有效的DomainRecord实体
        $validDomainRecord = new DomainRecord();
        $validDomainRecord->setDomainName('example.com');
        $validDomainRecord->setRecordId(1);
        $validDomainRecord->setType('A');
        $validDomainRecord->setName('www');
        $validDomainRecord->setData('192.168.1.1');

        $violations = $validator->validate($validDomainRecord);
        $this->assertCount(0, $violations, '有效的DomainRecord实体应该通过验证');
    }
}
