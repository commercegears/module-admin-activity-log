<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\AbstractEntity as EavResource;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds the field-level diff (old -> new) of a model. Only real table columns / EAV attributes are compared,
 * secrets are masked and long values are truncated, so the stored diff stays small and safe.
 */
class DiffBuilder
{
    public const MASK = '******';
    private const MAX_VALUE_LENGTH = 5000;

    /** Never compared for any entity. */
    private const IGNORED_FIELDS = [
        'updated_at', 'update_time', 'created_at', 'creation_time', 'form_key', 'store_id',
        'rp_token', 'rp_token_created_at', 'failures_num', 'first_failure', 'lock_expires',
    ];

    /** Values that are always masked. */
    private const SENSITIVE_FIELDS = [
        'password', 'password_hash', 'password_confirmation', 'new_password', 'current_password',
        'confirmation', 'token', 'secret', 'api_key', 'api_secret', 'access_token', 'refresh_token',
    ];

    /** @var array<string, array<string, true>|null> */
    private array $allowedFieldsCache = [];

    public function __construct(
        private readonly EntityTypeResolver $typeResolver,
        private readonly EavConfig $eavConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param array<string, mixed> $orig data before the save (empty for new objects)
     * @return array<string, array{0: string, 1: string}> field => [old, new]
     */
    public function diff(AbstractModel $object, string $type, array $orig, bool $isNew): array
    {
        $allowed = $this->getAllowedFields($object, $type);
        $ignored = array_flip(array_merge(self::IGNORED_FIELDS, $this->typeResolver->getIgnoredFields($type)));
        $extra = array_flip($this->typeResolver->getExtraFields($type));
        $idField = $object->getIdFieldName();
        $changes = [];
        foreach ($object->getData() as $field => $newValue) {
            $field = (string)$field;
            if (isset($ignored[$field]) || $field === $idField || ($allowed !== null && !isset($allowed[$field]))) {
                continue;
            }
            if (!$isNew && isset($extra[$field]) && !array_key_exists($field, $orig)) {
                // Not loaded before the save, so the old value is unknown: do not report a false change.
                continue;
            }
            $new = $this->normalize($newValue);
            if ($new === null) {
                continue;
            }
            if (!$isNew && !array_key_exists($field, $orig) && ($new === '' || $new === '0')) {
                // Attribute had no stored value and the form posted its empty/default value.
                continue;
            }
            $old = $isNew ? '' : $this->normalize($orig[$field] ?? null);
            if ($old === null || $this->isEqual($old, $new)) {
                continue;
            }
            if (!$isNew && $old === '' && $this->isSensitive($field)) {
                // Some repositories do not carry secrets in the loaded data; an unknown old value is not a change.
                continue;
            }
            $changes[$field] = $this->protect($field, $old, $new);
        }
        return $changes;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function protect(string $field, string $old, string $new, bool $forceMask = false): array
    {
        if ($forceMask || $this->isSensitive($field)) {
            return [$old === '' ? '' : self::MASK, $new === '' ? '' : self::MASK];
        }
        return [$this->truncate($old), $this->truncate($new)];
    }

    public function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            $flat = [];
            foreach ($value as $item) {
                if ($item !== null && !is_scalar($item)) {
                    return null;
                }
                $flat[] = (string)$item;
            }
            sort($flat);
            return implode(', ', $flat);
        }
        return null;
    }

    public function isEqual(string $old, string $new): bool
    {
        if ($old === $new) {
            return true;
        }
        if (($old === '' && $new === '0') || ($old === '0' && $new === '')) {
            // Empty and "0" mean the same for flags and option fields posted by admin forms.
            return true;
        }
        if (is_numeric($old) && is_numeric($new)) {
            return (float)$old === (float)$new;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $old) && preg_match('/^\d{4}-\d{2}-\d{2}/', $new)) {
            return strtotime($old) === strtotime($new);
        }
        return false;
    }

    public function isSensitive(string $field): bool
    {
        return in_array(strtolower($field), self::SENSITIVE_FIELDS, true)
            || (bool)preg_match(
                '/(^|_)(password|passwd|passphrase|secret|token|apikey|cvv|cvc|cc_number|cc_cid|card_number)(_|$)/i',
                $field
            )
            || (bool)preg_match('/(^|_)(api|private|secret|access|license|licence|signing|encryption)_?key(_|$)/i', $field);
    }

    /**
     * Copy of a nested array (for example posted form data) with the values of sensitive keys masked.
     */
    public function maskNested(mixed $value, int $depth = 0): mixed
    {
        if (!is_array($value) || $depth > 10) {
            return $value;
        }
        foreach ($value as $key => $item) {
            if (is_string($key) && $this->isSensitive($key) && !is_array($item)) {
                $value[$key] = ($item === null || $item === '') ? $item : self::MASK;
            } else {
                $value[$key] = $this->maskNested($item, $depth + 1);
            }
        }
        return $value;
    }

    /**
     * Human readable label (SKU, title, increment ID, ...) for the log grid.
     */
    public function getLabel(AbstractModel $object, string $type): string
    {
        foreach ($this->typeResolver->getLabelFields($type) as $field) {
            $value = $object->getData($field);
            if (is_scalar($value) && trim((string)$value) !== '') {
                return mb_substr(trim(strip_tags((string)$value)), 0, 255);
            }
        }
        if (method_exists($object, 'getOrder')) {
            try {
                $order = $object->getOrder();
                if ($order && $order->getIncrementId()) {
                    return (string)__('Order #%1', $order->getIncrementId());
                }
            } catch (\Throwable) {
                // The label is cosmetic; never break a save because of it.
            }
        }
        return $object->getId() ? '#' . $object->getId() : '';
    }

    public function getScope(AbstractModel $object, string $type): ?string
    {
        if (!in_array($type, ['product', 'category'], true) || !$object->hasData('store_id')) {
            return null;
        }
        $storeId = (int)$object->getData('store_id');
        if ($storeId === 0) {
            return 'default';
        }
        try {
            return 'store:' . $this->storeManager->getStore($storeId)->getCode();
        } catch (\Throwable) {
            return 'store:' . $storeId;
        }
    }

    /**
     * Columns of the main table plus EAV attribute codes; null = compare every scalar field.
     *
     * @return array<string, true>|null
     */
    private function getAllowedFields(AbstractModel $object, string $type): ?array
    {
        $cacheKey = get_class($object) . '|' . $type;
        if (array_key_exists($cacheKey, $this->allowedFieldsCache)) {
            return $this->allowedFieldsCache[$cacheKey];
        }
        $fields = null;
        try {
            $resource = $object->getResource();
            if ($resource instanceof EavResource) {
                $fields = array_keys($resource->getConnection()->describeTable($resource->getEntityTable()));
                $attributes = $this->eavConfig->getEntityAttributes($resource->getEntityType());
                $fields = array_merge($fields, array_keys($attributes));
            } elseif ($resource instanceof AbstractDb) {
                $fields = array_keys($resource->getConnection()->describeTable($resource->getMainTable()));
            }
        } catch (\Throwable) {
            $fields = null;
        }
        if ($fields !== null) {
            $fields = array_fill_keys(array_merge($fields, $this->typeResolver->getExtraFields($type)), true);
        }
        return $this->allowedFieldsCache[$cacheKey] = $fields;
    }

    private function truncate(string $value): string
    {
        return mb_strlen($value) > self::MAX_VALUE_LENGTH
            ? mb_substr($value, 0, self::MAX_VALUE_LENGTH) . ' […]'
            : $value;
    }
}
