<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Command\Traits\UpdateDomainRecordSupportTrait;
use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Service\DomainServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 更新DigitalOcean域名记录命令
 */
#[AsCommand(
    name: self::NAME,
    description: '更新DigitalOcean域名记录',
)]
#[Autoconfigure(public: true)]
class UpdateDomainRecordCommand extends Command
{
    use UpdateDomainRecordSupportTrait;
    public const NAME = 'digital-ocean:domain:record:update';

    public function __construct(
        private readonly DomainServiceInterface $domainService,
        private readonly DomainRecordRepository $domainRecordRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::REQUIRED, '域名')
            ->addArgument('record_id', InputArgument::REQUIRED, '记录ID')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, '记录类型 (A, AAAA, CNAME, MX, TXT, NS, SRV, CAA等)')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, '记录名称 (@表示根域名)')
            ->addOption('data', 'd', InputOption::VALUE_OPTIONAL, '记录值 (如IP地址)')
            ->addOption('priority', null, InputOption::VALUE_OPTIONAL, 'MX或SRV记录的优先级')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'SRV记录的端口')
            ->addOption('ttl', null, InputOption::VALUE_OPTIONAL, 'TTL值')
            ->addOption('weight', null, InputOption::VALUE_OPTIONAL, 'SRV记录的权重')
            ->addOption('flags', null, InputOption::VALUE_OPTIONAL, 'CAA记录的标志')
            ->addOption('tag', null, InputOption::VALUE_OPTIONAL, 'CAA记录的标签')
            ->addOption('local', 'l', InputOption::VALUE_NONE, '从本地记录获取数据进行更新')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $domain = $input->getArgument('domain');
        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Domain must be a string');
        }

        $recordIdValue = $input->getArgument('record_id');
        if (!is_numeric($recordIdValue)) {
            throw new \InvalidArgumentException('Record ID must be numeric');
        }
        $recordId = (int) $recordIdValue;

        $useLocalData = $input->getOption('local');

        try {
            return true === $useLocalData
                ? $this->updateWithLocalData($io, $domain, $recordId)
                : $this->updateWithRemoteData($io, $input, $domain, $recordId);
        } catch (\Throwable $e) {
            $io->error('更新域名记录时发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function updateWithLocalData(SymfonyStyle $io, string $domain, int $recordId): int
    {
        $localRecord = $this->findLocalRecord($domain, $recordId);
        if (null === $localRecord) {
            $io->error(sprintf('找不到本地记录, 域名: %s, 记录ID: %d', $domain, $recordId));

            return Command::FAILURE;
        }

        return $this->updateWithLocalRecordData($io, $domain, $recordId, $localRecord);
    }

    private function findLocalRecord(string $domain, int $recordId): ?DomainRecord
    {
        return $this->domainRecordRepository->findOneBy([
            'domainName' => $domain,
            'recordId' => $recordId,
        ]);
    }

    private function updateWithLocalRecordData(SymfonyStyle $io, string $domain, int $recordId, DomainRecord $localRecord): int
    {
        $record = $this->domainService->updateDomainRecord(
            $domain,
            $recordId,
            $localRecord->getType(),
            $localRecord->getName(),
            $localRecord->getData(),
            $localRecord->getPriority(),
            $localRecord->getPort(),
            $localRecord->getTtl(),
            $localRecord->getWeight(),
            $localRecord->getFlags(),
            $localRecord->getTag()
        );

        return $this->handleUpdateResult($io, $record, $localRecord->getName(), $domain, $localRecord->getType(), false);
    }

    private function updateWithRemoteData(SymfonyStyle $io, InputInterface $input, string $domain, int $recordId): int
    {
        $currentRecord = $this->fetchCurrentRecord($domain, $recordId);
        if (null === $currentRecord) {
            $io->error(sprintf('找不到远程记录, 域名: %s, 记录ID: %d', $domain, $recordId));

            return Command::FAILURE;
        }

        $updateData = $this->prepareUpdateData($input, $currentRecord);
        $this->displayUpdatePreview($io, $currentRecord, $updateData);

        if (!$this->confirmUpdate($io)) {
            return Command::SUCCESS;
        }

        return $this->executeUpdate($io, $domain, $recordId, $updateData);
    }

    /**
     * @param array<string, mixed> $currentRecord
     * @return array<string, mixed>
     */
    private function prepareUpdateData(InputInterface $input, array $currentRecord): array
    {
        return [
            'type' => $this->extractStringValue($input, 'type', $currentRecord),
            'name' => $this->extractStringValue($input, 'name', $currentRecord),
            'data' => $this->extractStringValue($input, 'data', $currentRecord),
            'priority' => $this->getIntOptionWithFallback($input, 'priority', $this->extractIntValue($currentRecord, 'priority')),
            'port' => $this->getIntOptionWithFallback($input, 'port', $this->extractIntValue($currentRecord, 'port')),
            'ttl' => $this->getIntOptionWithFallback($input, 'ttl', $this->extractIntValue($currentRecord, 'ttl')),
            'weight' => $this->getIntOptionWithFallback($input, 'weight', $this->extractIntValue($currentRecord, 'weight')),
            'flags' => $this->getStringOptionWithFallback($input, 'flags', $this->extractStringValueFromRecord($currentRecord, 'flags')),
            'tag' => $this->getStringOptionWithFallback($input, 'tag', $this->extractStringValueFromRecord($currentRecord, 'tag')),
        ];
    }

    /**
     * @param array<string, mixed> $currentRecord
     */
    private function extractStringValue(InputInterface $input, string $key, array $currentRecord): string
    {
        $value = $input->getOption($key) ?? $currentRecord[$key] ?? '';
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('%s must be a string', ucfirst($key)));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function extractIntValue(array $record, string $key): ?int
    {
        return isset($record[$key]) && is_numeric($record[$key]) ? (int) $record[$key] : null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function extractStringValueFromRecord(array $record, string $key): ?string
    {
        return isset($record[$key]) && is_string($record[$key]) ? $record[$key] : null;
    }

    private function getIntOptionWithFallback(InputInterface $input, string $name, ?int $fallback): ?int
    {
        $value = $input->getOption($name);

        if (null === $value) {
            return $fallback;
        }

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" must be numeric', $name));
        }

        return (int) $value;
    }

    private function getStringOptionWithFallback(InputInterface $input, string $name, ?string $fallback): ?string
    {
        $value = $input->getOption($name);

        if (null === $value) {
            return $fallback;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" must be a string', $name));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function handleUpdateResult(SymfonyStyle $io, array $record, string $name, string $domain, string $type, bool $syncToLocal): int
    {
        if (0 === count($record)) {
            $io->error('更新域名记录失败');

            return Command::FAILURE;
        }

        $io->success(sprintf('成功更新域名记录: %s.%s (%s)', $name, $domain, $type));

        $rows = $this->buildTableRows($record);
        if (count($rows) > 0) {
            $io->table(['属性', '值'], $rows);
        }

        if ($syncToLocal) {
            $this->syncToLocalDatabase($io, $domain);
        }

        return Command::SUCCESS;
    }

    private function syncToLocalDatabase(SymfonyStyle $io, string $domain): void
    {
        $io->section('同步到本地数据库');
        $this->domainService->syncDomainRecords($domain);
        $io->success('成功同步记录到本地数据库');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchCurrentRecord(string $domain, int $recordId): ?array
    {
        $currentRecord = $this->domainService->getDomainRecord($domain, $recordId);

        return 0 === count($currentRecord) ? null : $currentRecord;
    }

    private function confirmUpdate(SymfonyStyle $io): bool
    {
        if (!$io->confirm('确定要更新这条记录吗?', false)) {
            $io->warning('操作已取消');

            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function executeUpdate(SymfonyStyle $io, string $domain, int $recordId, array $updateData): int
    {
        $validatedData = $this->validateUpdateData($updateData);
        $this->performRuntimeTypeValidation($validatedData);

        // Extract validated types for safe casting
        $type = $validatedData['type'];
        $name = $validatedData['name'];
        $data = $validatedData['data'];
        $priority = $validatedData['priority'];
        $port = $validatedData['port'];
        $ttl = $validatedData['ttl'];
        $weight = $validatedData['weight'];
        $flags = $validatedData['flags'];
        $tag = $validatedData['tag'];

        if (!is_string($type) || !is_string($name) || !is_string($data)) {
            throw new \RuntimeException('Type, name, and data must be strings');
        }

        $record = $this->domainService->updateDomainRecord(
            $domain,
            $recordId,
            $type,
            $name,
            $data,
            is_int($priority) ? $priority : null,
            is_int($port) ? $port : null,
            is_int($ttl) ? $ttl : null,
            is_int($weight) ? $weight : null,
            is_string($flags) ? $flags : null,
            is_string($tag) ? $tag : null
        );

        return $this->handleUpdateResult($io, $record, $name, $domain, $type, true);
    }
}
