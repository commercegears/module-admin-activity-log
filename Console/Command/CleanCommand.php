<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Console\Command;

use CommerceGears\ActivityLog\Model\Cleaner;
use CommerceGears\ActivityLog\Model\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends Command
{
    public function __construct(
        private readonly Cleaner $cleaner,
        private readonly Config $config
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('commercegears:activity-log:clean')
            ->setDescription('Delete admin activity and login log entries older than the retention period')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Keep this many days instead of the configured value');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getOption('days');
        if ($days !== null && (!ctype_digit((string)$days) || (int)$days < 1)) {
            $output->writeln('<error>--days must be a positive whole number.</error>');
            return Command::INVALID;
        }
        $days = $days !== null ? (int)$days : $this->config->getRetentionDays();
        if ($days <= 0) {
            $output->writeln('Retention is set to 0 (keep forever); nothing deleted.');
            return Command::SUCCESS;
        }
        $result = $this->cleaner->clean($days);
        $output->writeln(sprintf(
            'Deleted %d action log and %d login log entries older than %d days.',
            $result['actions'],
            $result['logins'],
            $days
        ));
        return Command::SUCCESS;
    }
}
