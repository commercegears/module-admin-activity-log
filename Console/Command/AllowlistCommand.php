<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Console\Command;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use CommerceGears\ActivityLog\Model\Config;
use CommerceGears\ActivityLog\Model\IpMatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows or changes the admin IP allowlist from the command line (way back in after a lock-out).
 */
class AllowlistCommand extends Command
{
    public function __construct(
        private readonly Config $config,
        private readonly WriterInterface $configWriter,
        private readonly ReinitableConfigInterface $reinitableConfig,
        private readonly TypeListInterface $cacheTypeList,
        private readonly IpMatcher $ipMatcher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('commercegears:activity-log:allowlist')
            ->setDescription('Show the admin IP allowlist, add an IP or CIDR range, or disable the allowlist')
            ->addOption('add', null, InputOption::VALUE_REQUIRED, 'IP address or CIDR range to add')
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Turn the allowlist off');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $changed = false;
        if ($input->getOption('disable')) {
            $this->configWriter->save(Config::XML_ALLOWLIST_ENABLED, '0');
            $output->writeln('Admin IP allowlist disabled.');
            $changed = true;
        }
        $add = $input->getOption('add');
        if ($add !== null) {
            $add = trim((string)$add);
            if (!$this->ipMatcher->isValidRule($add)) {
                $output->writeln(sprintf('<error>"%s" is not a valid IP address or CIDR range.</error>', $add));
                return Command::INVALID;
            }
            $list = $this->config->getAllowlist();
            if (!in_array($add, $list, true)) {
                $list[] = $add;
                $this->configWriter->save(Config::XML_ALLOWLIST_IPS, implode("\n", $list));
                $output->writeln(sprintf('Added %s to the admin IP allowlist.', $add));
                $changed = true;
            }
        }
        if ($changed) {
            $this->cacheTypeList->cleanType('config');
            $this->reinitableConfig->reinit();
        }
        $output->writeln('Status: ' . ($this->config->isAllowlistEnabled() ? 'enabled' : 'disabled'));
        foreach ($this->config->getAllowlist() as $rule) {
            $output->writeln('  ' . $rule);
        }
        return Command::SUCCESS;
    }
}
