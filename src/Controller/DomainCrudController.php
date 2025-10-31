<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Controller;

use DigitalOceanDomainBundle\Entity\Domain;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

#[AdminCrud(
    routePath: '/digital-ocean/domain',
    routeName: 'digital_ocean_domain'
)]
final class DomainCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Domain::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('DigitalOcean域名')
            ->setEntityLabelInPlural('DigitalOcean域名管理')
            ->setPageTitle(Crud::PAGE_INDEX, 'DigitalOcean域名列表')
            ->setPageTitle(Crud::PAGE_NEW, '新建DigitalOcean域名')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑DigitalOcean域名')
            ->setPageTitle(Crud::PAGE_DETAIL, 'DigitalOcean域名详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name'])
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

        yield TextField::new('name', '域名')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('域名，如 example.com')
        ;

        yield TextareaField::new('ttl', 'TTL')
            ->setColumns('col-md-6')
            ->setRequired(false)
            ->setMaxLength(65535)
            ->setHelp('域名的TTL设置')
            ->hideOnIndex()
        ;

        yield TextareaField::new('zoneFile', 'Zone文件')
            ->setColumns('col-md-12')
            ->setRequired(false)
            ->setMaxLength(65535)
            ->setHelp('域名的Zone文件内容')
            ->hideOnIndex()
            ->setNumOfRows(10)
        ;

        yield DateTimeField::new('createdAt', '创建时间')
            ->onlyOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updatedAt', '更新时间')
            ->onlyOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
        ;
    }
}
