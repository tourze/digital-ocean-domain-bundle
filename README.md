# DigitalOcean域名管理Bundle

这个Bundle提供了对DigitalOcean域名和DNS记录管理的支持。

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

## 文档

更多信息请参考[DigitalOcean API文档](https://docs.digitalocean.com/reference/api/api-reference/#tag/Domains)。
