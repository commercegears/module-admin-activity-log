<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Typed access to the module settings (Stores > Configuration > Softaware > Admin Activity Log).
 * All settings are global because the admin panel is global.
 */
class Config
{
    public const SECTION = 'softaware_activitylog';

    public const IP_SOURCE_SERVER = 'server';
    public const IP_SOURCE_CLOUDFLARE = 'cloudflare';

    private const XML_ENABLED = 'softaware_activitylog/general/enabled';
    private const XML_LOG_ACTIONS = 'softaware_activitylog/general/log_actions';
    private const XML_LOG_LOGINS = 'softaware_activitylog/general/log_logins';
    private const XML_IP_SOURCE = 'softaware_activitylog/general/ip_source';
    private const XML_RETENTION_DAYS = 'softaware_activitylog/general/retention_days';
    private const XML_EXCLUDE_TYPES = 'softaware_activitylog/exclude/entity_types';
    private const XML_EXCLUDE_PATHS = 'softaware_activitylog/exclude/config_paths';
    private const XML_NOTIFY_ENABLED = 'softaware_activitylog/notifications/enabled';
    private const XML_NOTIFY_ADMIN_NOTICE = 'softaware_activitylog/notifications/admin_notice';
    private const XML_NOTIFY_RECIPIENTS = 'softaware_activitylog/notifications/recipients';
    private const XML_NOTIFY_SENDER = 'softaware_activitylog/notifications/sender';
    private const XML_NOTIFY_THRESHOLD = 'softaware_activitylog/notifications/failed_threshold';
    private const XML_NOTIFY_WINDOW = 'softaware_activitylog/notifications/failed_window';
    private const XML_NOTIFY_NEW_IP = 'softaware_activitylog/notifications/new_ip';
    private const XML_NOTIFY_NEW_COUNTRY = 'softaware_activitylog/notifications/new_country';
    private const XML_NOTIFY_USER = 'softaware_activitylog/notifications/notify_user';
    private const XML_NOTIFY_FAILED_TEMPLATE = 'softaware_activitylog/notifications/failed_template';
    private const XML_NOTIFY_NEW_IP_TEMPLATE = 'softaware_activitylog/notifications/new_ip_template';
    public const XML_ALLOWLIST_ENABLED = 'softaware_activitylog/allowlist/enabled';
    public const XML_ALLOWLIST_IPS = 'softaware_activitylog/allowlist/ips';

    /** @var string[]|null */
    private ?array $excludedTypes = null;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED);
    }

    public function isActionLogEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_LOG_ACTIONS);
    }

    public function isLoginLogEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_LOG_LOGINS);
    }

    /**
     * Where the client IP comes from: the web server (REMOTE_ADDR) or Cloudflare's CF-Connecting-IP header.
     */
    public function getIpSource(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_IP_SOURCE) === self::IP_SOURCE_CLOUDFLARE
            ? self::IP_SOURCE_CLOUDFLARE
            : self::IP_SOURCE_SERVER;
    }

    public function getRetentionDays(): int
    {
        return max(0, (int)$this->scopeConfig->getValue(self::XML_RETENTION_DAYS));
    }

    public function isEntityTypeExcluded(string $type): bool
    {
        if ($this->excludedTypes === null) {
            $value = (string)$this->scopeConfig->getValue(self::XML_EXCLUDE_TYPES);
            $this->excludedTypes = array_flip(array_filter(array_map('trim', explode(',', $value))));
        }
        return isset($this->excludedTypes[$type]);
    }

    /**
     * Config path patterns that are never logged. One per line, "*" is a wildcard.
     *
     * @return string[]
     */
    public function getExcludedConfigPaths(): array
    {
        return $this->splitLines((string)$this->scopeConfig->getValue(self::XML_EXCLUDE_PATHS));
    }

    public function isNotificationEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_NOTIFY_ENABLED);
    }

    public function isAdminNoticeEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_NOTIFY_ADMIN_NOTICE);
    }

    /**
     * @return string[]
     */
    public function getRecipients(): array
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_NOTIFY_RECIPIENTS);
        $emails = preg_split('/[\s,;]+/', $value) ?: [];
        return array_values(array_filter($emails, static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }

    public function getSenderIdentity(): string
    {
        return (string)($this->scopeConfig->getValue(self::XML_NOTIFY_SENDER) ?: 'general');
    }

    public function getFailedThreshold(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_NOTIFY_THRESHOLD));
    }

    public function getFailedWindowMinutes(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_NOTIFY_WINDOW));
    }

    public function isNewIpNotificationEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_NOTIFY_NEW_IP);
    }

    public function isNewCountryNotificationEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_NOTIFY_NEW_COUNTRY);
    }

    public function isNotifyUserEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_NOTIFY_USER);
    }

    public function getFailedLoginTemplate(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_NOTIFY_FAILED_TEMPLATE);
    }

    public function getNewIpTemplate(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_NOTIFY_NEW_IP_TEMPLATE);
    }

    public function isAllowlistEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_ALLOWLIST_ENABLED);
    }

    /**
     * @return string[]
     */
    public function getAllowlist(): array
    {
        return $this->splitLines((string)$this->scopeConfig->getValue(self::XML_ALLOWLIST_IPS));
    }

    /**
     * @return string[]
     */
    private function splitLines(string $value): array
    {
        $lines = [];
        foreach (preg_split('/\R/', $value) ?: [] as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line) ?? '');
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        return $lines;
    }
}
