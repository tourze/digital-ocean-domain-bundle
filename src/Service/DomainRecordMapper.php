<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Service;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Exception\ConfigurationException;

/**
 * 域名记录数据映射器
 */
readonly class DomainRecordMapper
{
    /**
     * 从API响应数据更新域名记录实体
     *
     * @param array<string, mixed> $recordData
     */
    public function updateRecordFromData(DomainRecord $record, array $recordData, string $domainName): void
    {
        $this->setRecordBasicFields($record, $recordData, $domainName);
        $this->setRecordOptionalFields($record, $recordData);
    }

    /**
     * @param array<string, mixed> $recordData
     */
    private function setRecordBasicFields(DomainRecord $record, array $recordData, string $domainName): void
    {
        $record->setDomainName($domainName);

        $recordId = $recordData['id'] ?? 0;
        if (!is_numeric($recordId)) {
            throw new \InvalidArgumentException('Record ID must be numeric');
        }
        $record->setRecordId((int) $recordId);

        $this->setRecordFieldIfExists($record, $recordData, 'type', 'setType');
        $this->setRecordFieldIfExists($record, $recordData, 'name', 'setName');
        $this->setRecordFieldIfExists($record, $recordData, 'data', 'setData');
    }

    /**
     * @param array<string, mixed> $recordData
     */
    private function setRecordOptionalFields(DomainRecord $record, array $recordData): void
    {
        $this->setRecordFieldIfExists($record, $recordData, 'priority', 'setPriority');
        $this->setRecordFieldIfExists($record, $recordData, 'port', 'setPort');
        $this->setRecordFieldIfExists($record, $recordData, 'ttl', 'setTtl');
        $this->setRecordFieldIfExists($record, $recordData, 'weight', 'setWeight');
        $this->setRecordFieldIfExists($record, $recordData, 'flags', 'setFlags');
        $this->setRecordFieldIfExists($record, $recordData, 'tag', 'setTag');
    }

    /**
     * @param array<string, mixed> $recordData
     */
    private function setRecordFieldIfExists(DomainRecord $record, array $recordData, string $field, string $setter): void
    {
        if (!isset($recordData[$field])) {
            return;
        }

        $value = $recordData[$field];
        $this->applyRecordFieldSetter($record, $setter, $value);
    }

    private function applyRecordFieldSetter(DomainRecord $record, string $setter, mixed $value): void
    {
        match ($setter) {
            'setType' => $record->setType(is_string($value) ? $value : ''),
            'setName' => $record->setName(is_string($value) ? $value : ''),
            'setData' => $record->setData(is_string($value) ? $value : ''),
            'setPriority' => $record->setPriority(is_numeric($value) ? (int) $value : null),
            'setPort' => $record->setPort(is_numeric($value) ? (int) $value : null),
            'setTtl' => $record->setTtl(is_numeric($value) ? (int) $value : null),
            'setWeight' => $record->setWeight(is_numeric($value) ? (int) $value : null),
            'setFlags' => $record->setFlags(is_string($value) ? $value : null),
            'setTag' => $record->setTag(is_string($value) ? $value : null),
            default => throw new ConfigurationException("Unknown setter: {$setter}"),
        };
    }
}
