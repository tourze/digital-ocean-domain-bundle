<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 删除DigitalOcean域名记录命令
 */
#[AsCommand(
    name: self::NAME,
    description: '删除DigitalOcean域名记录',
)]
class DeleteDomainRecordCommand extends Command
{
    public const NAME = 'digital-ocean:domain:record:delete';

    public function __construct(
        private readonly DomainService $domainService,
        private readonly DomainRecordRepository $domainRecordRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::REQUIRED, '域名')
            ->addArgument('record_id', InputArgument::REQUIRED, '记录ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domain = $input->getArgument('domain');
        $recordId = (int)$input->getArgument('record_id');

        // 查找本地记录，显示详细信息
        $localRecord = $this->domainRecordRepository->findOneBy([
            'domainName' => $domain,
            'recordId' => $recordId,
        ]);

        if ($localRecord !== null) {
            $io->section('本地记录信息');
            $io->table(
                ['属性', '值'],
                [
                    ['ID', $localRecord->getRecordId()],
                    ['域名', $localRecord->getDomainName()],
                    ['类型', $localRecord->getType()],
                    ['名称', $localRecord->getName()],
                    ['数据', $localRecord->getData()],
                ]
            );
        } else {
            // 尝试获取远程记录信息
            try {
                $remoteRecord = $this->domainService->getDomainRecord($domain, $recordId);
                
                if (!empty($remoteRecord)) {
                    $io->section('远程记录信息');
                    $rows = [];
                    foreach ($remoteRecord as $key => $value) {
                        if (is_scalar($value)) {
                            $rows[] = [$key, $value];
                        }
                    }
                    
                    if (!empty($rows)) {
                        $io->table(['属性', '值'], $rows);
                    }
                } else {
                    $io->warning(sprintf('找不到记录信息, 域名: %s, 记录ID: %d', $domain, $recordId));
                }
            } catch (\Throwable $e) {
                $io->warning('无法获取远程记录信息: ' . $e->getMessage());
            }
        }

        // 确认删除
        if (!$io->confirm(sprintf('您确定要删除域名 "%s" 的记录ID %d 吗？此操作不可撤销！', $domain, $recordId), false)) {
            $io->warning('操作已取消');
            return Command::SUCCESS;
        }

        try {
            $result = $this->domainService->deleteDomainRecord($domain, $recordId);
            
            if ($result) {
                $io->success(sprintf('成功删除域名记录: %s (ID: %d)', $domain, $recordId));
                
                // 删除本地记录
                if ($localRecord !== null) {
                    $io->section('删除本地数据库记录');
                    $this->entityManager->remove($localRecord);
                    $this->entityManager->flush();
                    $io->success('成功删除本地数据库记录');
                }
                
                return Command::SUCCESS;
            } else {
                $io->error('删除域名记录失败');
                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            $io->error('删除域名记录时发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
