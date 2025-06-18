<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Repository\DomainRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 同步DigitalOcean域名记录命令
 */
#[AsCommand(
    name: 'digital-ocean:domain:sync-records',
    description: '同步DigitalOcean域名记录数据',
)]
class SyncDomainRecordsCommand extends Command
{
    protected const NAME = 'digital-ocean:domain:sync-records';

    public function __construct(
        private readonly DomainService $domainService,
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

        if ($domainName !== null) {
            $io->title(sprintf('同步域名 "%s" 的记录', $domainName));
            
            try {
                $records = $this->domainService->syncDomainRecords($domainName);
                $this->displayRecords($io, $records);
                return Command::SUCCESS;
            } catch (\Throwable $e) {
                $io->error('同步域名记录时发生错误: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->title('同步所有域名的记录');
            
            // 获取所有域名
            $domains = $this->domainRepository->findAll();
            
            if (empty($domains)) {
                $io->warning('没有找到任何域名，请先运行 digital-ocean:domain:sync 命令同步域名');
                return Command::FAILURE;
            }

            $totalRecords = 0;
            $errors = [];

            foreach ($domains as $domain) {
                $domainName = $domain->getName();
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
                    $errors[] = [
                        'domain' => $domainName,
                        'error' => $e->getMessage(),
                    ];
                    $io->error(sprintf('同步域名 "%s" 记录时发生错误: %s', $domainName, $e->getMessage()));
                }
            }

            $io->section('同步结果');
            
            if ($totalRecords > 0) {
                $io->success(sprintf('成功同步总计 %d 条记录', $totalRecords));
            } else {
                $io->info('没有找到任何记录');
            }
            
            if (!empty($errors)) {
                $io->warning(sprintf('同步过程中发生 %d 个错误', count($errors)));
                
                $io->table(
                    ['域名', '错误信息'],
                    array_map(function ($error) {
                        return [$error['domain'], $error['error']];
                    }, $errors)
                );
                
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        }
    }

    /**
     * 显示记录信息
     */
    private function displayRecords(SymfonyStyle $io, array $records): void
    {
        $count = count($records);
            
        if ($count > 0) {
            $io->success(sprintf('成功同步 %d 条记录', $count));
            
            $tableRows = array_map(function ($record) {
                return [
                    $record->getDomainName(),
                    $record->getRecordId(),
                    $record->getType(),
                    $record->getName(),
                    $record->getData(),
                ];
            }, $records);
            
            $io->table(
                ['域名', 'ID', '类型', '名称', '值'],
                $tableRows
            );
        } else {
            $io->info('没有找到任何记录');
        }
    }
}
