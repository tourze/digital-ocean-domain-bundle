<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Controller;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

#[AdminCrud(
    routePath: '/digital-ocean/domain-record',
    routeName: 'digital_ocean_domain_record'
)]
final class DomainRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DomainRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('DigitalOcean域名记录')
            ->setEntityLabelInPlural('DigitalOcean域名记录管理')
            ->setPageTitle(Crud::PAGE_INDEX, 'DigitalOcean域名记录列表')
            ->setPageTitle(Crud::PAGE_NEW, '新建DigitalOcean域名记录')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑DigitalOcean域名记录')
            ->setPageTitle(Crud::PAGE_DETAIL, 'DigitalOcean域名记录详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['domainName', 'name', 'type', 'data'])
            ->showEntityActionsInlined()
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('domainName', '域名')
            ->setColumns('col-md-4')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('域名，如 example.com')
        ;

        yield IntegerField::new('recordId', '记录ID')
            ->setColumns('col-md-4')
            ->setRequired(true)
            ->setHelp('DigitalOcean API中的记录ID')
        ;

        yield TextField::new('type', '记录类型')
            ->setColumns('col-md-4')
            ->setRequired(true)
            ->setMaxLength(100)
            ->setHelp('DNS记录类型，如 A, AAAA, CNAME, MX 等')
        ;

        yield TextField::new('name', '记录名称')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('DNS记录的名称或子域名')
        ;

        yield TextareaField::new('data', '记录值')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(65535)
            ->setHelp('DNS记录的值，如IP地址或目标主机名')
            ->setNumOfRows(3)
        ;

        yield IntegerField::new('priority', '优先级')
            ->setColumns('col-md-3')
            ->setRequired(false)
            ->setHelp('MX或SRV记录的优先级')
            ->hideOnIndex()
        ;

        yield IntegerField::new('port', '端口')
            ->setColumns('col-md-3')
            ->setRequired(false)
            ->setHelp('SRV记录的端口号')
            ->hideOnIndex()
        ;

        yield IntegerField::new('ttl', 'TTL')
            ->setColumns('col-md-3')
            ->setRequired(false)
            ->setHelp('记录的生存时间（秒）')
            ->hideOnIndex()
        ;

        yield IntegerField::new('weight', '权重')
            ->setColumns('col-md-3')
            ->setRequired(false)
            ->setHelp('SRV记录的权重')
            ->hideOnIndex()
        ;

        yield TextField::new('flags', '标志位')
            ->setColumns('col-md-6')
            ->setRequired(false)
            ->setMaxLength(10)
            ->setHelp('CAA记录的标志位')
            ->hideOnIndex()
        ;

        yield TextField::new('tag', '标签')
            ->setColumns('col-md-6')
            ->setRequired(false)
            ->setMaxLength(10)
            ->setHelp('CAA记录的标签')
            ->hideOnIndex()
        ;

        yield AssociationField::new('config', '配置')
            ->setColumns('col-md-12')
            ->setRequired(false)
            ->setHelp('关联的DigitalOcean配置')
            ->hideOnIndex()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('domainName'))
            ->add(TextFilter::new('type'))
            ->add(TextFilter::new('name'))
            ->add(TextFilter::new('data'))
            ->add(EntityFilter::new('config'))
        ;
    }
}
