<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Plugin;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Security\Model\AdminSessionsManager;
use CommerceGears\ActivityLog\Model\LoginLogger;

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
