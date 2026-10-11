<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Plugin;

use Magento\Backend\Model\Auth\Session as AuthSession;
use CommerceGears\ActivityLog\Model\Allowlist;
use CommerceGears\ActivityLog\Model\RequestContext;

/**
 * An existing admin session used from an IP outside the allowlist is treated as signed out
 * (the sign-in form is shown, and signing in is refused by AuthPlugin).
 */
class AuthSessionAllowlistPlugin
{
    public function __construct(
        private readonly Allowlist $allowlist,
        private readonly RequestContext $context
    ) {
    }

    public function afterIsLoggedIn(AuthSession $subject, bool $result): bool
    {
        if (!$result) {
            return false;
        }
        return $this->allowlist->isAllowed($this->context->getIp());
    }
}
