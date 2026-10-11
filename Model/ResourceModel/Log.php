<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    public const TABLE = 'commercegears_activity_log';

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
     *
     * @param string[] $extraWhere further SQL conditions (already quoted) that rows must match to be deleted
     */
    public function deleteOlderThan(
        string $table,
        string $idField,
        string $date,
        int $batchSize = 5000,
        array $extraWhere = []
    ): int {
        $connection = $this->getConnection();
        $tableName = $this->getTable($table);
        $deleted = 0;
        do {
            $select = $connection->select()->from($tableName, [$idField])->where('created_at < ?', $date)->limit($batchSize);
            foreach ($extraWhere as $condition) {
                $select->where($condition);
            }
            $ids = $connection->fetchCol($select);
            if ($ids) {
                $deleted += $connection->delete($tableName, [$idField . ' IN (?)' => $ids]);
            }
        } while (count($ids) === $batchSize);
        return $deleted;
    }
}
