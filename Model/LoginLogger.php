<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;
use Softaware\ActivityLog\Model\ResourceModel\Login as LoginResource;

/**
 * Writes the admin login log and triggers the security notifications.
 */
class LoginLogger
{
    private const GENERIC_MESSAGE = 'The account sign-in was incorrect';

    private int $lastSuccessId = 0;

    public function __construct(
        private readonly Config $config,
        private readonly LoginResource $loginResource,
        private readonly RequestContext $context,
        private readonly AuthSession $authSession,
        private readonly UserFactory $userFactory,
        private readonly UserResource $userResource,
        private readonly SecurityNotifier $notifier,
        private readonly LoggerInterface $logger
    ) {
    }

    public function logSuccess(User $user): void
    {
        if (!$this->config->isLoginLogEnabled()) {
            return;
        }
        $country = $this->context->getCountry();
        $ip = $this->context->getIp();
        $id = $this->write([
            'user_id' => (int)$user->getId(),
            'username' => (string)$user->getUserName(),
            'status' => Login::STATUS_SUCCESS,
            'country' => $country,
        ]);
        if ($id) {
            $this->lastSuccessId = $id;
            $this->notifier->checkNewLocation($user, $id, $ip, $country);
        }
    }

    /**
     * Magento_Security creates the admin session record after the login event; link it to the log row then.
     */
    public function attachSession(int $sessionInfoId): void
    {
        if ($this->lastSuccessId && $sessionInfoId) {
            try {
                $this->loginResource->getConnection()->update(
                    $this->loginResource->getMainTable(),
                    ['session_info_id' => $sessionInfoId],
                    ['login_id = ?' => $this->lastSuccessId]
                );
            } catch (\Throwable $e) {
                $this->logger->error('Softaware_ActivityLog: could not link admin session: ' . $e->getMessage());
            }
            $this->lastSuccessId = 0;
        }
    }

    public function logFailure(string $username, ?\Throwable $exception): void
    {
        if (!$this->config->isLoginLogEnabled()) {
            return;
        }
        $user = $this->userFactory->create();
        if ($username !== '') {
            $this->userResource->load($user, $username, 'username');
        }
        $status = Login::STATUS_FAILURE;
        $message = $exception ? (string)$exception->getMessage() : '';
        if (!$user->getId()) {
            $reason = (string)__('Unknown username');
        } elseif ($exception instanceof UserLockedException || $this->isLocked($user)) {
            $status = Login::STATUS_LOCKED;
            $reason = (string)__('Account locked until %1 (UTC)', (string)$user->getLockExpires());
        } elseif (!$user->getIsActive()) {
            $reason = (string)__('Account is inactive');
        } elseif ($message !== '' && !str_contains($message, self::GENERIC_MESSAGE)) {
            $reason = $message;
        } else {
            $reason = (string)__('Wrong password');
        }
        $this->write([
            'user_id' => $user->getId() ? (int)$user->getId() : null,
            'username' => mb_substr($username, 0, 255),
            'status' => $status,
            'country' => $this->context->getCountry(),
            'reason' => mb_substr($reason, 0, 255),
        ]);
        $this->notifier->checkFailures($this->context->getIp(), $username, $status === Login::STATUS_LOCKED);
    }

    public function logBlocked(string $username): void
    {
        if (!$this->config->isLoginLogEnabled()) {
            return;
        }
        $this->write([
            'username' => mb_substr($username, 0, 255),
            'status' => Login::STATUS_BLOCKED,
            'country' => $this->context->getCountry(),
            'reason' => (string)__('IP address is not on the admin allowlist'),
        ]);
    }

    public function logLogout(?User $user, string $reason = '', ?int $sessionInfoId = null, ?string $ip = null): void
    {
        if (!$this->config->isLoginLogEnabled() || !$user || !$user->getId()) {
            return;
        }
        $row = $ip !== null ? ['ip' => $ip, 'user_agent' => null, 'country' => null] : [];
        $this->write($row + [
            'user_id' => (int)$user->getId(),
            'username' => (string)$user->getUserName(),
            'status' => Login::STATUS_LOGOUT,
            'country' => $this->context->getCountry(),
            'reason' => mb_substr($reason, 0, 255) ?: null,
            'session_info_id' => $sessionInfoId ?? ($this->authSession->getAdminSessionInfoId() ?: null),
        ]);
    }

    private function isLocked(User $user): bool
    {
        $lockExpires = $user->getLockExpires();
        return $lockExpires && strtotime((string)$lockExpires . ' UTC') > time();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function write(array $row): int
    {
        try {
            return $this->loginResource->insertRow($row + [
                'created_at' => gmdate('Y-m-d H:i:s'),
                'ip' => $this->context->getIp(),
                'user_agent' => $this->context->getUserAgent(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Softaware_ActivityLog: could not write login log: ' . $e->getMessage());
            return 0;
        }
    }
}
