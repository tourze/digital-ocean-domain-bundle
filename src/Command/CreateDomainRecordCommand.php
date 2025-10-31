<?php

namespace DigitalOceanDomainBundle\Command;

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
 * 添加DigitalOcean域名记录命令
 */
#[AsCommand(
    name: self::NAME,
    description: '添加DigitalOcean域名记录',
)]
#[Autoconfigure(public: true)]
class CreateDomainRecordCommand extends Command
{
    public const NAME = 'digital-ocean:domain:record:create';

    public function __construct(
        private readonly DomainServiceInterface $domainService,
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

        try {
            $record = $this->createDomainRecord($input);

            return $this->handleCreateResult($io, $record, $input);
        } catch (\Throwable $e) {
            $io->error('添加域名记录时发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createDomainRecord(InputInterface $input): array
    {
        $domain = $input->getArgument('domain');
        $type = $input->getArgument('type');
        $name = $input->getArgument('name');
        $data = $input->getArgument('data');

        // Ensure string types for required parameters
        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Domain must be a string');
        }
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Type must be a string');
        }
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Name must be a string');
        }
        if (!is_string($data)) {
            throw new \InvalidArgumentException('Data must be a string');
        }

        // Get optional string parameters with proper type checking
        $flags = $this->getStringOption($input, 'flags');
        $tag = $this->getStringOption($input, 'tag');

        return $this->domainService->createDomainRecord(
            $domain,
            $type,
            $name,
            $data,
            $this->getIntOption($input, 'priority'),
            $this->getIntOption($input, 'port'),
            $this->getIntOption($input, 'ttl'),
            $this->getIntOption($input, 'weight'),
            $flags,
            $tag
        );
    }

    private function getIntOption(InputInterface $input, string $name): ?int
    {
        $value = $input->getOption($name);

        if (null === $value) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" must be numeric', $name));
        }

        return (int) $value;
    }

    private function getStringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);

        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" must be a string', $name));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function handleCreateResult(SymfonyStyle $io, array $record, InputInterface $input): int
    {
        if (0 === count($record)) {
            $io->error('添加域名记录失败');

            return Command::FAILURE;
        }

        $this->displaySuccessMessage($io, $record, $input);
        $domain = $input->getArgument('domain');
        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Domain must be a string');
        }
        $this->syncToLocalDatabase($io, $domain);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function displaySuccessMessage(SymfonyStyle $io, array $record, InputInterface $input): void
    {
        $name = $input->getArgument('name');
        $domain = $input->getArgument('domain');
        $type = $input->getArgument('type');

        // Ensure string types for display
        if (!is_string($name) || !is_string($domain) || !is_string($type)) {
            throw new \InvalidArgumentException('Arguments must be strings');
        }

        $io->success(sprintf('成功添加域名记录: %s.%s (%s)', $name, $domain, $type));

        $rows = $this->buildTableRows($record);
        if (count($rows) > 0) {
            $io->table(['属性', '值'], $rows);
        }
    }

    /**
     * @param array<string, mixed> $record
     * @return list<array{string, string}>
     */
    private function buildTableRows(array $record): array
    {
        $rows = [];
        foreach ($record as $key => $value) {
            if (is_scalar($value)) {
                $rows[] = [$key, (string) $value];
            }
        }

        return $rows;
    }

    private function syncToLocalDatabase(SymfonyStyle $io, string $domain): void
    {
        $io->section('同步到本地数据库');
        $this->domainService->syncDomainRecords($domain);
        $io->success('成功同步记录到本地数据库');
    }
}
