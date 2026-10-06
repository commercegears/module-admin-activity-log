<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * One logged admin action (read-only; rows are written in bulk by LogBuffer).
 */
class Log extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Log::class);
    }

    /**
     * @return array<int, array{field: string, old: string, new: string}>
     */
    public function getChangeList(): array
    {
        $changes = json_decode((string)$this->getData('changes'), true);
        return is_array($changes) ? $changes : [];
    }
}
