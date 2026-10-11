<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Plugin;

use Magento\Authorization\Model\ResourceModel\Rules as RulesResource;
use Magento\Authorization\Model\Rules;
use CommerceGears\ActivityLog\Model\Config;
use CommerceGears\ActivityLog\Model\LogBuffer;

/**
 * Role resources (permissions) are written straight to the table, so the added/removed ACL resources are logged here.
 */
class RulesPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly LogBuffer $buffer
    ) {
    }

    public function aroundSaveRel(RulesResource $subject, callable $proceed, Rules $rule): mixed
    {
        if (!$this->config->isActionLogEnabled() || $this->config->isEntityTypeExcluded('admin_role')) {
            return $proceed($rule);
        }
        $roleId = (int)$rule->getRoleId();
        $before = $this->getAllowed($subject, $roleId);
        $result = $proceed($rule);
        $after = $this->getAllowed($subject, $roleId);
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));
        if ($added || $removed) {
            $changes = [];
            if ($added) {
                $changes['resources_added'] = ['', implode(', ', $added)];
            }
            if ($removed) {
                $changes['resources_removed'] = [implode(', ', $removed), ''];
            }
            $connection = $subject->getConnection();
            $name = (string)$connection->fetchOne(
                $connection->select()
                    ->from($subject->getTable('authorization_role'), ['role_name'])
                    ->where('role_id = ?', $roleId)
            );
            $this->buffer->add([
                'key' => 'admin_role|' . $roleId,
                'action' => 'update',
                'entity_type' => 'admin_role',
                'entity_id' => (string)$roleId,
                'entity_label' => $name,
                'changes' => $changes,
            ]);
        }
        return $result;
    }

    /**
     * @return string[]
     */
    private function getAllowed(RulesResource $resource, int $roleId): array
    {
        $connection = $resource->getConnection();
        return $connection->fetchCol(
            $connection->select()
                ->from($resource->getMainTable(), ['resource_id'])
                ->where('role_id = ?', $roleId)
                ->where('permission = ?', 'allow')
        );
    }
}
