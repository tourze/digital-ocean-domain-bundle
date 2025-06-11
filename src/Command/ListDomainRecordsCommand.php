<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 列出DigitalOcean域名记录命令
 */
#[AsCommand(
    name: 'digital-ocean:domain:record:list',
    description: '列出DigitalOcean域名记录',
)]
class ListDomainRecordsCommand extends Command
{
    public function __construct(
        private readonly DomainService $domainService,
        private readonly DomainRecordRepository $domainRecordRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::REQUIRED, '域名')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, '记录类型过滤 (如A, AAAA, CNAME等)')
            ->addOption('name', 'n', InputOption::VALUE_OPTIONAL, '记录名称过滤')
            ->addOption('remote', 'r', InputOption::VALUE_NONE, '使用远程API查询而不是本地数据库')
            ->addOption('page', 'p', InputOption::VALUE_OPTIONAL, '页码', 1)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, '每页记录数', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domain = $input->getArgument('domain');
        $type = $input->getOption('type');
        $name = $input->getOption('name');
        $useRemote = $input->getOption('remote');
        $page = (int)$input->getOption('page');
        $limit = (int)$input->getOption('limit');

        $io->title(sprintf('域名 "%s" 的DNS记录列表', $domain));

        try {
            if ($useRemote) {
                // 使用远程API获取记录
                $response = $this->domainService->listDomainRecords($domain, $page, $limit);
                $records = $response['domain_records'] ?? [];

                if (empty($records)) {
                    $io->info('没有找到任何记录');
                    return Command::SUCCESS;
                }

                // 应用过滤
                if ($type || $name) {
                    $filteredRecords = [];
                    foreach ($records as $record) {
                        if ($type && (!isset($record['type']) || strtoupper($record['type']) !== strtoupper($type))) {
                            continue;
                        }
                        if ($name && (!isset($record['name']) || strpos($record['name'], $name) === false)) {
                            continue;
                        }
                        $filteredRecords[] = $record;
                    }
                    $records = $filteredRecords;
                }

                if (empty($records)) {
                    $io->info('没有符合过滤条件的记录');
                    return Command::SUCCESS;
                }

                // 准备表格数据
                $rows = [];
                foreach ($records as $record) {
                    $row = [
                        $record['id'] ?? '',
                        $record['type'] ?? '',
                        $record['name'] ?? '',
                        $record['data'] ?? '',
                        $record['ttl'] ?? '',
                    ];
                    $rows[] = $row;
                }

                $io->table(
                    ['ID', '类型', '名称', '值', 'TTL'],
                    $rows
                );

                // 分页信息
                if (isset($response['meta']['total'])) {
                    $total = $response['meta']['total'];
                    $totalPages = ceil($total / $limit);
                    $io->info(sprintf('第 %d/%d 页，共 %d 条记录', $page, $totalPages, $total));
                }
            } else {
                // 从本地数据库获取记录
                $criteria = ['domainName' => $domain];
                
                if ($type) {
                    $criteria['type'] = $type;
                }
                
                if ($name) {
                    // 名称模糊查询需要使用自定义查询方法
                    $records = $this->domainRecordRepository->findByDomainAndName($domain, $name, $type);
                } else {
                    $records = $this->domainRecordRepository->findBy($criteria, ['recordId' => 'ASC'], $limit, ($page - 1) * $limit);
                }

                if (empty($records)) {
                    $io->info('没有找到任何记录');
                    return Command::SUCCESS;
                }

                // 准备表格数据
                $rows = [];
                foreach ($records as $record) {
                    $row = [
                        $record->getRecordId(),
                        $record->getType(),
                        $record->getName(),
                        $record->getData(),
                        $record->getTtl(),
                    ];
                    $rows[] = $row;
                }

                $io->table(
                    ['ID', '类型', '名称', '值', 'TTL'],
                    $rows
                );

                // 分页信息
                $total = $this->domainRecordRepository->count(['domainName' => $domain]);
                $totalPages = ceil($total / $limit);
                $io->info(sprintf('第 %d/%d 页，共 %d 条记录', $page, $totalPages, $total));
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('获取域名记录时发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
