<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Service\DomainService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 同步DigitalOcean域名命令
 */
#[AsCommand(
    name: 'digital-ocean:domain:sync',
    description: '同步DigitalOcean域名数据',
)]
class SyncDomainsCommand extends Command
{
    public function __construct(
        private readonly DomainService $domainService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('开始同步DigitalOcean域名数据');

        try {
            $domains = $this->domainService->syncDomains();
            $count = count($domains);

            if ($count > 0) {
                $io->success(sprintf('成功同步 %d 个域名', $count));
                
                $domainNames = array_map(function ($domain) {
                    return $domain->getName();
                }, $domains);
                
                $io->table(
                    ['域名'],
                    array_map(function ($domainName) {
                        return [$domainName];
                    }, $domainNames)
                );
            } else {
                $io->info('没有找到任何域名');
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('同步域名时发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
