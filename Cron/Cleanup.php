<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Cron;

use Softaware\ActivityLog\Model\Cleaner;
use Softaware\ActivityLog\Model\Config;

class Cleanup
{
    public function __construct(
        private readonly Config $config,
        private readonly Cleaner $cleaner
    ) {
    }

    public function execute(): void
    {
        if ($this->config->isEnabled()) {
            $this->cleaner->clean();
        }
    }
}
