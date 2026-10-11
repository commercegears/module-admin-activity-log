<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use CommerceGears\ActivityLog\Model\Source\Action;

/**
 * 1.4.0 names actions as Adobe Commerce does. Existing entries are converted so that filters and exports show the
 * same names for old and new entries:
 * - "mass_action" becomes "mass_delete" (massDelete / massRemove) or "mass_update" (every other mass action);
 * - product bulk attribute updates ("Update attributes", grid status change; entries listing "product_ids") become
 *   "mass_update";
 * - order saves made by Cancel / Hold / Unhold (single or mass) become "cancel" / "hold" / "unhold".
 * Each step is one UPDATE on the action column, so it is safe to run on large logs and on repeated runs.
 */
class AdobeCommerceActionCodes implements DataPatchInterface
{
    private const ORDER_ACTIONS = [
        Action::CANCEL => ['cancel', 'massCancel'],
        Action::HOLD => ['hold', 'massHold'],
        Action::UNHOLD => ['unhold', 'massUnhold'],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('commercegears_activity_log');
        $connection->update(
            $table,
            ['action' => Action::MASS_DELETE],
            [
                'action = ?' => Action::LEGACY_MASS_ACTION,
                '(request_path LIKE \'%/massDelete\' OR request_path LIKE \'%/massRemove\')',
            ]
        );
        $connection->update(
            $table,
            ['action' => Action::MASS_UPDATE],
            ['action = ?' => Action::LEGACY_MASS_ACTION]
        );
        $connection->update(
            $table,
            ['action' => Action::MASS_UPDATE],
            [
                'action = ?' => Action::SAVE,
                'entity_type = ?' => 'product',
                'changes LIKE ?' => '%"field":"product_ids"%',
            ]
        );
        foreach (self::ORDER_ACTIONS as $code => $names) {
            $paths = [];
            foreach ($names as $name) {
                $paths[] = $connection->quoteInto('request_path LIKE ?', '%/' . $name);
            }
            $connection->update(
                $table,
                ['action' => $code],
                [
                    'action = ?' => Action::SAVE,
                    'entity_type = ?' => 'order',
                    '(' . implode(' OR ', $paths) . ')',
                ]
            );
        }
        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
