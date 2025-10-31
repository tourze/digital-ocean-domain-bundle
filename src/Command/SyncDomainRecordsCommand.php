<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRepository;
use DigitalOceanDomainBundle\Service\DomainServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 同步DigitalOcean域名记录命令
 */
#[AsCommand(
    name: self::NAME,
    description: '同步DigitalOcean域名记录数据',
)]
#[Autoconfigure(public: true)]
class SyncDomainRecordsCommand extends Command
{
    public const NAME = 'digital-ocean:domain:sync-records';

    public function __construct(
        private readonly DomainServiceInterface $domainService,
        private readonly DomainRepository $domainRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::OPTIONAL, '要同步的域名（留空表示同步所有域名的记录）')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domainName = $input->getArgument('domain');

        if (null !== $domainName) {
            if (!is_string($domainName)) {
                throw new \InvalidArgumentException('Domain must be a string');
            }

            return $this->syncSingleDomain($io, $domainName);
        }

        return $this->syncAllDomains($io);
    }

    private function syncSingleDomain(SymfonyStyle $io, string $domainName): int
    {
        $io->title(sprintf('同步域名 "%s" 的记录', $domainName));

        try {
            $records = $this->domainService->syncDomainRecords($domainName);
            $this->displayRecords($io, $records);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('同步域名记录时发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function syncAllDomains(SymfonyStyle $io): int
    {
        $io->title('同步所有域名的记录');

        $domains = $this->domainRepository->findAll();

        if (0 === count($domains)) {
            $io->warning('没有找到任何域名，请先运行 digital-ocean:domain:sync 命令同步域名');

            return Command::FAILURE;
        }

        $result = $this->processDomains($io, $domains);
        $this->displaySyncResults($io, $result);

        return true === $result['hasErrors'] ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param array<mixed> $domains
     * @return array<string, mixed>
     */
    private function processDomains(SymfonyStyle $io, array $domains): array
    {
        $totalRecords = 0;
        $errors = [];

        foreach ($domains as $domain) {
            if (!is_object($domain) || !method_exists($domain, 'getName')) {
                continue;
            }
            $domainNameRaw = $domain->getName();
            if (!is_string($domainNameRaw)) {
                continue;
            }
            $domainName = $domainNameRaw;
            $io->section(sprintf('同步域名 "%s" 的记录', $domainName));

            try {
                $records = $this->domainService->syncDomainRecords($domainName);
                $count = count($records);
                $totalRecords += $count;

                if ($count > 0) {
                    $io->success(sprintf('成功同步 %d 条记录', $count));
                } else {
                    $io->info('没有找到任何记录');
                }
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();
                $errors[] = [
                    'domain' => $domainName,
                    'error' => $errorMessage,
                ];
                $io->error(sprintf('同步域名 "%s" 记录时发生错误: %s', $domainName, $errorMessage));
            }
        }

        return [
            'totalRecords' => $totalRecords,
            'errors' => $errors,
            'hasErrors' => count($errors) > 0,
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displaySyncResults(SymfonyStyle $io, array $result): void
    {
        $io->section('同步结果');

        $totalRecords = $this->getValidIntValue($result, 'totalRecords');
        $errors = $this->getValidArrayValue($result, 'errors');

        $this->displaySuccessMessage($io, $totalRecords);
        $this->displayErrorsIfAny($io, $errors);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function getValidIntValue(array $result, string $key): int
    {
        $value = $result[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<mixed>
     */
    private function getValidArrayValue(array $result, string $key): array
    {
        $value = $result[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    private function displaySuccessMessage(SymfonyStyle $io, int $totalRecords): void
    {
        if ($totalRecords > 0) {
            $io->success(sprintf('成功同步总计 %d 条记录', $totalRecords));
        } else {
            $io->info('没有找到任何记录');
        }
    }

    /**
     * @param array<mixed> $errors
     */
    private function displayErrorsIfAny(SymfonyStyle $io, array $errors): void
    {
        if (0 === count($errors)) {
            return;
        }

        $io->warning(sprintf('同步过程中发生 %d 个错误', count($errors)));
        $this->displayErrorTable($io, $errors);
    }

    /**
     * @param array<mixed> $errors
     */
    private function displayErrorTable(SymfonyStyle $io, array $errors): void
    {
        $errorRows = [];
        foreach ($errors as $error) {
            if (is_array($error) && isset($error['domain'], $error['error'])) {
                $errorRows[] = [$error['domain'], $error['error']];
            }
        }

        if (count($errorRows) > 0) {
            $io->table(['域名', '错误信息'], $errorRows);
        }
    }

    /**
     * 显示记录信息
     */
    /**
     * @param array<mixed> $records
     */
    private function displayRecords(SymfonyStyle $io, array $records): void
    {
        $count = count($records);

        if ($count > 0) {
            $io->success(sprintf('成功同步 %d 条记录', $count));

            $tableRows = [];
            foreach ($records as $record) {
                if (!$record instanceof DomainRecord) {
                    continue;
                }
                $tableRows[] = [
                    $record->getDomainName(),
                    $record->getRecordId(),
                    $record->getType(),
                    $record->getName(),
                    $record->getData(),
                ];
            }

            $io->table(
                ['域名', 'ID', '类型', '名称', '值'],
                $tableRows
            );
        } else {
            $io->info('没有找到任何记录');
        }
    }
}
