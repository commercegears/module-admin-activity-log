<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    public const TABLE = 'softaware_activity_log';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'log_id');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function insertRows(array $rows): void
    {
        $this->getConnection()->insertMultiple($this->getMainTable(), $rows);
    }

    /**
     * Delete rows older than the given UTC date in batches. Returns the number of deleted rows.
     */
    public function deleteOlderThan(string $table, string $idField, string $date, int $batchSize = 5000): int
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable($table);
        $deleted = 0;
        do {
            $ids = $connection->fetchCol(
                $connection->select()->from($tableName, [$idField])->where('created_at < ?', $date)->limit($batchSize)
            );
            if ($ids) {
                $deleted += $connection->delete($tableName, [$idField . ' IN (?)' => $ids]);
            }
        } while (count($ids) === $batchSize);
        return $deleted;
    }
}
