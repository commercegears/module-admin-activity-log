<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Turns model save/delete events into log entries. Called from the adminhtml model event observers.
 */
class ChangeTracker
{
    /** @var array<int, array{0: string, 1: bool, 2: array<string, mixed>}> object id => [type, isNew, orig] */
    private array $pending = [];

    public function __construct(
        private readonly Config $config,
        private readonly EntityTypeResolver $typeResolver,
        private readonly SnapshotLoader $snapshotLoader,
        private readonly DiffBuilder $diffBuilder,
        private readonly LogBuffer $buffer,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function beforeSave(AbstractModel $object): void
    {
        if ($object instanceof ConfigValue) {
            $this->trackConfigValue($object, false);
            return;
        }
        $type = $this->getTrackedType($object);
        if ($type === null) {
            return;
        }
        $isNew = $object->isObjectNew() || !$object->getId();
        $orig = $isNew ? [] : $this->snapshotLoader->getOriginalData($object);
        $this->pending[spl_object_id($object)] = [$type, $isNew, $orig];
    }

    public function afterSave(AbstractModel $object): void
    {
        $key = spl_object_id($object);
        if (!isset($this->pending[$key])) {
            return;
        }
        [$type, $isNew, $orig] = $this->pending[$key];
        unset($this->pending[$key]);
        $changes = $this->diffBuilder->diff($object, $type, $orig, $isNew);
        if (!$isNew && $changes === []) {
            return;
        }
        $this->buffer->stage($object, [
            'action' => $isNew ? 'create' : 'update',
            'entity_type' => $type,
            'entity_id' => (string)$object->getId(),
            'entity_label' => $this->diffBuilder->getLabel($object, $type),
            'scope' => $this->diffBuilder->getScope($object, $type),
            'changes' => $changes,
        ]);
    }

    public function afterDelete(AbstractModel $object): void
    {
        if ($object instanceof ConfigValue) {
            $this->trackConfigValue($object, true);
            return;
        }
        $type = $this->getTrackedType($object);
        if ($type === null) {
            return;
        }
        $this->buffer->stage($object, [
            'action' => 'delete',
            'entity_type' => $type,
            'entity_id' => (string)$object->getId(),
            'entity_label' => $this->diffBuilder->getLabel($object, $type),
            'scope' => $this->diffBuilder->getScope($object, $type),
            'changes' => [],
        ]);
    }

    public function afterCommit(AbstractModel $object): void
    {
        $this->buffer->commit($object);
    }

    private function getTrackedType(AbstractModel $object): ?string
    {
        if (!$this->config->isActionLogEnabled()) {
            return null;
        }
        $type = $this->typeResolver->resolve($object);
        if ($type === null || $this->config->isEntityTypeExcluded($type)) {
            return null;
        }
        // Role records of type "U" only link a user to a role; the user/role saves are logged themselves.
        if ($type === 'admin_role' && $object->getData('role_type') !== 'G') {
            return null;
        }
        return $type;
    }

    private function trackConfigValue(ConfigValue $value, bool $deleted): void
    {
        if (!$this->config->isActionLogEnabled() || $this->config->isEntityTypeExcluded('config')) {
            return;
        }
        $path = (string)$value->getPath();
        if ($path === '' || $this->isPathExcluded($path)) {
            return;
        }
        $old = $this->diffBuilder->normalize($value->getOldValue());
        if ($deleted) {
            $new = (string)__('(use parent scope)');
        } else {
            $new = $this->diffBuilder->normalize($value->getValue());
            if ($new === null || preg_match('/^\*+$/', $new)) {
                // Obscured fields post "******" when they are left unchanged.
                return;
            }
        }
        if ($old === null || $this->diffBuilder->isEqual($old, $new)) {
            return;
        }
        $mask = $value instanceof Encrypted
            || (bool)preg_match('/(pass(word|wd)?|secret|token|key|credential|signature|salt|hash)[^\/]*$/i', $path);
        $scope = (string)($value->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $scopeId = (int)$value->getScopeId();
        $section = strtok($path, '/');
        $this->buffer->stage($value, [
            'key' => 'config|' . $scope . '|' . $scopeId . '|' . $section,
            'action' => 'update',
            'entity_type' => 'config',
            'entity_id' => $section,
            'entity_label' => $this->getScopeLabel($scope, $scopeId),
            'scope' => $scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT ? 'default' : $scope . ':' . $scopeId,
            'changes' => [$path => $this->diffBuilder->protect($path, $old, $new, $mask)],
        ]);
    }

    private function isPathExcluded(string $path): bool
    {
        foreach ($this->config->getExcludedConfigPaths() as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }
        return false;
    }

    private function getScopeLabel(string $scope, int $scopeId): string
    {
        try {
            if ($scope === ScopeInterface::SCOPE_WEBSITES || $scope === ScopeInterface::SCOPE_WEBSITE) {
                return (string)__('Website: %1', $this->storeManager->getWebsite($scopeId)->getName());
            }
            if ($scope === ScopeInterface::SCOPE_STORES || $scope === ScopeInterface::SCOPE_STORE) {
                return (string)__('Store view: %1', $this->storeManager->getStore($scopeId)->getName());
            }
        } catch (\Throwable) {
            return $scope . ' #' . $scopeId;
        }
        return (string)__('Default Config');
    }
}
