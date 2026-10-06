<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Psr\Log\LoggerInterface;
use Softaware\ActivityLog\Model\ResourceModel\Log as LogResource;

/**
 * Request-scoped buffer. Changes are collected while models are saved, kept only once the database transaction
 * is committed, merged per entity, and written with a single multi-row insert at the end of the request.
 */
class LogBuffer
{
    /** @var array<int, array<string, mixed>> object id => entry waiting for the transaction commit */
    private array $staged = [];

    /** @var array<string, array<string, mixed>> entity key => committed entry */
    private array $entries = [];

    private bool $shutdownRegistered = false;

    public function __construct(
        private readonly LogResource $logResource,
        private readonly RequestContext $context,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Keep an entry until the model's transaction is committed (see commit()).
     *
     * @param array<string, mixed> $entry
     */
    public function stage(object $object, array $entry): void
    {
        $id = spl_object_id($object);
        if (isset($this->staged[$id])) {
            $entry = $this->merge($this->staged[$id], $entry);
        }
        $this->staged[$id] = $entry;
    }

    public function commit(object $object): void
    {
        $id = spl_object_id($object);
        if (!isset($this->staged[$id])) {
            return;
        }
        $entry = $this->staged[$id];
        unset($this->staged[$id]);
        $this->add($entry);
    }

    /**
     * Add an entry that needs no transaction (mass actions, imports, permission changes).
     *
     * @param array<string, mixed> $entry
     */
    public function add(array $entry): void
    {
        $key = $entry['key'] ?? ($entry['entity_type'] . '|' . ($entry['entity_id'] ?? uniqid('', true)));
        $entry['key'] = $key;
        $this->entries[$key] = isset($this->entries[$key]) ? $this->merge($this->entries[$key], $entry) : $entry;
        if (!$this->shutdownRegistered) {
            // Safety net for requests that end with an exception before the front controller plugin flushes.
            register_shutdown_function([$this, 'flush']);
            $this->shutdownRegistered = true;
        }
    }

    public function flush(): void
    {
        $entries = $this->entries;
        $this->entries = [];
        if ($entries === []) {
            return;
        }
        $now = gmdate('Y-m-d H:i:s');
        $rows = [];
        try {
            $base = [
                'created_at' => $now,
                'user_id' => $this->context->getUserId(),
                'username' => $this->context->getUsername(),
                'ip' => $this->context->getIp(),
                'request_path' => $this->context->getActionPath(),
                'request_id' => $this->context->getRequestId(),
            ];
            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];
                if (($entry['action'] ?? '') === 'update' && $changes === []) {
                    continue;
                }
                $list = [];
                foreach ($changes as $field => [$old, $new]) {
                    $list[] = ['field' => (string)$field, 'old' => $old, 'new' => $new];
                }
                $rows[] = $base + [
                    'action' => (string)$entry['action'],
                    'entity_type' => (string)$entry['entity_type'],
                    'entity_id' => isset($entry['entity_id']) ? mb_substr((string)$entry['entity_id'], 0, 64) : null,
                    'entity_label' => mb_substr((string)($entry['entity_label'] ?? ''), 0, 255),
                    'scope' => isset($entry['scope']) ? mb_substr((string)$entry['scope'], 0, 64) : null,
                    'changes_count' => min(65535, count($list)),
                    'changes' => $list ? json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : null,
                ];
            }
            if ($rows) {
                $this->logResource->insertRows($rows);
            }
        } catch (\Throwable $e) {
            // Logging must never break an admin save.
            $this->logger->error('Softaware_ActivityLog: could not write activity log: ' . $e->getMessage());
        }
    }

    /**
     * Merge two entries of the same entity: first old value, last new value per field.
     *
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     * @return array<string, mixed>
     */
    private function merge(array $first, array $second): array
    {
        $priority = ['delete' => 3, 'create' => 2];
        $action = ($priority[$second['action']] ?? 1) > ($priority[$first['action']] ?? 1)
            ? $second['action'] : $first['action'];
        $changes = $first['changes'] ?? [];
        foreach ($second['changes'] ?? [] as $field => [$old, $new]) {
            $changes[$field] = [isset($changes[$field]) ? $changes[$field][0] : $old, $new];
            if ($changes[$field][0] === $changes[$field][1]) {
                unset($changes[$field]);
            }
        }
        $merged = array_merge($first, array_filter($second, static fn ($value) => $value !== null && $value !== ''));
        $merged['action'] = $action;
        $merged['changes'] = $changes;
        return $merged;
    }
}
