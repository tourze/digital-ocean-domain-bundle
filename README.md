# DigitalOcean Domain Management Bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

This bundle provides support for DigitalOcean domain and DNS record management.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Dependencies](#dependencies)
  - [Required Dependencies](#required-dependencies)
  - [Optional Dependencies](#optional-dependencies)
- [Usage](#usage)
  - [Domain Management](#domain-management)
  - [Domain Record Management](#domain-record-management)
  - [Data Synchronization](#data-synchronization)
- [Console Commands](#console-commands)
- [Bidirectional Sync](#bidirectional-sync)
- [Configuration](#configuration)
- [Advanced Usage](#advanced-usage)
  - [Error Handling](#error-handling)
  - [Batch Operations](#batch-operations)
  - [Custom Queries](#custom-queries)
- [Documentation](#documentation)
- [License](#license)

## Features

- Domain management: create, query, delete domains
- Domain record management: create, query, update, delete domain records
- Data synchronization: sync domains and records from DigitalOcean to local database
- Console commands: convenient command-line tools for domain and record management
- Bidirectional sync: support for two-way sync between local database and remote DigitalOcean

## Installation

```bash
composer require tourze/digital-ocean-domain-bundle
```

## Dependencies

### Required Dependencies

- PHP ^8.1
- Symfony ^7.3
- Doctrine ORM ^3.0
- tourze/digital-ocean-account-bundle ^0.1

### Optional Dependencies

- tourze/doctrine-indexed-bundle - for database index support
- tourze/doctrine-timestamp-bundle - for timestamp field support
- tourze/symfony-aop-doctrine-bundle - for AOP support

## Usage

### Domain Management

```php
// Get domain list
$domains = $domainService->listDomains();

// Get single domain
$domain = $domainService->getDomain('example.com');

// Create domain
$domain = $domainService->createDomain('example.com', '123.456.789.10');

// Delete domain
$result = $domainService->deleteDomain('example.com');
```

### Domain Record Management

```php
// Get domain record list
$records = $domainService->listDomainRecords('example.com');

// Get single domain record
$record = $domainService->getDomainRecord('example.com', 12345);

// Create domain record
$record = $domainService->createDomainRecord(
    'example.com',
    'A',
    'www',
    '123.456.789.10'
);

// Update domain record
$record = $domainService->updateDomainRecord(
    'example.com',
    12345,
    'A',
    'www',
    '123.456.789.11'
);

// Delete domain record
$result = $domainService->deleteDomainRecord('example.com', 12345);
```

### Data Synchronization

```php
// Sync all domains
$domains = $domainService->syncDomains();

// Sync records for specific domain
$records = $domainService->syncDomainRecords('example.com');
```

## Console Commands

This bundle provides several console commands for managing domains and records:

```bash
# Sync all domains
php bin/console digital-ocean:domain:sync

# Sync records for single domain
php bin/console digital-ocean:domain:sync-records example.com

# Sync all domain records
php bin/console digital-ocean:domain:sync-records

# List domain records (query local database)
php bin/console digital-ocean:domain:record:list example.com

# List domain records (query remote API directly)
php bin/console digital-ocean:domain:record:list example.com --remote

# Filter records by type
php bin/console digital-ocean:domain:record:list example.com --type=A

# Search records by name
php bin/console digital-ocean:domain:record:list example.com --name=www

# Create domain record
php bin/console digital-ocean:domain:record:create example.com A www 123.456.789.10

# Create MX record
php bin/console digital-ocean:domain:record:create example.com MX mail 123.456.789.10 --priority=10

# Update domain record
php bin/console digital-ocean:domain:record:update example.com 12345 --data=123.456.789.11

# Update remote record with local data
php bin/console digital-ocean:domain:record:update example.com 12345 --local

# Delete domain record
php bin/console digital-ocean:domain:record:delete example.com 12345
```

## Bidirectional Sync

This bundle supports bidirectional synchronization between local database and remote DigitalOcean:

1. From remote to local: use `digital-ocean:domain:sync-records` command or `syncDomainRecords` method
2. From local to remote: use `digital-ocean:domain:record:update` command with `--local` flag

This allows you to manage DNS records locally and then push changes to DigitalOcean, or pull the latest changes from DigitalOcean to your local database.

## Configuration

This bundle depends on the configuration service provided by `digital-ocean-account-bundle`. Make sure the API token is properly configured.

## Advanced Usage

### Error Handling

```php
try {
    $record = $domainService->createDomainRecord(
        'example.com',
        'A',
        'www',
        '123.456.789.10'
    );
} catch (\DigitalOceanDomainBundle\Exception\ConfigurationException $e) {
    // Handle configuration errors
    $logger->error('DigitalOcean configuration error: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle other errors
    $logger->error('Failed to create domain record: ' . $e->getMessage());
}
```

### Batch Operations

```php
// Batch sync multiple domain records
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

### Custom Queries

```php
// Use Repository for custom queries
$domainRepository = $em->getRepository(Domain::class);
$domainRecordRepository = $em->getRepository(DomainRecord::class);

// Find domains created within specific time range
$domains = $domainRepository->createQueryBuilder('d')
    ->where('d.createTime BETWEEN :start AND :end')
    ->setParameter('start', new \DateTime('2023-01-01'))
    ->setParameter('end', new \DateTime('2023-12-31'))
    ->getQuery()
    ->getResult();

// Count records by type
$recordCounts = $domainRecordRepository->createQueryBuilder('dr')
    ->select('dr.type, COUNT(dr.id) as count')
    ->groupBy('dr.type')
    ->getQuery()
    ->getResult();
```

## Documentation

For more information, please refer to the [DigitalOcean API Documentation](https://docs.digitalocean.com/reference/api/api-reference/#tag/Domains).

## License

MIT