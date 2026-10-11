<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model;

use CommerceGears\ActivityLog\Model\ResourceModel\Log as LogResource;
use CommerceGears\ActivityLog\Model\ResourceModel\Login as LoginResource;

/**
 * Deletes log rows older than the retention period, in batches.
 */
class Cleaner
{
    public function __construct(
        private readonly Config $config,
        private readonly LogResource $logResource
    ) {
    }

    /**
     * @return array{actions: int, logins: int}
     */
    public function clean(?int $days = null): array
    {
        $days ??= $this->config->getRetentionDays();
        if ($days <= 0) {
            return ['actions' => 0, 'logins' => 0];
        }
        $date = gmdate('Y-m-d H:i:s', time() - $days * 86400);
        return [
            // Changes to the Activity Log's own settings are kept, so lowering the retention or switching logging
            // off cannot remove the record of who did it.
            'actions' => $this->logResource->deleteOlderThan(LogResource::TABLE, 'log_id', $date, 5000, [
                $this->getKeepOwnSettingsCondition(),
            ]),
            'logins' => $this->logResource->deleteOlderThan(LoginResource::TABLE, 'login_id', $date),
        ];
    }

    private function getKeepOwnSettingsCondition(): string
    {
        $connection = $this->logResource->getConnection();
        return 'NOT (' . $connection->quoteInto('entity_type = ?', 'config')
            . ' AND ' . $connection->quoteInto("COALESCE(entity_id, '') = ?", Config::SECTION) . ')';
    }
}
