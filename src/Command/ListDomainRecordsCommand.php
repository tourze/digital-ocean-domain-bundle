<?php

namespace DigitalOceanDomainBundle\Command;

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
 * 列出DigitalOcean域名记录命令
 */
#[AsCommand(
    name: self::NAME,
    description: '列出DigitalOcean域名记录',
)]
#[Autoconfigure(public: true)]
class ListDomainRecordsCommand extends Command
{
    public const NAME = 'digital-ocean:domain:record:list';

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
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, '记录类型过滤 (如A, AAAA, CNAME等)')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, '记录名称过滤')
            ->addOption('remote', 'r', InputOption::VALUE_NONE, '使用远程API查询而不是本地数据库')
            ->addOption('page', 'p', InputOption::VALUE_OPTIONAL, '页码', 1)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, '每页记录数', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $domain = $input->getArgument('domain');
        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Domain must be a string');
        }

        $useRemote = $input->getOption('remote');

        $io->title(sprintf('域名 "%s" 的DNS记录列表', $domain));

        try {
            return true === $useRemote
                ? $this->listRemoteRecords($io, $input, $domain)
                : $this->listLocalRecords($io, $input, $domain);
        } catch (\Throwable $e) {
            $io->error('获取域名记录时发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function listRemoteRecords(SymfonyStyle $io, InputInterface $input, string $domain): int
    {
        $pageValue = $input->getOption('page');
        $limitValue = $input->getOption('limit');

        if (!is_numeric($pageValue)) {
            throw new \InvalidArgumentException('Page must be numeric');
        }
        if (!is_numeric($limitValue)) {
            throw new \InvalidArgumentException('Limit must be numeric');
        }

        $page = (int) $pageValue;
        $limit = (int) $limitValue;

        $response = $this->domainService->listDomainRecords($domain, $page, $limit);
        $records = $response['domain_records'] ?? [];

        if (!is_array($records) || 0 === count($records)) {
            $io->info('没有找到任何记录');

            return Command::SUCCESS;
        }

        // Ensure records have proper typing for applyFilters method
        $typedRecords = [];
        foreach ($records as $record) {
            if (is_array($record)) {
                $typedRecord = [];
                foreach ($record as $key => $value) {
                    $typedRecord[(string) $key] = $value;
                }
                $typedRecords[] = $typedRecord;
            }
        }

        $filteredRecords = $this->applyFilters($typedRecords, $input);
        if (0 === count($filteredRecords)) {
            $io->info('没有符合过滤条件的记录');

            return Command::SUCCESS;
        }

        $this->displayRemoteRecords($io, $filteredRecords, $response, $page, $limit);

        return Command::SUCCESS;
    }

    private function listLocalRecords(SymfonyStyle $io, InputInterface $input, string $domain): int
    {
        $pageValue = $input->getOption('page');
        $limitValue = $input->getOption('limit');

        if (!is_numeric($pageValue)) {
            throw new \InvalidArgumentException('Page must be numeric');
        }
        if (!is_numeric($limitValue)) {
            throw new \InvalidArgumentException('Limit must be numeric');
        }

        $page = (int) $pageValue;
        $limit = (int) $limitValue;

        $type = $input->getOption('type');
        if (null !== $type && !is_string($type)) {
            throw new \InvalidArgumentException('Type must be a string');
        }

        $name = $input->getOption('name');
        if (null !== $name && !is_string($name)) {
            throw new \InvalidArgumentException('Name must be a string');
        }

        $records = $this->fetchLocalRecords($domain, $type, $name, $page, $limit);

        if (0 === count($records)) {
            $io->info('没有找到任何记录');

            return Command::SUCCESS;
        }

        $this->displayLocalRecords($io, $records, $domain, $page, $limit);

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array<int, array<string, mixed>>
     */
    private function applyFilters(array $records, InputInterface $input): array
    {
        $type = $this->getValidStringOption($input, 'type');
        $name = $this->getValidStringOption($input, 'name');

        if (null === $type && null === $name) {
            return $records;
        }

        return array_filter($records, fn (array $record): bool => $this->matchesFilters($record, $type, $name));
    }

    private function getValidStringOption(InputInterface $input, string $optionName): ?string
    {
        $value = $input->getOption($optionName);
        if (null !== $value && !is_string($value)) {
            throw new \InvalidArgumentException(ucfirst($optionName) . ' must be a string');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function matchesFilters(array $record, ?string $type, ?string $name): bool
    {
        if (null !== $type && !$this->matchesType($record, $type)) {
            return false;
        }

        if (null !== $name && !$this->matchesName($record, $name)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function matchesType(array $record, string $type): bool
    {
        return isset($record['type']) && is_string($record['type']) && strtoupper($record['type']) === strtoupper($type);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function matchesName(array $record, string $name): bool
    {
        return isset($record['name']) && is_string($record['name']) && false !== strpos($record['name'], $name);
    }

    /**
     * @return array<mixed>
     */
    private function fetchLocalRecords(string $domain, ?string $type, ?string $name, int $page, int $limit): array
    {
        if (null !== $name) {
            return $this->domainRecordRepository->findByDomainAndName($domain, $name, $type);
        }

        $criteria = ['domainName' => $domain];
        if (null !== $type) {
            $criteria['type'] = $type;
        }

        return $this->domainRecordRepository->findBy($criteria, ['recordId' => 'ASC'], $limit, ($page - 1) * $limit);
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @param array<string, mixed> $response
     */
    private function displayRemoteRecords(SymfonyStyle $io, array $records, array $response, int $page, int $limit): void
    {
        $rows = array_map(fn (array $record): array => [
            $record['id'] ?? '',
            $record['type'] ?? '',
            $record['name'] ?? '',
            $record['data'] ?? '',
            $record['ttl'] ?? '',
        ], $records);

        $io->table(['ID', '类型', '名称', '值', 'TTL'], $rows);

        if (isset($response['meta']) && is_array($response['meta']) && isset($response['meta']['total']) && is_numeric($response['meta']['total'])) {
            $total = (int) $response['meta']['total'];
            $totalPages = ceil($total / $limit);
            $io->info(sprintf('第 %d/%d 页，共 %d 条记录', $page, $totalPages, $total));
        }
    }

    /**
     * @param array<mixed> $records
     */
    private function displayLocalRecords(SymfonyStyle $io, array $records, string $domain, int $page, int $limit): void
    {
        $rows = [];
        foreach ($records as $record) {
            if (!$record instanceof DomainRecord) {
                continue;
            }
            $rows[] = [
                $record->getRecordId(),
                $record->getType(),
                $record->getName(),
                $record->getData(),
                $record->getTtl(),
            ];
        }

        $io->table(['ID', '类型', '名称', '值', 'TTL'], $rows);

        $total = $this->domainRecordRepository->count(['domainName' => $domain]);
        $totalPages = ceil($total / $limit);
        $io->info(sprintf('第 %d/%d 页，共 %d 条记录', $page, $totalPages, $total));
    }
}
