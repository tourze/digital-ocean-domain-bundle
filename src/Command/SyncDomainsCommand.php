<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Service\DomainServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 同步DigitalOcean域名命令
 */
#[AsCommand(
    name: self::NAME,
    description: '同步DigitalOcean域名数据',
)]
#[Autoconfigure(public: true)]
class SyncDomainsCommand extends Command
{
    public const NAME = 'digital-ocean:domain:sync';

    public function __construct(
        private readonly DomainServiceInterface $domainService,
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
                    if (!is_object($domain) || !method_exists($domain, 'getName')) {
                        throw new \InvalidArgumentException('Invalid domain object');
                    }

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
