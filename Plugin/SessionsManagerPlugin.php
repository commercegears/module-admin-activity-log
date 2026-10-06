<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Plugin;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Security\Model\AdminSessionsManager;
use Softaware\ActivityLog\Model\LoginLogger;

/**
 * Links the login log row to Magento's admin session record, so the Active Sessions grid can show browser/country.
 */
class SessionsManagerPlugin
{
    public function __construct(
        private readonly LoginLogger $loginLogger,
        private readonly AuthSession $authSession
    ) {
    }

    public function afterProcessLogin(AdminSessionsManager $subject, mixed $result): mixed
    {
        $this->loginLogger->attachSession((int)$this->authSession->getAdminSessionInfoId());
        return $result;
    }
}
