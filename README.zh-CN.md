# DigitalOcean域名管理Bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

本Bundle提供了对DigitalOcean域名和DNS记录管理的支持。

## 目录

- [功能](#功能)
- [安装](#安装)
- [Dependencies](#dependencies)
  - [必需依赖](#必需依赖)
  - [可选依赖](#可选依赖)
- [使用](#使用)
  - [域名管理](#域名管理)
  - [域名记录管理](#域名记录管理)
  - [数据同步](#数据同步)
- [命令行工具](#命令行工具)
- [双向同步](#双向同步)
- [配置](#配置)
- [Advanced Usage](#advanced-usage)
  - [错误处理](#错误处理)
  - [批量操作](#批量操作)
  - [自定义查询](#自定义查询)
- [文档](#文档)
- [License](#license)

## 功能

- 域名管理：创建、查询、删除域名
- 域名记录管理：创建、查询、更新、删除域名记录
- 数据同步：将DigitalOcean中的域名和记录同步到本地数据库
- 命令行工具：提供方便的命令行工具进行域名和记录管理
- 双向同步：支持本地数据库和远程DigitalOcean之间的双向同步

## 安装

```bash
composer require tourze/digital-ocean-domain-bundle
```

## Dependencies

### 必需依赖

- PHP ^8.1
- Symfony ^7.3
- Doctrine ORM ^3.0
- tourze/digital-ocean-account-bundle ^0.1

### 可选依赖

- tourze/doctrine-indexed-bundle - 用于数据库索引支持
- tourze/doctrine-timestamp-bundle - 用于时间戳字段支持
- tourze/symfony-aop-doctrine-bundle - 用于AOP支持

## 使用

### 域名管理

```php
// 获取域名列表
$domains = $domainService->listDomains();

// 获取单个域名
$domain = $domainService->getDomain('example.com');

// 创建域名
$domain = $domainService->createDomain('example.com', '123.456.789.10');

// 删除域名
$result = $domainService->deleteDomain('example.com');
```

### 域名记录管理

```php
// 获取域名记录列表
$records = $domainService->listDomainRecords('example.com');

// 获取单个域名记录
$record = $domainService->getDomainRecord('example.com', 12345);

// 创建域名记录
$record = $domainService->createDomainRecord(
    'example.com',
    'A',
    'www',
    '123.456.789.10'
);

// 更新域名记录
$record = $domainService->updateDomainRecord(
    'example.com',
    12345,
    'A',
    'www',
    '123.456.789.11'
);

// 删除域名记录
$result = $domainService->deleteDomainRecord('example.com', 12345);
```

### 数据同步

```php
// 同步所有域名
$domains = $domainService->syncDomains();

// 同步指定域名的记录
$records = $domainService->syncDomainRecords('example.com');
```

## 命令行工具

此Bundle提供了多个命令行工具用于管理域名和记录：

```bash
# 同步所有域名
php bin/console digital-ocean:domain:sync

# 同步单个域名的记录
php bin/console digital-ocean:domain:sync-records example.com

# 同步所有域名的记录
php bin/console digital-ocean:domain:sync-records

# 列出域名下的记录（查询本地数据库）
php bin/console digital-ocean:domain:record:list example.com

# 列出域名下的记录（直接查询远程API）
php bin/console digital-ocean:domain:record:list example.com --remote

# 按类型过滤记录
php bin/console digital-ocean:domain:record:list example.com --type=A

# 按名称模糊查询记录
php bin/console digital-ocean:domain:record:list example.com --name=www

# 创建域名记录
php bin/console digital-ocean:domain:record:create example.com A www 123.456.789.10

# 创建MX记录
php bin/console digital-ocean:domain:record:create example.com MX mail 123.456.789.10 --priority=10

# 更新域名记录
php bin/console digital-ocean:domain:record:update example.com 12345 --data=123.456.789.11

# 使用本地数据更新远程记录
php bin/console digital-ocean:domain:record:update example.com 12345 --local

# 删除域名记录
php bin/console digital-ocean:domain:record:delete example.com 12345
```

## 双向同步

本Bundle支持本地数据库和远程DigitalOcean之间的双向同步：

1. 从远程同步到本地：使用`digital-ocean:domain:sync-records`命令或`syncDomainRecords`方法
2. 从本地同步到远程：使用带`--local`参数的`digital-ocean:domain:record:update`命令

这使您可以在本地管理DNS记录，然后将更改推送到DigitalOcean，也可以拉取DigitalOcean上的最新更改到本地数据库。

## 配置

此Bundle依赖于`digital-ocean-account-bundle`提供的配置服务，需确保已正确配置API令牌。

## Advanced Usage

### 错误处理

```php
try {
    $record = $domainService->createDomainRecord(
        'example.com',
        'A',
        'www',
        '123.456.789.10'
    );
} catch (\DigitalOceanDomainBundle\Exception\ConfigurationException $e) {
    // 处理配置错误
    $logger->error('DigitalOcean配置错误：' . $e->getMessage());
} catch (\Exception $e) {
    // 处理其他错误
    $logger->error('创建域名记录失败：' . $e->getMessage());
}
```

### 批量操作

```php
// 批量同步多个域名的记录
$domains = ['example.com', 'test.com', 'demo.com'];
$results = [];

foreach ($domains as $domain) {
    try {
        $records = $domainService->syncDomainRecords($domain);
        $results[$domain] = ['success' => true, 'count' => count($records)];
    } catch (\Exception $e) {
        $results[$domain] = ['success' => false, 'error' => $e->getMessage()];
    }
}
```

### 自定义查询

```php
// 使用Repository进行自定义查询
$domainRepository = $em->getRepository(Domain::class);
$domainRecordRepository = $em->getRepository(DomainRecord::class);

// 查找特定时间段内创建的域名
$domains = $domainRepository->createQueryBuilder('d')
    ->where('d.createTime BETWEEN :start AND :end')
    ->setParameter('start', new \DateTime('2023-01-01'))
    ->setParameter('end', new \DateTime('2023-12-31'))
    ->getQuery()
    ->getResult();

// 按类型统计记录数量
$recordCounts = $domainRecordRepository->createQueryBuilder('dr')
    ->select('dr.type, COUNT(dr.id) as count')
    ->groupBy('dr.type')
    ->getQuery()
    ->getResult();
```

## 文档

更多信息请参考[DigitalOcean API文档](https://docs.digitalocean.com/reference/api/api-reference/#tag/Domains)。

## License

MIT
