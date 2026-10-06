<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Action implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'create', 'label' => __('Create')],
            ['value' => 'update', 'label' => __('Update')],
            ['value' => 'delete', 'label' => __('Delete')],
            ['value' => 'move', 'label' => __('Move')],
            ['value' => 'mass_action', 'label' => __('Mass action')],
            ['value' => 'import', 'label' => __('Import')],
        ];
    }
}
