<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Login extends AbstractDb
{
    public const TABLE = 'softaware_activity_log_login';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'login_id');
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertRow(array $row): int
    {
        $connection = $this->getConnection();
        $connection->insert($this->getMainTable(), $row);
        return (int)$connection->lastInsertId($this->getMainTable());
    }

    public function countFailuresFromIp(string $ip, string $sinceUtc): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['COUNT(*)'])
            ->where('ip = ?', $ip)
            ->where('created_at >= ?', $sinceUtc)
            ->where('status IN (?)', ['failure', 'locked']);
        return (int)$connection->fetchOne($select);
    }

    /**
     * Earlier successful logins of the user (excluding the given row), optionally limited to an IP or country.
     */
    public function countPreviousSuccess(int $userId, int $excludeId, ?string $ip = null, ?string $country = null): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['COUNT(*)'])
            ->where('user_id = ?', $userId)
            ->where('status = ?', 'success')
            ->where('login_id <> ?', $excludeId);
        if ($ip !== null) {
            $select->where('ip = ?', $ip);
        }
        if ($country !== null) {
            $select->where('country = ?', $country);
        }
        return (int)$connection->fetchOne($select);
    }

    public function hasCountryHistory(int $userId, int $excludeId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['login_id'])
            ->where('user_id = ?', $userId)
            ->where('status = ?', 'success')
            ->where('country IS NOT NULL')
            ->where('login_id <> ?', $excludeId)
            ->limit(1);
        return (bool)$connection->fetchOne($select);
    }
}
