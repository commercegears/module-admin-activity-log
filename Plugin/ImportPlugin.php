<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Plugin;

use Magento\ImportExport\Model\Import;
use Softaware\ActivityLog\Model\Config;
use Softaware\ActivityLog\Model\LogBuffer;

/**
 * Logs System > Data Transfer > Import runs (entity, behaviour, row and item counts).
 */
class ImportPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly LogBuffer $buffer
    ) {
    }

    public function afterImportSource(Import $subject, mixed $result): mixed
    {
        if (!$this->config->isActionLogEnabled() || $this->config->isEntityTypeExcluded('import')) {
            return $result;
        }
        try {
            $entity = (string)$subject->getData('entity');
            $behavior = (string)$subject->getData('behavior');
            $this->buffer->add([
                'key' => 'import|' . spl_object_id($subject),
                'action' => 'import',
                'entity_type' => 'import',
                'entity_id' => $entity,
                'entity_label' => sprintf('%s (%s)', $entity, $behavior),
                'changes' => [
                    'result' => ['', $result ? 'success' : 'failed'],
                    'entity' => ['', $entity],
                    'behavior' => ['', $behavior],
                    'processed_rows' => ['', (string)$subject->getProcessedRowsCount()],
                    'created' => ['', (string)$subject->getCreatedItemsCount()],
                    'updated' => ['', (string)$subject->getUpdatedItemsCount()],
                    'deleted' => ['', (string)$subject->getDeletedItemsCount()],
                ],
            ]);
        } catch (\Throwable) {
            return $result;
        }
        return $result;
    }
}
