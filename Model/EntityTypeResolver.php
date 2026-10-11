<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

/**
 * Maps model classes to logged entity types. The map is defined in etc/di.xml (argument "types") so other
 * modules can add their own entities without code changes.
 */
class EntityTypeResolver
{
    /** @var array<string, string|null> */
    private array $classCache = [];

    /**
     * @param array<string, array<string, string>> $types
     */
    public function __construct(
        private readonly array $types = []
    ) {
    }

    /**
     * Logged entity type for a model, or null when the model is not tracked.
     */
    public function resolve(object $object): ?string
    {
        $class = get_class($object);
        if (array_key_exists($class, $this->classCache)) {
            return $this->classCache[$class];
        }
        $resolved = null;
        foreach ($this->types as $code => $definition) {
            foreach ($this->split($definition['class'] ?? '') as $trackedClass) {
                if ($object instanceof $trackedClass) {
                    $resolved = (string)$code;
                    break 2;
                }
            }
        }
        $this->classCache[$class] = $resolved;
        return $resolved;
    }

    /**
     * @return array<string, string> code => label
     */
    public function getTypeOptions(): array
    {
        $options = [];
        foreach ($this->types as $code => $definition) {
            $options[(string)$code] = (string)__($definition['label'] ?? $code);
        }
        asort($options);
        return $options;
    }

    public function getTypeLabel(string $code): string
    {
        return (string)__($this->types[$code]['label'] ?? $code);
    }

    /**
     * @return string[]
     */
    public function getLabelFields(string $type): array
    {
        return $this->split($this->types[$type]['label_fields'] ?? '');
    }

    /**
     * Fields that are not table columns/attributes but are still compared (for example product category_ids).
     *
     * @return string[]
     */
    public function getExtraFields(string $type): array
    {
        return $this->split($this->types[$type]['extra_fields'] ?? '');
    }

    /**
     * @return string[]
     */
    public function getIgnoredFields(string $type): array
    {
        return $this->split($this->types[$type]['ignore_fields'] ?? '');
    }

    /**
     * Default values the system fills in on its own ("field=value", comma-separated), for example a product's
     * options_container=container2. They are not reported for new records, nor when the old value was not loaded.
     *
     * @return array<string, string> field => default value
     */
    public function getSystemDefaults(string $type): array
    {
        $defaults = [];
        foreach ($this->split($this->types[$type]['system_defaults'] ?? '') as $pair) {
            [$field, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if (trim($field) !== '') {
                $defaults[trim($field)] = trim($value);
            }
        }
        return $defaults;
    }

    /**
     * @return string[]
     */
    private function split(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
