<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Cron;

use CommerceGears\ActivityLog\Model\Cleaner;
use CommerceGears\ActivityLog\Model\Config;

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
