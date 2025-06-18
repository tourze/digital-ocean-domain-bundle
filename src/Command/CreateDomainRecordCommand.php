<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Service\DomainService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 添加DigitalOcean域名记录命令
 */
#[AsCommand(
    name: 'digital-ocean:domain:record:create',
    description: '添加DigitalOcean域名记录',
)]
class CreateDomainRecordCommand extends Command
{
    protected const NAME = 'digital-ocean:domain:record:create';

    public function __construct(
        private readonly DomainService $domainService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::REQUIRED, '域名')
            ->addArgument('type', InputArgument::REQUIRED, '记录类型 (A, AAAA, CNAME, MX, TXT, NS, SRV, CAA等)')
            ->addArgument('name', InputArgument::REQUIRED, '记录名称 (@表示根域名)')
            ->addArgument('data', InputArgument::REQUIRED, '记录值 (如IP地址)')
            ->addOption('priority', null, InputOption::VALUE_OPTIONAL, 'MX或SRV记录的优先级')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'SRV记录的端口')
            ->addOption('ttl', null, InputOption::VALUE_OPTIONAL, 'TTL值')
            ->addOption('weight', null, InputOption::VALUE_OPTIONAL, 'SRV记录的权重')
            ->addOption('flags', null, InputOption::VALUE_OPTIONAL, 'CAA记录的标志')
            ->addOption('tag', null, InputOption::VALUE_OPTIONAL, 'CAA记录的标签')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domain = $input->getArgument('domain');
        $type = $input->getArgument('type');
        $name = $input->getArgument('name');
        $data = $input->getArgument('data');
        
        $priority = $input->getOption('priority') !== null ? (int)$input->getOption('priority') : null;
        $port = $input->getOption('port') !== null ? (int)$input->getOption('port') : null;
        $ttl = $input->getOption('ttl') !== null ? (int)$input->getOption('ttl') : null;
        $weight = $input->getOption('weight') !== null ? (int)$input->getOption('weight') : null;
        $flags = $input->getOption('flags');
        $tag = $input->getOption('tag');

        try {
            $record = $this->domainService->createDomainRecord(
                $domain,
                $type,
                $name,
                $data,
                $priority,
                $port,
                $ttl,
                $weight,
                $flags,
                $tag
            );
            
            if (!empty($record)) {
                $io->success(sprintf('成功添加域名记录: %s.%s (%s)', $name, $domain, $type));
                
                $rows = [];
                foreach ($record as $key => $value) {
                    if (is_scalar($value)) {
                        $rows[] = [$key, $value];
                    }
                }
                
                if (!empty($rows)) {
                    $io->table(['属性', '值'], $rows);
                }
                
                // 同步新记录到本地数据库
                $io->section('同步到本地数据库');
                $this->domainService->syncDomainRecords($domain);
                $io->success('成功同步记录到本地数据库');
                
                return Command::SUCCESS;
            } else {
                $io->error('添加域名记录失败');
                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            $io->error('添加域名记录时发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
