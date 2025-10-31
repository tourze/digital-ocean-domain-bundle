<?php

namespace DigitalOceanDomainBundle\Command;

use DigitalOceanDomainBundle\Entity\DomainRecord;
use DigitalOceanDomainBundle\Repository\DomainRecordRepository;
use DigitalOceanDomainBundle\Service\DomainServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 删除DigitalOcean域名记录命令
 */
#[AsCommand(
    name: self::NAME,
    description: '删除DigitalOcean域名记录',
)]
#[Autoconfigure(public: true)]
class DeleteDomainRecordCommand extends Command
{
    public const NAME = 'digital-ocean:domain:record:delete';

    public function __construct(
        private readonly DomainServiceInterface $domainService,
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
        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Domain must be a string');
        }

        $recordIdValue = $input->getArgument('record_id');
        if (!is_numeric($recordIdValue)) {
            throw new \InvalidArgumentException('Record ID must be numeric');
        }
        $recordId = (int) $recordIdValue;

        $localRecord = $this->displayRecordInfo($io, $domain, $recordId);

        if (!$this->confirmDeletion($io, $domain, $recordId)) {
            return Command::SUCCESS;
        }

        return $this->performDeletion($io, $domain, $recordId, $localRecord);
    }

    private function displayRecordInfo(SymfonyStyle $io, string $domain, int $recordId): ?DomainRecord
    {
        $localRecord = $this->domainRecordRepository->findOneBy([
            'domainName' => $domain,
            'recordId' => $recordId,
        ]);

        if (null !== $localRecord) {
            $this->displayLocalRecord($io, $localRecord);
        } else {
            $this->displayRemoteRecord($io, $domain, $recordId);
        }

        return $localRecord;
    }

    private function displayLocalRecord(SymfonyStyle $io, DomainRecord $localRecord): void
    {
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
    }

    private function displayRemoteRecord(SymfonyStyle $io, string $domain, int $recordId): void
    {
        try {
            $remoteRecord = $this->domainService->getDomainRecord($domain, $recordId);

            if (count($remoteRecord) > 0) {
                $this->displayRemoteRecordData($io, $remoteRecord);
            } else {
                $io->warning(sprintf('找不到记录信息, 域名: %s, 记录ID: %d', $domain, $recordId));
            }
        } catch (\Throwable $e) {
            $io->warning('无法获取远程记录信息: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $remoteRecord
     */
    private function displayRemoteRecordData(SymfonyStyle $io, array $remoteRecord): void
    {
        $io->section('远程记录信息');
        $rows = [];

        foreach ($remoteRecord as $key => $value) {
            if (is_scalar($value)) {
                $rows[] = [$key, $value];
            }
        }

        if (count($rows) > 0) {
            $io->table(['属性', '值'], $rows);
        }
    }

    private function confirmDeletion(SymfonyStyle $io, string $domain, int $recordId): bool
    {
        $confirmationMessage = sprintf('您确定要删除域名 "%s" 的记录ID %d 吗？此操作不可撤销！', $domain, $recordId);

        if (!$io->confirm($confirmationMessage, false)) {
            $io->warning('操作已取消');

            return false;
        }

        return true;
    }

    private function performDeletion(SymfonyStyle $io, string $domain, int $recordId, ?DomainRecord $localRecord): int
    {
        try {
            $result = $this->domainService->deleteDomainRecord($domain, $recordId);

            if ($result) {
                $io->success(sprintf('成功删除域名记录: %s (ID: %d)', $domain, $recordId));
                $this->removeLocalRecord($io, $localRecord);

                return Command::SUCCESS;
            }

            $io->error('删除域名记录失败');

            return Command::FAILURE;
        } catch (\Throwable $e) {
            $io->error('删除域名记录时发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function removeLocalRecord(SymfonyStyle $io, ?DomainRecord $localRecord): void
    {
        if (null !== $localRecord) {
            $io->section('删除本地数据库记录');
            $this->entityManager->remove($localRecord);
            $this->entityManager->flush();
            $io->success('成功删除本地数据库记录');
        }
    }
}
