<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

/**
 * Admin IP allowlist. An empty list never blocks anybody, so a misconfiguration cannot lock everyone out.
 */
class Allowlist
{
    /** @var array<string, bool> */
    private array $cache = [];

    public function __construct(
        private readonly Config $config,
        private readonly IpMatcher $ipMatcher
    ) {
    }

    public function isAllowed(string $ip): bool
    {
        if (!$this->config->isAllowlistEnabled()) {
            return true;
        }
        if (!isset($this->cache[$ip])) {
            $rules = $this->config->getAllowlist();
            $this->cache[$ip] = $rules === [] || $this->ipMatcher->matches($ip, $rules);
        }
        return $this->cache[$ip];
    }
}
