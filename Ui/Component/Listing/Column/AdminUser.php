<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Admin user column: says where an entry without an admin user came from instead of leaving the cell empty.
 */
class AdminUser extends Column
{
    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        $name = (string)$this->getData('name');
        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            if ((string)($item[$name] ?? '') !== '') {
                continue;
            }
            $dataSource['data']['items'][$i][$name] = str_starts_with((string)($item['request_path'] ?? ''), 'cli:')
                ? (string)__('(command line)')
                : (string)__('(not signed in)');
        }
        return $dataSource;
    }
}
