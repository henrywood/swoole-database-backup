<?php

namespace DatabaseBackup;

use DatabaseBackup\Services\BackupService;
use Swoole\Timer;
use Throwable;

/**
 * @phpstan-consistent-constructor
 */
class Backup
{
    private BackupService $backupService;
    private SmtpCredential $smtpCredential;
    private array $mailReceivers = [];

    public function __construct()
    {
        $this->smtpCredential = NoSmtp::new();
        $this->backupService = new BackupService();
    }

    public static function new(): static
    {
        return new static();
    }

    public function withSmtp(SmtpCredential $credential): static
    {
        $this->smtpCredential = $credential;
        return $this;
    }

    /**
     * @param MailReceiver[] $emailAddresses
     * @return $this
     */
    public function withMailReceivers(array $emailAddresses): static
    {
        $this->mailReceivers = $emailAddresses;
        return $this;
    }

    /**
     * @param array<int, class-string<AbstractBackup>> $backupClasses
     * @return array timers array
     */
    public function startPeriodic(array $backupClasses): array {
        $timerIds = [];
        foreach ($backupClasses as $backupClass) {
            $backup = new $backupClass;
            $timerIds[] = Timer::tick($backup->interval(), function() use ($backup) {
                $backup->onBefore($backup->getBackupFilePath());
                go(function() use ($backup) {
                    try {
                        $this->backupService->takeBackup(
                            backup: $backup,
                            smtpCredential: $this->smtpCredential,
                            mailReceivers: $this->mailReceivers,
                        );
                    } catch (Throwable $exception) {
                        $backup->onError($exception);
                    }
                });
            });
        }
        return $timerIds;
    }

    /**
     * @param array<int, class-string<AbstractBackup>> $backupClasses
     * @return void
     */
    public function start(array $backupClasses): void
    {
        foreach ($backupClasses as $backupClass) {
            /** @var AbstractBackup $backup */
            $backup = new $backupClass;

            Timer::after($backup->interval(), function () use ($backup) {
                $backup->onBefore($backup->getBackupFilePath());

                go(function () use ($backup) {
                    try {
                        $this->backupService->takeBackup(
                            backup: $backup,
                            smtpCredential: $this->smtpCredential,
                            mailReceivers: $this->mailReceivers,
                        );
                    } catch (Throwable $exception) {
                        $backup->onError($exception);
                    }
                });
            });
        }
    }
}
