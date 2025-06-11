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
 * 更新DigitalOcean域名记录命令
 */
#[AsCommand(
    name: 'digital-ocean:domain:record:update',
    description: '更新DigitalOcean域名记录',
)]
class UpdateDomainRecordCommand extends Command
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
            ->addArgument('record_id', InputArgument::REQUIRED, '记录ID')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, '记录类型 (A, AAAA, CNAME, MX, TXT, NS, SRV, CAA等)')
            ->addOption('name', 'n', InputOption::VALUE_OPTIONAL, '记录名称 (@表示根域名)')
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
        $recordId = (int)$input->getArgument('record_id');
        $useLocalData = $input->getOption('local');
        
        if ($useLocalData) {
            // 从本地数据库获取记录信息
            $localRecord = $this->domainRecordRepository->findOneBy([
                'domainName' => $domain,
                'recordId' => $recordId,
            ]);
            
            if (!$localRecord) {
                $io->error(sprintf('找不到本地记录, 域名: %s, 记录ID: %d', $domain, $recordId));
                return Command::FAILURE;
            }
            
            try {
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
                
                if (!empty($record)) {
                    $io->success(sprintf('成功使用本地数据更新域名记录: %s.%s (%s)', 
                        $localRecord->getName(), 
                        $domain, 
                        $localRecord->getType()
                    ));
                    
                    $rows = [];
                    foreach ($record as $key => $value) {
                        if (is_scalar($value)) {
                            $rows[] = [$key, $value];
                        }
                    }
                    
                    if (!empty($rows)) {
                        $io->table(['属性', '值'], $rows);
                    }
                    
                    return Command::SUCCESS;
                } else {
                    $io->error('更新域名记录失败');
                    return Command::FAILURE;
                }
            } catch (\Throwable $e) {
                $io->error('更新域名记录时发生错误: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            // 获取当前记录信息
            try {
                $currentRecord = $this->domainService->getDomainRecord($domain, $recordId);
                
                if (empty($currentRecord)) {
                    $io->error(sprintf('找不到远程记录, 域名: %s, 记录ID: %d', $domain, $recordId));
                    return Command::FAILURE;
                }
                
                // 获取命令行指定的新值，如果未指定则使用当前值
                $type = $input->getOption('type') ?? $currentRecord['type'];
                $name = $input->getOption('name') ?? $currentRecord['name'];
                $data = $input->getOption('data') ?? $currentRecord['data'];
                
                $priority = $input->getOption('priority') !== null ? 
                    (int)$input->getOption('priority') : 
                    ($currentRecord['priority'] ?? null);
                
                $port = $input->getOption('port') !== null ? 
                    (int)$input->getOption('port') : 
                    ($currentRecord['port'] ?? null);
                
                $ttl = $input->getOption('ttl') !== null ? 
                    (int)$input->getOption('ttl') : 
                    ($currentRecord['ttl'] ?? null);
                
                $weight = $input->getOption('weight') !== null ? 
                    (int)$input->getOption('weight') : 
                    ($currentRecord['weight'] ?? null);
                
                $flags = $input->getOption('flags') ?? ($currentRecord['flags'] ?? null);
                $tag = $input->getOption('tag') ?? ($currentRecord['tag'] ?? null);
                
                // 确认是否更新
                $io->section('当前记录信息');
                $currentRows = [];
                foreach ($currentRecord as $key => $value) {
                    if (is_scalar($value)) {
                        $currentRows[] = [$key, $value];
                    }
                }
                
                if (!empty($currentRows)) {
                    $io->table(['属性', '值'], $currentRows);
                }
                
                $io->section('将更新为');
                $newRows = [
                    ['type', $type],
                    ['name', $name],
                    ['data', $data],
                ];
                
                if ($priority !== null) $newRows[] = ['priority', $priority];
                if ($port !== null) $newRows[] = ['port', $port];
                if ($ttl !== null) $newRows[] = ['ttl', $ttl];
                if ($weight !== null) $newRows[] = ['weight', $weight];
                if ($flags !== null) $newRows[] = ['flags', $flags];
                if ($tag !== null) $newRows[] = ['tag', $tag];
                
                $io->table(['属性', '值'], $newRows);
                
                if (!$io->confirm('确定要更新这条记录吗?', false)) {
                    $io->warning('操作已取消');
                    return Command::SUCCESS;
                }
                
                $record = $this->domainService->updateDomainRecord(
                    $domain,
                    $recordId,
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
                    $io->success(sprintf('成功更新域名记录: %s.%s (%s)', $name, $domain, $type));
                    
                    // 同步更新的记录到本地数据库
                    $io->section('同步到本地数据库');
                    $this->domainService->syncDomainRecords($domain);
                    $io->success('成功同步记录到本地数据库');
                    
                    return Command::SUCCESS;
                } else {
                    $io->error('更新域名记录失败');
                    return Command::FAILURE;
                }
            } catch (\Throwable $e) {
                $io->error('更新域名记录时发生错误: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }
    }
}
