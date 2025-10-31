<?php

declare(strict_types=1);

namespace DigitalOceanDomainBundle\Command\Traits;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 将 UpdateDomainRecordCommand 的展示与校验相关方法抽取到 trait，降低类复杂度。
 */
trait UpdateDomainRecordSupportTrait
{
    /**
     * @param array<string, mixed> $currentRecord
     * @param array<string, mixed> $updateData
     */
    private function displayUpdatePreview(SymfonyStyle $io, array $currentRecord, array $updateData): void
    {
        $this->displayCurrentRecordInfo($io, $currentRecord);
        $this->displayUpdateInfo($io, $updateData);
    }

    /**
     * @param array<string, mixed> $currentRecord
     */
    private function displayCurrentRecordInfo(SymfonyStyle $io, array $currentRecord): void
    {
        $io->section('当前记录信息');
        $io->table(['属性', '值'], $this->buildTableRows($currentRecord));
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function displayUpdateInfo(SymfonyStyle $io, array $updateData): void
    {
        $io->section('将更新为');
        $io->table(['属性', '值'], $this->buildUpdateTableRows($updateData));
    }

    /**
     * @param array<string, mixed> $record
     * @return list<array<int, bool|float|int|string>>
     */
    private function buildTableRows(array $record): array
    {
        $rows = [];
        foreach ($record as $key => $value) {
            if (is_scalar($value)) {
                $rows[] = [$key, $value];
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $updateData
     * @return list<array<int, mixed>>
     */
    private function buildUpdateTableRows(array $updateData): array
    {
        $rows = [
            ['type', $updateData['type']],
            ['name', $updateData['name']],
            ['data', $updateData['data']],
        ];

        foreach (['priority', 'port', 'ttl', 'weight', 'flags', 'tag'] as $field) {
            if (null !== $updateData[$field]) {
                $rows[] = [$field, $updateData[$field]];
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $validatedData
     */
    private function performRuntimeTypeValidation(array $validatedData): void
    {
        $this->validateRequiredStringTypes($validatedData);
        $this->validateOptionalIntegerTypes($validatedData);
        $this->validateOptionalStringTypes($validatedData);
    }

    /**
     * @param array<string, mixed> $validatedData
     */
    private function validateRequiredStringTypes(array $validatedData): void
    {
        $requiredStrings = ['type', 'name', 'data'];
        foreach ($requiredStrings as $field) {
            if (!is_string($validatedData[$field])) {
                throw new \InvalidArgumentException(sprintf('%s must be a string', ucfirst($field)));
            }
        }
    }

    /**
     * @param array<string, mixed> $validatedData
     */
    private function validateOptionalIntegerTypes(array $validatedData): void
    {
        $optionalIntegers = ['priority', 'port', 'ttl', 'weight'];
        foreach ($optionalIntegers as $field) {
            $value = $validatedData[$field];
            if (null !== $value && !is_int($value)) {
                throw new \InvalidArgumentException(sprintf('%s must be an integer or null', ucfirst($field)));
            }
        }
    }

    /**
     * @param array<string, mixed> $validatedData
     */
    private function validateOptionalStringTypes(array $validatedData): void
    {
        $optionalStrings = ['flags', 'tag'];
        foreach ($optionalStrings as $field) {
            $value = $validatedData[$field];
            if (null !== $value && !is_string($value)) {
                throw new \InvalidArgumentException(sprintf('%s must be a string or null', ucfirst($field)));
            }
        }
    }

    /**
     * @param array<string, mixed> $updateData
     * @return array<string, mixed>
     */
    private function validateUpdateData(array $updateData): array
    {
        $this->validateStringFields($updateData);
        $this->validateIntegerFields($updateData);
        $this->validateOptionalStringFields($updateData);

        return $updateData;
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function validateStringFields(array $updateData): void
    {
        $this->validateFieldsByType($updateData, ['type', 'name', 'data'], 'string', false);
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function validateIntegerFields(array $updateData): void
    {
        $this->validateFieldsByType($updateData, ['priority', 'port', 'ttl', 'weight'], 'int', true);
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function validateOptionalStringFields(array $updateData): void
    {
        $this->validateFieldsByType($updateData, ['flags', 'tag'], 'string', true);
    }

    /**
     * @param array<string, mixed> $updateData
     * @param list<string> $fields
     * @param string $expectedType
     * @param bool $allowNull
     */
    private function validateFieldsByType(array $updateData, array $fields, string $expectedType, bool $allowNull): void
    {
        foreach ($fields as $field) {
            $value = $updateData[$field];

            if ($allowNull && null === $value) {
                continue;
            }

            $isValid = match ($expectedType) {
                'string' => is_string($value),
                'int' => is_int($value),
                default => false,
            };

            if (!$isValid) {
                $nullText = $allowNull ? ' or null' : '';
                throw new \InvalidArgumentException(sprintf('%s must be a %s%s', ucfirst($field), $expectedType, $nullText));
            }
        }
    }
}
