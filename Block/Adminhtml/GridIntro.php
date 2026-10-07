<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Security\Model\ConfigInterface as SecurityConfig;
use Softaware\ActivityLog\Model\Config;

/**
 * One-line introduction above the Activity Log grids, with a warning when the matching log is switched off.
 * The block's "page" argument is "actions", "logins" or "sessions".
 */
class GridIntro extends Template
{
    protected $_template = 'Softaware_ActivityLog::grid/intro.phtml';

    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly SecurityConfig $securityConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getPage(): string
    {
        return (string)$this->getData('page');
    }

    /**
     * Whether the log shown on this page is currently recorded.
     */
    public function isLogging(): bool
    {
        return match ($this->getPage()) {
            'actions' => $this->config->isActionLogEnabled(),
            'logins' => $this->config->isLoginLogEnabled(),
            default => true,
        };
    }

    public function getRetentionDays(): int
    {
        return $this->config->getRetentionDays();
    }

    public function getSessionLifetimeMinutes(): int
    {
        return (int)ceil(max(60, (int)$this->securityConfig->getAdminSessionLifetime()) / 60);
    }

    /**
     * Link to the module's settings, or null when the admin may not open them.
     */
    public function getSettingsUrl(): ?string
    {
        if (!$this->_authorization->isAllowed('Softaware_ActivityLog::config')) {
            return null;
        }
        return $this->getUrl('adminhtml/system_config/edit', ['section' => 'softaware_activitylog']);
    }
}
