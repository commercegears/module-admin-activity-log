<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Plugin;

use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use Softaware\ActivityLog\Model\Allowlist;
use Softaware\ActivityLog\Model\LoginLogger;
use Softaware\ActivityLog\Model\RequestContext;

/**
 * Enforces the admin IP allowlist at sign-in and records sign-outs.
 */
class AuthPlugin
{
    public function __construct(
        private readonly Allowlist $allowlist,
        private readonly RequestContext $context,
        private readonly LoginLogger $loginLogger,
        private readonly AuthSession $authSession
    ) {
    }

    /**
     * @param string $username
     * @param string $password
     * @throws AuthenticationException
     */
    public function beforeLogin(Auth $subject, $username, $password): void
    {
        $ip = $this->context->getIp();
        if (!$this->allowlist->isAllowed($ip)) {
            $this->loginLogger->logBlocked((string)$username);
            throw new AuthenticationException(
                __('Admin sign-in from your IP address (%1) is not allowed.', $ip)
            );
        }
    }

    public function beforeLogout(Auth $subject): void
    {
        $user = $this->authSession->getUser();
        if ($user instanceof User) {
            $this->loginLogger->logLogout($user, (string)__('Signed out'));
        }
    }
}
