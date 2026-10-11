<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Security\Model\AdminSessionInfo;
use Magento\Security\Model\AdminSessionInfoFactory;
use Magento\Security\Model\ResourceModel\AdminSessionInfo as AdminSessionInfoResource;
use Magento\User\Model\UserFactory;

/**
 * Ends other admins' sessions. Uses Magento_Security's session records: a session marked as "logged out manually"
 * is destroyed on its next request and the user sees Magento's standard "signed out" notice.
 */
class SessionTerminator
{
    public function __construct(
        private readonly AdminSessionInfoFactory $sessionInfoFactory,
        private readonly AdminSessionInfoResource $sessionInfoResource,
        private readonly AuthSession $authSession,
        private readonly UserFactory $userFactory,
        private readonly LoginLogger $loginLogger
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function terminate(int $sessionInfoId): void
    {
        if ($sessionInfoId === (int)$this->authSession->getAdminSessionInfoId()) {
            throw new LocalizedException(__('This is your own session. Use "Sign Out" instead.'));
        }
        $info = $this->sessionInfoFactory->create();
        $this->sessionInfoResource->load($info, $sessionInfoId);
        if (!$info->getId()) {
            throw new LocalizedException(__('The session no longer exists.'));
        }
        if ((int)$info->getStatus() !== AdminSessionInfo::LOGGED_IN) {
            return;
        }
        $info->setStatus(AdminSessionInfo::LOGGED_OUT_MANUALLY);
        $this->sessionInfoResource->save($info);

        $user = $this->userFactory->create()->load((int)$info->getUserId());
        $by = $this->authSession->getUser() ? (string)$this->authSession->getUser()->getUserName() : '';
        $this->loginLogger->logLogout(
            $user,
            (string)__('Session terminated by %1', $by),
            $sessionInfoId,
            (string)$info->getIp()
        );
    }
}
